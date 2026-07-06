# Fiche de décision — POC L0 (voix fon / yoruba)

- **Date :** __________
- **Responsable décision :** __________
- **Version des modèles :** SpeechBrain dvoice-fongbe · MMS-1b-all (yor) · NLLB-200-distilled-600M · Whisper large-v3

---

## 1. Jeu de test réellement constitué

| | Fon | Yoruba |
|---|---|---|
| Audio brut collecté (h) | | |
| Gold set transcrit (min) | | |
| Nb de clips évalués | | |
| Part conditions dégradées (%) | | |

---

## 2. Résultats mesurés

| Métrique | Seuil Go | Fon | Verdict | Yoruba | Verdict |
|---|---|---|---|---|---|
| ASR WER (avec tons) | < 0.25 | | ⬜ | | ⬜ |
| ASR CER (avec tons) | < 0.12 | | ⬜ | | ⬜ |
| Adéquation traduction (1-5) | ≥ 4 | | ⬜ | | ⬜ |
| Compréhension end-to-end (%) | ≥ 70 | | ⬜ | | ⬜ |

*(WER/CER sans tons, pour information : Fon ___ / ___ · Yoruba ___ / ___)*

---

## 3. Décision

- [ ] **Go total** — voix livrée en fon **et** yoruba au L3.
- [ ] **Go partiel** — voix livrée en __________ ; __________ reste en saisie texte au MVP.
- [ ] **No-Go** — voix reportée en itération 2 ; MVP = AI-native + conversationnel en texte.

**Justification (2-3 lignes) :**

> ____________________________________________________________
> ____________________________________________________________

---

## 4. Actions déclenchées

| Action | Responsable | Échéance |
|---|---|---|
| | | |
| | | |

---

## 5. Notes / limites observées

> (ex. faiblesse MMS sur oral spontané yoruba, code-switching mal géré, licence NC à trancher, etc.)
>
> ____________________________________________________________
