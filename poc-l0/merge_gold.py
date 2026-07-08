"""Fusionne les transcriptions saisies (gold-tool/gold.sqlite) dans data/manifest.csv.

La fusion se fait **par `id`**, jamais par position : aucun décalage possible.
Par défaut, une référence déjà présente dans le manifeste n'est PAS écrasée
(utiliser --force pour l'autoriser).

Usage :
    python merge_gold.py --dry-run     # montre ce qui serait fait, n'écrit rien
    python merge_gold.py               # fusionne
    python merge_gold.py --force       # autorise l'écrasement des références existantes
"""
import argparse
import sqlite3
import sys
import unicodedata
from pathlib import Path

import pandas as pd

from config import MANIFEST, ROOT

DB = ROOT / "gold-tool" / "gold.sqlite"


def combining_marks(text: str) -> int:
    """Nombre de diacritiques combinants (indicateur de marques tonales)."""
    return sum(1 for c in unicodedata.normalize("NFD", text)
               if unicodedata.category(c) == "Mn")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--db", default=str(DB), help="base SQLite de l'outil de saisie")
    ap.add_argument("--dry-run", action="store_true", help="n'écrit rien")
    ap.add_argument("--force", action="store_true", help="écrase les références existantes")
    args = ap.parse_args()

    db_path = Path(args.db)
    if not db_path.exists():
        sys.exit(f"Base introuvable : {db_path}\n"
                 f"→ aucun locuteur n'a encore saisi de transcription.")

    df = pd.read_csv(MANIFEST, dtype=str).fillna("")
    if "reference" not in df.columns:
        sys.exit("Colonne 'reference' absente du manifeste.")
    known = set(df["id"])

    con = sqlite3.connect(db_path)
    con.row_factory = sqlite3.Row
    rows = con.execute("SELECT id, lang, reference, status FROM gold").fetchall()
    con.close()

    by_id = {r["id"]: r for r in rows}
    inconnus = [i for i in by_id if i not in known]

    ajoutes, ecrases, ignores, vides = 0, 0, 0, 0
    tons_perdus = []

    for idx, row in df.iterrows():
        r = by_id.get(row["id"])
        if r is None:
            continue
        nouvelle = unicodedata.normalize("NFC", (r["reference"] or "").strip())
        if not nouvelle:
            vides += 1          # clip 'skipped' ou pas encore saisi
            continue
        actuelle = row["reference"].strip()
        if actuelle and not args.force:
            if actuelle != nouvelle:
                ignores += 1
            continue
        if actuelle and args.force and actuelle != nouvelle:
            ecrases += 1
        elif not actuelle:
            ajoutes += 1
        if combining_marks(nouvelle) == 0 and len(nouvelle) > 15:
            tons_perdus.append(row["id"])   # suspect : longue phrase sans aucun ton
        df.at[idx, "reference"] = nouvelle

    # --- Rapport -----------------------------------------------------------
    print(f"Saisies dans la base : {len(rows)}")
    if inconnus:
        print(f"  ⚠ {len(inconnus)} id(s) inconnus du manifeste (ignorés) : {inconnus[:3]}...")
    print(f"  + {ajoutes} références ajoutées")
    if ecrases:
        print(f"  ! {ecrases} références écrasées (--force)")
    if ignores:
        print(f"  ⚠ {ignores} références différentes NON écrasées (relancer avec --force pour forcer)")
    print(f"  · {vides} clips sans texte (musique / inaudible / à faire)")
    if tons_perdus:
        print(f"  ⚠ {len(tons_perdus)} phrases longues sans aucune marque tonale — "
              f"vérifier l'encodage : {tons_perdus[:3]}")

    print("\nAvancement du gold set :")
    rempli = df["reference"].str.strip() != ""
    for lang, sub in df.groupby("lang"):
        n = (sub["reference"].str.strip() != "").sum()
        print(f"  {lang} : {n}/{len(sub)} clips transcrits")
    print(f"  TOTAL : {rempli.sum()}/{len(df)}")

    if args.dry_run:
        print("\n(--dry-run : rien n'a été écrit)")
        return
    if ajoutes == 0 and ecrases == 0:
        print("\nRien à écrire.")
        return

    df.to_csv(MANIFEST, index=False, encoding="utf-8")
    print(f"\n✔ {MANIFEST} mis à jour.")
    print("→ Évaluer : python 04_evaluate.py --input data/translations.csv")


if __name__ == "__main__":
    main()
