"""Orchestrateur — enchaîne 02 (ASR) → 03 (traduction) → 04 (évaluation).

Usage :
    python run_pipeline.py --manifest data/manifest.csv

Chaque étape est lancée dans un sous-processus (isolation des modèles / mémoire).
L'étape 05 (extraction de claim, API Claude) reste manuelle et facultative.
"""
import argparse
import subprocess
import sys
from pathlib import Path

from config import ROOT, TRANSCRIPTS, TRANSLATIONS


def run(step: str, *cli):
    print(f"\n{'='*60}\n▶ {step}\n{'='*60}")
    r = subprocess.run([sys.executable, str(ROOT / step), *cli])
    if r.returncode != 0:
        sys.exit(f"Échec de {step} (code {r.returncode})")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--manifest", default="data/manifest.csv")
    args = ap.parse_args()

    if not (ROOT / args.manifest).exists():
        sys.exit(f"Manifeste introuvable : {args.manifest}\n"
                 f"→ lancez d'abord 01_prepare_audio.py puis remplissez 'reference'.")

    tr = str(TRANSCRIPTS.relative_to(ROOT)).replace("\\", "/")
    tl = str(TRANSLATIONS.relative_to(ROOT)).replace("\\", "/")

    run("02_transcribe.py", "--manifest", args.manifest, "--output", tr)
    run("03_translate.py", "--input", tr, "--output", tl)
    run("04_evaluate.py", "--input", tl)

    print("\n✔ Pipeline terminé. Voir data/report.md")
    print("  (option) claim + end-to-end : python 05_extract_claim.py --input", tl)


if __name__ == "__main__":
    main()
