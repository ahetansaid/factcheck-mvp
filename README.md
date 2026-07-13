# Plateforme de fact-checking — Plan MVP (DRWINTECH)

> Document de travail technique. Cible : partenaire éditorial béninois éligible (appel EPSG).
> Périmètre : ce qui est **buildable sur nos moyens propres**, sans dépendance externe bloquante.

> 📌 **Ce document est le plan initial (vision).** Pour l'inventaire de **ce qui a été
> réellement construit**, comment le lancer et l'état d'avancement, voir
> **[`docs/etat-du-projet.md`](docs/etat-du-projet.md)**. Déploiement : [`docs/deploiement.md`](docs/deploiement.md).

---

## 1. Objectif du MVP

Livrer une plateforme de fact-checking qui n'est **pas** un blog de vérifications de plus,
mais un produit **AI-native, conversationnel et multilingue par la voix** — capacités
qu'aucune plateforme francophone d'Afrique de l'Ouest n'offre aujourd'hui.

Le MVP doit être **démontrable devant un partenaire** sans attendre :
- ❌ l'approbation Meta WhatsApp Business API,
- ❌ un budget GPU pour du monitoring radio 24/7,
- ❌ du matériel SDR de captation FM.

Ces trois éléments sont **reportés en post-MVP** (extensions de canal et de volume, pas le cœur du produit).

---

## 2. Ce qui rend le produit non-conventionnel

| # | Différenciateur | Pourquoi ça sort du lot | Coût de build |
|---|---|---|---|
| ① | **AI-native (ClaimReview)** | Les vérifications deviennent citables par Perplexity / ChatGPT / Claude | Faible |
| ② | **Bot conversationnel RAG** | On demande « c'est vrai ? » → réponse ancrée + source | Moyen |
| ③ | **Vérification par la voix (fon / yoruba)** | Personne au Bénin ne transcrit + vérifie le fon/yoruba | Moyen (conditionné au POC L0) |

Le « conventionnel » = site + articles + note Vrai/Faux. Notre écart se joue sur ①②③, pas sur
la radio 24/7 ou la détection deepfake (reportées).

---

## 3. Architecture MVP (resserrée)

```
FRONTS      Next.js 15 (site + SSR/SEO)  ·  Widget chat web  ·  Bot Telegram
                                │
CORE        Laravel 11 ── API publique · CMS · Auth · ClaimReview · orchestration
                                │  (Redis + Horizon, asynchrone)
WORKERS IA  FastAPI (stateless) ── ASR (fon/yoruba/fr) · NLLB · embeddings · claim-extraction
                                │
DATA        PostgreSQL 16 + pgvector (source de vérité + recherche sémantique)  ·  MinIO (média)
```

**Choix de simplification (équipe 4-5 pers.) :** pas d'ElasticSearch (pgvector suffit),
pas de Kong (Nginx + middleware Laravel), pas de GPU, pas de Meta, pas de SDR au MVP.
→ **5 services** à opérer.

### Stack retenue

| Couche | Techno | Note |
|---|---|---|
| Core | Laravel 11 (PHP 8.3) | source de vérité, API, CMS |
| Workers IA | Python 3.12 + FastAPI | stateless, appelés en asynchrone |
| Front web | Next.js 15 (App Router) | SSR pour SEO + injection ClaimReview |
| Base | PostgreSQL 16 + **pgvector** | données + recherche sémantique |
| File | Redis + Laravel Horizon | ASR, embeddings, scraping en asynchrone |
| Média | MinIO (S3) | notes vocales, pièces jointes |
| Canaux bot | Telegram Bot API + widget web | WhatsApp reporté (post-MVP) |

---

## 4. Les 3 différenciateurs — détail technique

### ① AI-native — ClaimReview
- À la publication, Laravel génère le **JSON-LD `ClaimReview` + `Article`** injecté en SSR dans Next.js.
- `sitemap.xml` + RSS enrichi.
- Validation **Google Rich Results Test** intégrée à la CI.
- **Démo cible :** publier une vérif, montrer Perplexity/Claude qui la cite comme source.

### ② Bot conversationnel RAG
- Un seul endpoint backend, deux canaux (Telegram + widget web).
- **Indexation :** embedding de chaque vérif publiée → stocké en `pgvector`.
- **Requête :** question → embedding → recherche cosinus top-k → **Claude répond uniquement
  à partir des vérifs récupérées, avec citation**.
- **Garde-fou dur :** aucun match → « pas encore vérifié, transmis à l'équipe » +
  création d'une entrée dans la file éditoriale. **Le LLM n'invente jamais un verdict.**

