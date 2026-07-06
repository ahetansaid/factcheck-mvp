"""Étape 3 — Traduction des transcriptions vers le français (NLLB-200).

Traduit la colonne `hypothesis` (langue locale) vers le français -> `french_hyp`.
Les lignes déjà en français sont recopiées telles quelles.

Sortie : data/translations.csv

Usage :
    python 03_translate.py --input data/transcripts.csv --output data/translations.csv
"""
import argparse

import pandas as pd

from config import ROOT, NLLB_MODEL, TARGET_LANG, LANGUAGES


class NLLBTranslator:
    def __init__(self):
        from transformers import AutoModelForSeq2SeqLM, AutoTokenizer
        print(f"chargement {NLLB_MODEL}...")
        self.tok = AutoTokenizer.from_pretrained(NLLB_MODEL)
        self.model = AutoModelForSeq2SeqLM.from_pretrained(NLLB_MODEL)
        self.model.eval()
        self.bos = self.tok.convert_tokens_to_ids(TARGET_LANG)

    def translate(self, text: str, src_lang: str) -> str:
        if not text.strip():
            return ""
        self.tok.src_lang = src_lang
        enc = self.tok(text, return_tensors="pt", truncation=True, max_length=512)
        gen = self.model.generate(
            **enc, forced_bos_token_id=self.bos, max_length=512, num_beams=4,
        )
        return self.tok.batch_decode(gen, skip_special_tokens=True)[0].strip()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", default="data/transcripts.csv")
    ap.add_argument("--output", default="data/translations.csv")
    args = ap.parse_args()

    df = pd.read_csv(ROOT / args.input, dtype=str).fillna("")
    translator = NLLBTranslator()

    french_hyp = []
    for _, row in df.iterrows():
        if row["lang"] == "fr":
            french_hyp.append(row["hypothesis"])  # déjà en français
        else:
            src = LANGUAGES[row["lang"]]["nllb_src"]
            fr = translator.translate(row["hypothesis"], src)
            french_hyp.append(fr)
            print(f"  [{row['id']}] {fr[:70]}")
    df["french_hyp"] = french_hyp

    out = ROOT / args.output
    df.to_csv(out, index=False, encoding="utf-8")
    print(f"→ {out}")


if __name__ == "__main__":
    main()
