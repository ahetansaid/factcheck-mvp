# Vérifon — documentation du projet

Plateforme de fact-checking **AI-native, conversationnelle et multilingue** pour le
Bénin (français, fon, yoruba). Ce document inventorie **tout ce qui a été construit**,
comment le lancer, et ce qui reste.

> Vision et cadrage initial : voir [`../README.md`](../README.md) (plan MVP).
> Déploiement en production : voir [`deploiement.md`](deploiement.md).

---

## 1. Les trois différenciateurs (tous concrétisés)

| # | Différenciateur | État |
|---|---|---|
| ① | **AI-native** — chaque vérification publiée porte le balisage `ClaimReview` (schema.org), citable par Google/Perplexity/ChatGPT ; validé en CI | ✅ construit |
| ② | **Bot conversationnel** — « c'est vrai ? » → réponse **ancrée + sourcée**, jamais inventée. Récupération déterministe → **sémantique** → reformulation Claude | ✅ construit |
| ③ | **Vérification par la voix** (fon/yoruba) — POC de faisabilité linguistique mesuré | ✅ POC (branché au L3 plus tard) |

---

## 2. Structure du dépôt

```
webalizer/
├── README.md              Plan MVP (vision, stack, séquencement)
├── core/                  Laravel 12 — API publique + administration Filament + ClaimReview
├── web/                   Next.js 15 — site public SSR (App Router, Tailwind)
├── workers/               FastAPI — worker d'embeddings (recherche sémantique)
├── poc-l0/                POC voix fon/yoruba (pipeline ASR→traduction→WER) + gold-tool
├── demo/ + demo-site/     Page de candidature EPSG (Artifact + version Vercel)
├── docs/                  Cette doc, déploiement, POC, plateforme
└── .github/workflows/     CI (tests + validation ClaimReview)
```

---

## 3. Architecture

```
NAVIGATEUR
    │
    ▼
web/  Next.js 15 (SSR)  ──► routes proxy /api/ask, /api/submissions
    │   pages : accueil, /recherche, /verifications/[slug] (ClaimReview injecté),
    │           /personnalites, /signaler ; widget de chat ; sitemap + RSS
    │  (fetch serveur)
    ▼
core/ Laravel 12  ──► API REST publique + administration Filament + génération ClaimReview
    │                 bot (VerificationSearch) + file éditoriale + throttle
    ├──► workers/ FastAPI (port 8100)  — embeddings e5 (recherche sémantique)
    ├──► API Anthropic                 — reformulation des réponses (si clé + crédit)
    ▼
BASE : SQLite (dev) / PostgreSQL (cible prod)   — vecteurs stockés en JSON, cosinus en PHP
```

---

## 4. Composants en détail

### 4.1 `core/` — Backend Laravel 12 + Filament v5

**Modèles** (`app/Models`) : `Verification` (affirmation, verdict, résumé, corps,
catégorie, statut, `embedding`), `Personality`, `Source`, `Submission` (file éditoriale).

