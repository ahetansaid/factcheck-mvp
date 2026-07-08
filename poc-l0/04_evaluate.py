"""Étape 4 — Évaluation WER/CER + rapport Go/No-Go.

Calcule, par langue :
  - WER / CER AVEC marques tonales (seuil appliqué sur cette variante)
  - WER / CER SANS marques tonales (informatif)
  - chrF de la traduction si `french_reference` est fourni dans le manifeste

Sorties : data/report.md  +  data/report.json

Usage :
    python 04_evaluate.py --input data/translations.csv
"""
import argparse
import json
import re
import unicodedata

import jiwer
import pandas as pd

from config import ROOT, REPORT_MD, REPORT_JSON, THRESHOLDS

try:
    import sacrebleu
    HAS_SACREBLEU = True
except ImportError:
    HAS_SACREBLEU = False


# --- Normalisation ---------------------------------------------------------
# Certains modèles encodent une lettre africaine avec un point de code homoglyphe.
# Le modèle DVoice-fongbé sort l'epsilon GREC (ε U+03B5) là où l'orthographe fongbé
# utilise l'epsilon latin (ɛ U+025B). Sans unification, chaque occurrence compterait
# comme une erreur et gonflerait le WER (611 occurrences sur notre seul lot fon).
CONFUSABLES = str.maketrans({
    "ε": "ɛ",   # ε grec  -> ɛ latin
    "ο": "o",        # ο grec  -> o latin
})


def normalize(text: str) -> str:
    """Minuscules, homoglyphes unifiés, ponctuation retirée — MARQUES TONALES CONSERVÉES.

    Attention : on ne peut pas utiliser `[^\\w\\s]` pour retirer la ponctuation, car
    `\\w` ne matche pas les diacritiques combinants (catégorie Mn). Ils seraient
    remplacés par une espace, ce qui supprime le ton *et* coupe le mot en deux
    ('tɔ́n' -> 'tɔ n'). Or ɔ́, ɛ̀, ẹ̀, ọ́ n'ont pas de forme précomposée : le bug ne
    frappait que les lettres propres au fon et au yoruba.
    """
    text = unicodedata.normalize("NFC", text).lower().strip()
    text = text.translate(CONFUSABLES)
    # Retire ponctuation (P*) et symboles (S*) ; garde lettres, chiffres et marques (M*).
    text = "".join(" " if unicodedata.category(c)[0] in ("P", "S") else c for c in text)
    text = re.sub(r"\s+", " ", text)
    return text.strip()


def strip_tones(text: str) -> str:
    """Retire les diacritiques combinants (marques tonales)."""
    decomposed = unicodedata.normalize("NFD", text)
    no_marks = "".join(c for c in decomposed if unicodedata.category(c) != "Mn")
    return unicodedata.normalize("NFC", no_marks)


def score_pair(refs, hyps):
    refs = [normalize(r) for r in refs]
    hyps = [normalize(h) for h in hyps]
    # jiwer ignore les refs vides -> on filtre les paires où ref est vide
    pairs = [(r, h) for r, h in zip(refs, hyps) if r]
    if not pairs:
        return None
    refs, hyps = zip(*pairs)
    return {
        "n": len(refs),
        "wer": round(jiwer.wer(list(refs), list(hyps)), 4),
        "cer": round(jiwer.cer(list(refs), list(hyps)), 4),
    }


def evaluate_lang(sub: pd.DataFrame) -> dict:
    refs = sub["reference"].tolist()
    hyps = sub["hypothesis"].tolist()

    marked = score_pair(refs, hyps)
    stripped = score_pair([strip_tones(r) for r in refs],
                          [strip_tones(h) for h in hyps])

    result = {"marked": marked, "stripped": stripped}

    # chrF traduction (optionnel : requiert french_reference non vide)
    if HAS_SACREBLEU and "french_reference" in sub.columns:
        fr_pairs = [(r, h) for r, h in zip(sub["french_reference"], sub["french_hyp"])
                    if isinstance(r, str) and r.strip()]
        if fr_pairs:
            fr_refs, fr_hyps = zip(*fr_pairs)
            chrf = sacrebleu.corpus_chrf(list(fr_hyps), [list(fr_refs)]).score
            result["chrf"] = round(chrf, 1)
    return result


def verdict(value, threshold, lower_is_better=True):
    if value is None:
        return "—"
    ok = value < threshold if lower_is_better else value >= threshold
    return "✅" if ok else "❌"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", default="data/translations.csv")
    args = ap.parse_args()

    df = pd.read_csv(ROOT / args.input, dtype=str).fillna("")
    report = {"thresholds": THRESHOLDS, "languages": {}}

    lines = ["# Rapport POC L0 — WER/CER + Go/No-Go\n"]
    lines.append(f"Seuils : WER < {THRESHOLDS['asr_wer_max']} · "
                 f"CER < {THRESHOLDS['asr_cer_max']} · chrF ≥ {THRESHOLDS['chrf_min']}\n")

    for lang in sorted(df["lang"].unique()):
        if lang == "fr":
            continue  # le français n'est qu'un contrôle, pas un critère Go/No-Go
        sub = df[df["lang"] == lang]
        res = evaluate_lang(sub)
        report["languages"][lang] = res

        m, s = res.get("marked"), res.get("stripped")
        chrf = res.get("chrf")
        lines.append(f"\n## Langue : {lang}\n")
        if m is None:
            lines.append("_Aucune référence remplie — remplissez la colonne `reference`._\n")
            continue
        lines.append(f"- Clips évalués : **{m['n']}**")
        lines.append(f"- WER (avec tons) : **{m['wer']}** {verdict(m['wer'], THRESHOLDS['asr_wer_max'])}")
        lines.append(f"- CER (avec tons) : **{m['cer']}** {verdict(m['cer'], THRESHOLDS['asr_cer_max'])}")
        lines.append(f"- WER (sans tons) : {s['wer']}  ·  CER (sans tons) : {s['cer']}")
        if chrf is not None:
            lines.append(f"- chrF traduction : **{chrf}** "
                         f"{verdict(chrf, THRESHOLDS['chrf_min'], lower_is_better=False)}")
        else:
            lines.append("- chrF traduction : — (remplir `french_reference` pour l'activer)")

    lines.append("\n---\n")
    lines.append("Métriques humaines à reporter à la main (voir protocole §2) :")
    lines.append("- Adéquation traduction (1-5) : ______")
    lines.append("- Compréhension end-to-end (%) : ______  "
                 f"(seuil ≥ {int(THRESHOLDS['e2e_min']*100)} %)")

    REPORT_MD.write_text("\n".join(lines), encoding="utf-8")
    REPORT_JSON.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
    print("\n".join(lines))
    print(f"\n→ {REPORT_MD}\n→ {REPORT_JSON}")


if __name__ == "__main__":
    main()