### ③ Vérification par la voix
```
Note vocale ─► détection langue (bouton FR/Fon/Yoruba au MVP)
           ─► ASR routé :  Fon → SpeechBrain·DVoice   Yoruba → MMS-1b-all   FR → Whisper
           ─► transcription (langue locale)
           ─► NLLB → français
           ─► Claude : extraction du claim
           ─► pgvector : match sur base vérifiée
           ─► verdict + source (réponse re-traduite NLLB vers la langue d'origine)
```
> ⚠️ Ce différenciateur est **conditionné au Go du POC L0** (voir `docs/poc-l0-protocol.md`).

### Appuis qui nourrissent le RAG
- **Base personnalités** : CRUD + stats true/false, contexte du bot.
- **Détecteur de claims** : scraping 2-3 médias + **check-worthiness par Claude few-shot**
  (aucun BERT à entraîner) → file de priorisation éditoriale.

---

## 5. Modèles ASR/MT retenus (vérifiés)

| Langue | Modèle | Repo | Perf reportée | Licence |
|---|---|---|---|---|
| **Fon** | wav2vec2 SpeechBrain (DVoice/ALFFA) | `speechbrain/asr-wav2vec2-dvoice-fongbe` | WER 9,0 % · CER 3,98 % (parole lue) | **Apache 2.0** |
| **Yoruba** | Meta MMS-1B-all (adapter `yor`) | `facebook/mms-1b-all` | > Whisper, respecte les tons | CC-BY-NC 4.0 |
| **Français** | Whisper large-v3 | `openai/whisper-large-v3` | Excellent | MIT |
| **Traduction → FR** | NLLB-200 distilled | `facebook/nllb-200-distilled-600M` | `fon_Latn`/`yor_Latn` → `fra_Latn` | CC-BY-NC 4.0 |
| **Bariba** | — (aucun modèle) | — | — | ❌ hors MVP |

**Points d'attention :**
1. Le WER 9 % du fon est mesuré sur **parole lue propre** (DVoice). Sur radio réelle,
   prévoir 25-40 %+. → mesure réelle = **POC L0**.
2. **Licences :** fon = Apache 2.0 (OK commercial) ; MMS + NLLB = CC-BY-NC 4.0 (non-commercial).
   Acceptable *a priori* pour une plateforme d'intérêt public non lucrative — **à confirmer avec le partenaire**.
3. Tout tourne **sur CPU** pour des clips courts (note vocale) → compatible « pas de GPU au MVP ».

---

## 6. Séquencement de build

| Lot | Contenu | Dépend de |
|---|---|---|
| **L0 — POC langue** | Go/No-Go fon/yoruba sur audio réel (voir `docs/poc-l0-protocol.md`) | rien |
| **L1 — Socle + AI-native** | Next.js/Laravel/pgvector, CMS, ClaimReview auto, base personnalités | rien (parallèle à L0) |
| **L2 — Bot RAG texte** | Telegram + widget, embeddings, réponses citées, garde-fou, file éditoriale | L1 |
| **L3 — Voix** | intégration ASR + NLLB au bot | L0 = Go, L2 |
| **L4 — Détecteur de claims** | scraping + check-worthiness LLM | L1 |

> L1 + L2 donnent déjà une plateforme **AI-native + conversationnelle** démontrable
> sans attendre le POC. L3 branche la voix quand le Go tombe. Si **No-Go**, le fon/yoruba
> reste en **saisie texte** au MVP (le produit reste différenciant), la voix passe en itération 2.

---

## 7. Explicitement reporté (post-MVP)

WhatsApp officiel · monitoring radio à l'échelle (GPU) · captation SDR ·
détection deepfake · extension navigateur · bariba (R&D conditionnelle).

---

## 8. Arborescence du dépôt

```
factcheck-mvp/
├── README.md                     ← ce document (plan MVP)
├── docs/
│   ├── poc-l0-protocol.md        ← protocole POC L0 détaillé
│   └── go-no-go-template.md      ← fiche de décision Go/No-Go
└── poc-l0/                       ← code exécutable du POC
    ├── requirements.txt
    ├── config.py
    ├── 01_prepare_audio.py       ← normalise l'audio + génère le manifeste
    ├── 02_transcribe.py          ← ASR routé par langue
    ├── 03_translate.py           ← NLLB → français
    ├── 04_evaluate.py            ← WER/CER + rapport Go/No-Go
    ├── 05_extract_claim.py       ← (option) extraction du claim via Claude
    ├── run_pipeline.py           ← orchestre 02 → 03 → 04
    └── data/
        └── README.md             ← guide de collecte + format du gold set
```
