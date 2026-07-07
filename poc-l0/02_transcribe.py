"""Étape 2 — ASR routé par langue.

Lit le manifeste, transcrit chaque clip avec le bon backend :
  - fon → SpeechBrain wav2vec2 DVoice (Apache 2.0)
  - yor → Meta MMS-1b-all, adapter 'yor' (CC-BY-NC 4.0)
  - fr  → Whisper large-v3 (MIT)

Sortie : data/transcripts.csv  (manifeste + colonne `hypothesis`).

Les modèles sont chargés paresseusement (uniquement si la langue est présente),
une seule fois chacun. Tout tourne sur CPU.

Usage :
    python 02_transcribe.py --manifest data/manifest.csv --output data/transcripts.csv
"""
import argparse
import sys
import types
from pathlib import Path

import pandas as pd
import torch
import torchaudio

from config import (ROOT, SPEECHBRAIN_FON, MMS_MODEL, WHISPER_MODEL,
                    MODELS_DIR, SAMPLE_RATE, LANGUAGES)

# --- Compat versions -------------------------------------------------------
# Stack retenu : SpeechBrain 1.0.3 (le modèle DVoice-fongbé date de la 1.0 ;
# la 1.1.x casse l'import via ses "integrations" paresseuses) + torch/torchaudio
# 2.11 modernes (Python 3.13). Deux incompatibilités à combler :
#
# 1) torchaudio 2.11 a SUPPRIMÉ list_audio_backends()/get_audio_backend(), que
#    SpeechBrain 1.0.3 appelle au démarrage. load()/resample() restent présents,
#    donc un shim renvoyant un backend suffit (soundfile est installé).
if not hasattr(torchaudio, "list_audio_backends"):
    torchaudio.list_audio_backends = lambda: ["soundfile"]
if not hasattr(torchaudio, "get_audio_backend"):
    torchaudio.get_audio_backend = lambda: "soundfile"

# 2) k2 (décodage FSA) est inutile pour le CTC greedy d'ici et impossible à
#    installer sous Windows ; stub défensif au cas où un import le réclamerait.
if "k2" not in sys.modules:
    try:
        import k2  # noqa: F401
    except ImportError:
        _stub = types.ModuleType("k2")
        _stub.__version__ = "0.0.0-stub"
        sys.modules["k2"] = _stub


def load_audio(path: str):
    """Charge un WAV en mono 16 kHz -> tensor 1D."""
    wav, sr = torchaudio.load(str(ROOT / path))
    if wav.shape[0] > 1:
        wav = wav.mean(dim=0, keepdim=True)
    if sr != SAMPLE_RATE:
        wav = torchaudio.functional.resample(wav, sr, SAMPLE_RATE)
    return wav.squeeze(0)


# --- Backends (chargés à la demande) --------------------------------------
class FonASR:
    def __init__(self):
        try:
            from speechbrain.inference.ASR import EncoderASR
        except ImportError:  # SpeechBrain < 1.0
            from speechbrain.pretrained import EncoderASR
        print("  chargement SpeechBrain DVoice-Fongbe...")
        self.model = EncoderASR.from_hparams(
            source=SPEECHBRAIN_FON,
            savedir=str(MODELS_DIR / "asr-fon"),
            run_opts={"device": "cpu"},
        )

    def __call__(self, path: str) -> str:
        return self.model.transcribe_file(str(ROOT / path)).strip()


class YorubaASR:
    def __init__(self):
        from transformers import Wav2Vec2ForCTC, AutoProcessor
        print("  chargement MMS-1b-all (adapter yor)...")
        self.processor = AutoProcessor.from_pretrained(MMS_MODEL)
        self.model = Wav2Vec2ForCTC.from_pretrained(MMS_MODEL)
        self.processor.tokenizer.set_target_lang("yor")
        self.model.load_adapter("yor")
        self.model.eval()

    def __call__(self, path: str) -> str:
        wav = load_audio(path).numpy()
        inputs = self.processor(wav, sampling_rate=SAMPLE_RATE, return_tensors="pt")
        with torch.no_grad():
            logits = self.model(**inputs).logits
        ids = torch.argmax(logits, dim=-1)[0]
        return self.processor.decode(ids).strip()


class FrenchASR:
    def __init__(self):
        import whisper
        print(f"  chargement Whisper {WHISPER_MODEL}...")
        self.model = whisper.load_model(WHISPER_MODEL)

    def __call__(self, path: str) -> str:
        return self.model.transcribe(str(ROOT / path), language="fr")["text"].strip()


BACKENDS = {"speechbrain": FonASR, "mms": YorubaASR, "whisper": FrenchASR}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--manifest", default="data/manifest.csv")
    ap.add_argument("--output", default="data/transcripts.csv")
    args = ap.parse_args()

    df = pd.read_csv(ROOT / args.manifest, dtype=str).fillna("")
    if "hypothesis" not in df.columns:
        df["hypothesis"] = ""

    # Instancie uniquement les backends des langues présentes
    langs_present = set(df["lang"].unique())
    loaded = {}
    for lang in langs_present:
        backend_name = LANGUAGES[lang]["asr"]
        if backend_name not in loaded:
            print(f"[{lang}] backend = {backend_name}")
            loaded[backend_name] = BACKENDS[backend_name]()

    for i, row in df.iterrows():
        backend = loaded[LANGUAGES[row["lang"]]["asr"]]
        try:
            hyp = backend(row["audio_path"])
        except Exception as e:  # noqa: BLE001 — on veut continuer le batch
            hyp = ""
            print(f"  [ERREUR] {row['id']} : {e}")
        df.at[i, "hypothesis"] = hyp
        print(f"  [{row['id']}] {hyp[:70]}")

    out = ROOT / args.output
    df.to_csv(out, index=False, encoding="utf-8")
    print(f"→ {out}")


if __name__ == "__main__":
    main()
