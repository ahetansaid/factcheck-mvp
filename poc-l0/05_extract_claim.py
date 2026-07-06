"""Étape 5 (option) — Extraction du claim en français via Claude.

Pour chaque clip, transforme la transcription française (`french_hyp`) en une
affirmation vérifiable synthétique — exactement ce que le bot tenterait de matcher
dans la base de vérifications. Ce claim sert au scoring humain « compréhension end-to-end ».

Nécessite ANTHROPIC_API_KEY dans l'environnement.

Sortie : data/claims.csv (ajoute `claim_fr` + colonne vide `e2e_ok` à remplir : 1/0).

Usage :
    python 05_extract_claim.py --input data/translations.csv --output data/claims.csv
"""
import argparse
import os

import pandas as pd

from config import ROOT

MODEL = "claude-sonnet-5"  # rapide et suffisant pour de l'extraction ; ajustable

SYSTEM = (
    "Tu extrais l'affirmation factuelle vérifiable principale d'un extrait de discours "
    "traduit en français. Réponds par UNE seule phrase déclarative, concise et neutre, "
    "reformulant l'affirmation à vérifier. Si l'extrait ne contient aucune affirmation "
    "factuelle vérifiable, réponds exactement : AUCUN_CLAIM."
)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", default="data/translations.csv")
    ap.add_argument("--output", default="data/claims.csv")
    args = ap.parse_args()

    if not os.environ.get("ANTHROPIC_API_KEY"):
        raise SystemExit("ANTHROPIC_API_KEY manquante dans l'environnement.")

    import anthropic
    client = anthropic.Anthropic()

    df = pd.read_csv(ROOT / args.input, dtype=str).fillna("")
    claims = []
    for _, row in df.iterrows():
        text = row["french_hyp"].strip()
        if not text:
            claims.append("")
            continue
        msg = client.messages.create(
            model=MODEL,
            max_tokens=120,
            system=SYSTEM,
            messages=[{"role": "user", "content": text}],
        )
        claim = msg.content[0].text.strip()
        claims.append(claim)
        print(f"  [{row['id']}] {claim[:80]}")

    df["claim_fr"] = claims
    df["e2e_ok"] = ""  # à remplir à la main : 1 si le claim = sens réel, sinon 0
    out = ROOT / args.output
    df.to_csv(out, index=False, encoding="utf-8")
    print(f"→ {out}")
    print("→ Remplissez la colonne 'e2e_ok' (1/0) ; le taux moyen = métrique end-to-end.")


if __name__ == "__main__":
    main()
