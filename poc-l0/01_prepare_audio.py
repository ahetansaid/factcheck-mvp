"""Étape 1 — Normalise l'audio brut et génère (ou complète) le manifeste.

- Convertit tout fichier de data/audio/<lang>/ en WAV 16 kHz mono dans data/prepared/<lang>/.
- Ajoute une ligne par clip dans data/manifest.csv avec une colonne `reference` VIDE,
  à remplir par un locuteur natif (c'est le gold set).

Usage :
    python 01_prepare_audio.py --lang fon
    python 01_prepare_audio.py --lang yor
    python 01_prepare_audio.py --lang fr      # contrôle facultatif

Prérequis : ffmpeg dans le PATH.
"""
import argparse
import subprocess
import sys
import csv
from pathlib import Path

from config import ROOT, AUDIO_RAW, AUDIO_PREPARED, MANIFEST, SAMPLE_RATE, LANGUAGES

AUDIO_EXTS = {".wav", ".mp3", ".m4a", ".ogg", ".opus", ".flac", ".aac", ".webm"}


def ffmpeg_to_wav(src: Path, dst: Path) -> bool:
    """Convertit src en WAV mono 16 kHz. Retourne True si succès."""
    dst.parent.mkdir(parents=True, exist_ok=True)
    cmd = [
        "ffmpeg", "-y", "-i", str(src),
        "-ac", "1", "-ar", str(SAMPLE_RATE),
        "-loglevel", "error", str(dst),
    ]
    try:
        subprocess.run(cmd, check=True)
        return True
    except (subprocess.CalledProcessError, FileNotFoundError) as e:
        print(f"  [ERREUR] ffmpeg sur {src.name} : {e}", file=sys.stderr)
        return False


def load_existing_ids(manifest: Path) -> set:
    if not manifest.exists():
        return set()
    with manifest.open(encoding="utf-8", newline="") as f:
        return {row["id"] for row in csv.DictReader(f)}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--lang", required=True, choices=list(LANGUAGES.keys()))
    args = ap.parse_args()
    lang = args.lang

    src_dir = AUDIO_RAW / lang
    if not src_dir.exists():
        sys.exit(f"Répertoire introuvable : {src_dir}\n"
                 f"→ déposez l'audio brut dans {src_dir} puis relancez.")

    dst_dir = AUDIO_PREPARED / lang
    sources = sorted(p for p in src_dir.iterdir() if p.suffix.lower() in AUDIO_EXTS)
    if not sources:
        sys.exit(f"Aucun fichier audio dans {src_dir}")

    existing = load_existing_ids(MANIFEST)
    MANIFEST.parent.mkdir(parents=True, exist_ok=True)
    is_new = not MANIFEST.exists()

    rows_added = 0
    with MANIFEST.open("a", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        if is_new:
            writer.writerow(["id", "audio_path", "lang", "reference", "french_reference"])
        for src in sources:
            clip_id = f"{lang}_{src.stem}"
            if clip_id in existing:
                continue
            dst = dst_dir / f"{src.stem}.wav"
            if ffmpeg_to_wav(src, dst):
                # audio_path relatif à la racine poc-l0 (résolu ensuite via ROOT / path)
                rel = dst.relative_to(ROOT)  # -> data/prepared/<lang>/x.wav
                writer.writerow([clip_id, str(rel).replace("\\", "/"), lang, "", ""])
                rows_added += 1

    print(f"[{lang}] {rows_added} clips préparés → {dst_dir}")
    print(f"[{lang}] Manifeste : {MANIFEST}")
    print("→ Remplissez maintenant la colonne 'reference' (transcription native verbatim).")
    print("  (colonne 'french_reference' facultative : active le score chrF de traduction)")


if __name__ == "__main__":
    main()
