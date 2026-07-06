"""Configuration centrale du POC L0.

Une seule source de vérité pour les modèles, les langues, les chemins et les seuils.
"""
from pathlib import Path

# --- Répertoires -----------------------------------------------------------
ROOT = Path(__file__).resolve().parent
DATA = ROOT / "data"
AUDIO_RAW = DATA / "audio"          # data/audio/<lang>/*.{wav,mp3,m4a,...}
AUDIO_PREPARED = DATA / "prepared"  # data/prepared/<lang>/*.wav (16 kHz mono)
MODELS_DIR = ROOT / "models"        # caches SpeechBrain

MANIFEST = DATA / "manifest.csv"
TRANSCRIPTS = DATA / "transcripts.csv"
TRANSLATIONS = DATA / "translations.csv"
CLAIMS = DATA / "claims.csv"
REPORT_MD = DATA / "report.md"
REPORT_JSON = DATA / "report.json"

# --- Audio -----------------------------------------------------------------
SAMPLE_RATE = 16000

# --- Modèles ---------------------------------------------------------------
SPEECHBRAIN_FON = "speechbrain/asr-wav2vec2-dvoice-fongbe"  # Apache 2.0
MMS_MODEL = "facebook/mms-1b-all"                           # CC-BY-NC 4.0
WHISPER_MODEL = "large-v3"                                  # MIT
NLLB_MODEL = "facebook/nllb-200-distilled-600M"            # CC-BY-NC 4.0

# --- Routage par langue ----------------------------------------------------
# asr : quel backend ASR utiliser | nllb : code source FLORES-200 pour la traduction
LANGUAGES = {
    "fon": {"asr": "speechbrain", "nllb_src": "fon_Latn"},
    "yor": {"asr": "mms",         "nllb_src": "yor_Latn", "mms_lang": "yor"},
    "fr":  {"asr": "whisper",     "nllb_src": "fra_Latn", "whisper_lang": "fr"},
}
TARGET_LANG = "fra_Latn"  # on ramène tout vers le français

# --- Seuils Go/No-Go (voir docs/poc-l0-protocol.md §2) ---------------------
THRESHOLDS = {
    "asr_wer_max": 0.25,   # WER avec tons
    "asr_cer_max": 0.12,   # CER avec tons
    "chrf_min": 45.0,      # adéquation traduction (si gold FR fourni)
    "e2e_min": 0.70,       # compréhension end-to-end (scoré à la main)
}
