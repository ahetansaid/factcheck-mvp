"""Outil de collecte — découpe un enregistrement en clips de parole de 5-15 s.

Isole les zones parlées via la détection de silence de ffmpeg (silencedetect),
puis les tronçonne en clips de MIN_CLIP..MAX_CLIP secondes, déposés en WAV
16 kHz mono dans data/audio/<lang>/ — prêts pour 01_prepare_audio.py.

⚠️ La détection de silence ne distingue PAS parole et musique : les plages
musicales passeront le filtre. Un tri d'écoute (ou une passe VAD Silero) reste
recommandé pour ne garder que la parole en langue locale. Voir docs/poc-l0-protocol.md.

Prérequis : ffmpeg + ffprobe dans le PATH.

Usage :
    python segment.py --input data/captures/fon/radiocotonou-20260706-1700.mp3 --lang fon
"""
import argparse
import re
import subprocess
import sys
from pathlib import Path

from config import ROOT, AUDIO_RAW

MIN_CLIP = 5.0     # s — clips plus courts jetés
MAX_CLIP = 15.0    # s — zones plus longues redécoupées
NOISE = "-30dB"    # seuil de détection du silence
SIL_MIN = 0.4      # s — durée minimale d'un silence


def duration(path: Path) -> float:
    r = subprocess.run(
        ["ffprobe", "-v", "error", "-show_entries", "format=duration",
         "-of", "default=nk=1:nw=1", str(path)],
        capture_output=True, text=True)
    try:
        return float(r.stdout.strip())
    except ValueError:
        return 0.0


def silences(path: Path):
    r = subprocess.run(
        ["ffmpeg", "-i", str(path), "-af",
         f"silencedetect=noise={NOISE}:d={SIL_MIN}", "-f", "null", "-"],
        capture_output=True, text=True)
    starts = [float(x) for x in re.findall(r"silence_start: (-?[0-9.]+)", r.stderr)]
    ends = [float(x) for x in re.findall(r"silence_end: (-?[0-9.]+)", r.stderr)]
    return starts, ends


def speech_regions(path: Path):
    """Complément des silences sur [0, durée] = plages parlées."""
    total = duration(path)
    if total <= 0:
        return []
    starts, ends = silences(path)
    sil = []
    for i, s in enumerate(starts):
        e = ends[i] if i < len(ends) else total
        sil.append((max(0.0, s), min(total, e)))
    regions, cur = [], 0.0
    for s, e in sil:
        if s > cur:
            regions.append((cur, s))
        cur = max(cur, e)
    if cur < total:
        regions.append((cur, total))
    return regions


def chunk(regions):
    for start, end in regions:
        t = start
        while end - t >= MIN_CLIP:
            length = min(MAX_CLIP, end - t)
            yield round(t, 2), round(length, 2)
            t += length


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", required=True, help="fichier capturé (relatif à poc-l0/ ou absolu)")
    ap.add_argument("--lang", required=True, choices=["fon", "yor", "fr"])
    args = ap.parse_args()

    src = Path(args.input)
    if not src.is_absolute():
        src = ROOT / args.input
    if not src.exists():
        sys.exit(f"Introuvable : {src}")

    outdir = AUDIO_RAW / args.lang
    outdir.mkdir(parents=True, exist_ok=True)
    stem = src.stem

    regions = speech_regions(src)
    clips = list(chunk(regions))
    if not clips:
        sys.exit("Aucune zone parlée détectée (fichier vide, muet, ou seuil à ajuster).")
    print(f"{len(regions)} zones parlées → {len(clips)} clips (5-15 s)")

    n = 0
    for i, (ss, dd) in enumerate(clips):
        dst = outdir / f"{stem}-{i:03d}.wav"
        cmd = ["ffmpeg", "-y", "-ss", str(ss), "-t", str(dd), "-i", str(src),
               "-ac", "1", "-ar", "16000", "-loglevel", "error", str(dst)]
        try:
            subprocess.run(cmd, check=True)
            n += 1
        except subprocess.CalledProcessError as e:
            print(f"  [skip] clip {i}: {e}")
    print(f"✔ {n} clips → {outdir}")
    print(f"→ Prépare le manifeste : python 01_prepare_audio.py --lang {args.lang}")


if __name__ == "__main__":
    main()
