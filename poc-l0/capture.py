"""Outil de collecte — enregistre N minutes d'un flux radio en ligne.

Capture un stream direct (mp3 / aac / m3u8...) via ffmpeg vers
data/captures/<lang>/<name>-<date>.mp3, en mono pour économiser l'espace.
Le fichier capturé se découpe ensuite en clips avec segment.py.

Prérequis : ffmpeg dans le PATH.

Usage :
    python capture.py --url https://stream.zeno.fm/o9bspgm4y78vv \
                      --lang fon --name radiocotonou --minutes 30

Astuce : Ctrl+C arrête proprement ; le fichier partiel déjà capturé est conservé.
"""
import argparse
import subprocess
import sys
from datetime import datetime
from pathlib import Path

from config import ROOT, DATA

CAPTURES = DATA / "captures"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--url", required=True, help="URL du flux (mp3/aac/m3u8...)")
    ap.add_argument("--lang", required=True, choices=["fon", "yor", "fr"])
    ap.add_argument("--name", required=True, help="étiquette station, ex: radiocotonou")
    ap.add_argument("--minutes", type=float, default=30, help="durée d'enregistrement")
    args = ap.parse_args()

    outdir = CAPTURES / args.lang
    outdir.mkdir(parents=True, exist_ok=True)
    ts = datetime.now().strftime("%Y%m%d-%H%M")
    dst = outdir / f"{args.name}-{ts}.mp3"
    dur = int(args.minutes * 60)

    cmd = [
        "ffmpeg", "-y",
        "-i", args.url,
        "-t", str(dur),
        "-acodec", "libmp3lame", "-ar", "44100", "-ac", "1",
        "-loglevel", "warning", "-stats",
        str(dst),
    ]
    print(f"→ Capture {args.minutes} min · {args.name} ({args.lang})")
    print(f"  source : {args.url}")
    print(f"  sortie : {dst}")
    try:
        subprocess.run(cmd, check=True)
    except KeyboardInterrupt:
        print("\n(interrompu — fichier partiel conservé)")
    except FileNotFoundError:
        sys.exit("ffmpeg introuvable dans le PATH.")
    except subprocess.CalledProcessError as e:
        # ffmpeg renvoie souvent un code non nul sur un stream coupé : on garde le partiel
        print(f"[avertissement] ffmpeg a terminé avec le code {e.returncode}")

    if dst.exists() and dst.stat().st_size > 0:
        mb = dst.stat().st_size / 1e6
        rel = dst.relative_to(ROOT)
        print(f"✔ {dst.name} ({mb:.1f} Mo)")
        print(f"→ Découpe : python segment.py --input {str(rel).replace(chr(92), '/')} --lang {args.lang}")
    else:
        sys.exit("Aucune donnée capturée — vérifiez l'URL du flux.")


if __name__ == "__main__":
    main()