**API publique** (`routes/api.php`, 8 routes) :

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/verifications` | liste paginée + recherche `?q=` + filtre `?category=` |
| GET | `/api/verifications/{slug}` | détail + **JSON-LD ClaimReview** |
| GET | `/api/categories` | catégories distinctes |
| GET | `/api/personalities` | annuaire + compteurs vrai/faux |
| GET | `/api/personalities/{slug}` | détail personnalité + ses vérifs |
| POST | `/api/ask` | **bot** : verdict cité ou transmission rédaction (throttle 20/min) |
| POST | `/api/submissions` | **signalement** public → file éditoriale (throttle 20/min) |

**Services** (`app/Services`) :
- `ClaimReviewService` — génère le graphe JSON-LD `ClaimReview` + `Article`.
- `VerificationSearch` — récupération **sémantique d'abord** (cosinus), repli **mots-clés**.
- `EmbeddingClient` — appelle le worker d'embeddings.
- `AnswerComposer` — reformulation Claude (repli déterministe si indisponible).

**Administration Filament** (`/admin`) : CRUD Vérifications (verdict/statut en menus,
sources en liste, éditeur riche), Personnalités (photo), **file éditoriale**
(`Submission`, statut modifiable), **tableau de bord** (stats + graphique des verdicts).

**Commandes Artisan** :
- `php artisan claimreview:validate` — valide le balisage (utilisée en CI).
- `php artisan embeddings:build [--all]` — vectorise les vérifications publiées.

### 4.2 `web/` — Site public Next.js 15

Pages (`app/`, SSR) : accueil (vérif à la une + catégories), `/recherche`,
`/verifications/[slug]` (**ClaimReview injecté dans le HTML**), `/personnalites` (+ `[slug]`),
`/signaler`. Plus : **widget de chat** (`components/ChatWidget.tsx`), `sitemap.xml`,
`rss.xml`, proxies `/api/ask` et `/api/submissions`. Design éditorial sombre, thème
clair/sombre, responsive.

### 4.3 `workers/` — Worker d'embeddings (FastAPI)

`embed_service.py` : sert `multilingual-e5-small` (384 dim, CPU) sur le port 8100.
`POST /embed` → vecteurs normalisés. Voir [`../workers/README.md`](../workers/README.md).
**pgvector n'est pas requis** à l'échelle MVP (cosinus en PHP).

### 4.4 `poc-l0/` — POC voix fon/yoruba

Pipeline Python : captation radio → découpage → ASR (SpeechBrain fon, MMS yoruba,
Whisper fr) → traduction NLLB → **WER/CER**. Outil web `gold-tool/` (saisie des
transcriptions de référence par des locuteurs natifs). 363 clips prêts. Voir
[`poc-l0-protocol.md`](poc-l0-protocol.md). Deviendra le worker voix du **L3**.

### 4.5 `demo/` + `demo-site/` — Page de candidature EPSG

Page autonome interactive (démo vocale avec extraits réels fon/yoruba, assistant,
preuve de capacité). `demo-site/` = version déployable sur Vercel.

---

## 5. Inventaire des fonctionnalités

| Fonctionnalité | État |
|---|---|
| CMS de vérifications (Filament) | ✅ |
| API publique + pagination + recherche + catégories | ✅ |
| Balisage ClaimReview (JSON-LD) + validation CI | ✅ |
| Site public SSR (accueil, détail, annuaire, recherche) | ✅ |
| sitemap.xml + rss.xml | ✅ |
| Bot conversationnel — récupération déterministe | ✅ |
| Bot — recherche **sémantique** (embeddings) | ✅ |
| Bot — reformulation **Claude** | ✅ code (⏳ crédit Anthropic) |
| File éditoriale (signalements bot + formulaire public) | ✅ |
| Tableau de bord rédaction | ✅ |
| Rate limiting (anti-spam) | ✅ |
| Tests automatisés (4) + CI GitHub | ✅ |
| Guide de déploiement | ✅ |
| POC voix fon/yoruba (faisabilité) | ✅ mesuré |
| Gold set voix (WER Go/No-Go) | ⏳ locuteurs natifs |

---

## 6. Démarrage rapide (développement)

Prérequis : PHP 8.2 + Composer, Node 20+, (le venv `poc-l0/.venv` pour le worker).

```bash
# 1. API + administration (Laravel)
cd core
composer install
php artisan migrate --seed        # SQLite, 3 vérifications de démo
php artisan serve --port=8000     # http://localhost:8000  ·  /admin

# 2. Site public (Next.js)
cd ../web
npm install
npm run dev                       # http://localhost:3000

# 3. (optionnel) Worker sémantique
cd ../workers
../poc-l0/.venv/Scripts/python.exe -m uvicorn embed_service:app --port 8100
cd ../core && php artisan embeddings:build
```

Administration : `http://localhost:8000/admin` — **admin@verifon.bj** / **verifon2026**.

---

## 7. Tests & intégration continue

- `cd core && php artisan test` — 4 tests (liste, ClaimReview valide, recherche, garde-fou bot).
- `.github/workflows/ci.yml` — à chaque push : install + migrate/seed + tests +
  `claimreview:validate`. Le build échoue si un balisage est incomplet.

---

## 8. Feuille de route (README §6)

| Lot | Contenu | État |
|---|---|---|
| **L0** | POC voix fon/yoruba (faisabilité mesurée) | ✅ construit (gold set en attente) |
| **L1** | Socle AI-native (CMS, API, ClaimReview, site SSR, personnalités) | ✅ construit |
| **L2** | Bot RAG (texte) — déterministe + sémantique + Claude | ✅ construit |
| **L3** | Canal vocal (branche le POC au bot) | ⏳ après gold set |
| **L4** | Détecteur de claims (amorcé par la file éditoriale) | 🟡 amorcé |

---

## 9. Ce qui reste, et pourquoi

| Sujet | Dépend de |
|---|---|
| Bot reformule ses réponses (Claude) | **crédit** sur le compte Anthropic (clé déjà fournie) |
| Go/No-Go chiffré de la voix | **gold set** transcrit par 2 locuteurs natifs |
| pgvector (recherche sémantique à grande échelle) | déploiement sur un serveur **Linux** (inutile en MVP) |
| Mise en ligne | suivre [`deploiement.md`](deploiement.md) |

---

## 10. Journal des réalisations (commits principaux)

```
65cced2  L2 v3 : recherche sémantique (embeddings e5 + cosinus PHP)
acc87ea  L2 v2 : reformulation Claude (ancrée + repli)
2dacd03  déploiement : durcissement prod + guide
2535735  CI : validation ClaimReview + tests
1917101  admin : tableau de bord rédaction
d4a35bc  web : recherche publique + catégories
0cc28dd  file éditoriale : signalements bot + formulaire public
a5f66b2  L2 v1 : bot RAG à garde-fou
a6087a2  web : design propre du site public
cf337fb  admin : formulaire de rédaction soigné
0a93c66  socle L1 AI-native (Laravel + Filament + Next.js)
4808b62  page de candidature EPSG (démo interactive)
060b15a…69fb9a4  POC voix : gold set, outil de saisie, corrections WER
```
