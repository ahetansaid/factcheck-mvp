# POC L0 — Faisabilité de la vérification par la voix (fon / yoruba)

> **But :** décider, chiffres à l'appui, si le différenciateur « vérification par la voix
> en fon et yoruba » tient sur de l'audio béninois **réel** — avant d'écrire une ligne du L3.
> **Timebox : 2 à 3 semaines.** Résultat : un **Go** ou un **No-Go** documenté (`go-no-go-template.md`).

---

## 1. Ce qu'on teste (et ce qu'on ne teste pas)

On teste **la chaîne parole → français exploitable** :

```
audio réel ─► ASR (fon: SpeechBrain·DVoice | yor: MMS | fr: Whisper) ─► NLLB → français ─► claim en français
```

On **ne** teste **pas** au L0 : le bot, l'UI, WhatsApp, la base RAG. C'est un POC de **qualité linguistique**, rien d'autre.

**Hypothèse à valider :** sur de l'audio radio/terrain réel, la chaîne produit un claim en français
suffisamment fidèle pour être matché dans la base de vérifications.

---

## 2. Critères Go / No-Go (fixés à l'avance)

| Métrique | Mesure | Seuil **Go** | Automatisé ? |
|---|---|---|---|
| **ASR WER** (par langue) | Word Error Rate sur le gold set audio réel | **< 25 %** | ✅ `04_evaluate.py` |
| **ASR CER** (par langue) | Character Error Rate (indicatif tonalité) | < 12 % | ✅ `04_evaluate.py` |
| **Adéquation traduction** | Note humaine 1-5 sur les phrases porteuses de claim (ou chrF si gold FR fourni) | **≥ 4/5** (ou chrF ≥ 45) | Semi |
| **Compréhension end-to-end** | % de clips où le claim français extrait = le sens réel (jugé par un humain) | **≥ 70 %** | Manuel (grille) |

**Règle de décision :**
- **Go langue** si les 4 seuils sont tenus pour cette langue.
- **No-Go partiel** : une langue passe, l'autre non → on livre la voix pour la langue qui passe,
  l'autre reste en **saisie texte** au MVP.
- **No-Go total** : aucune langue ne passe → la voix passe en itération 2 ; le MVP reste
  AI-native + conversationnel en texte (fon/yoruba tapé + français).

> On mesure **deux variantes** de WER/CER : **avec** marques tonales et **sans**
> (les diacritiques tonaux gonflent artificiellement le WER). Les deux figurent au rapport ;
> le seuil s'applique à la variante **avec tons** pour être conservateur.

---

## 3. Constitution du jeu de test

### 3.1 Volumes cibles

| Élément | Fon | Yoruba |
|---|---|---|
| Audio brut collecté | 2–3 h | 2–3 h |
| **Gold set** (transcrit main, natif) | **30 min** | **30 min** |
| Découpage en clips courts | 5–15 s / clip (~120–200 clips par langue) | idem |

### 3.2 Diversité obligatoire du gold set

Le gold **doit** couvrir les conditions réelles, pas seulement du studio propre :

- ✅ Studio radio propre (voix claire, sans musique)
- ✅ Radio avec fond musical / jingle
- ✅ Qualité téléphone / appel entrant à l'antenne
- ✅ Parole spontanée / débit rapide
- ✅ **Code-switching** fon↔français / yoruba↔français (fréquent en radio béninoise)

> Un gold set 100 % studio propre donnerait un WER flatteur mais mensonger. La diversité
> est ce qui rend le Go/No-Go honnête.

### 3.3 Sources (uniquement diffusion publique)

- Streams IP des FM béninoises qui en ont un.
- Lives / rediffusions Facebook de radios et TV.
- Enregistrements terrain (micro téléphone) de locuteurs volontaires.
- **Éthique :** contenu de diffusion publique uniquement ; pas de conversation privée ;
  pas de données personnelles sensibles ; anonymiser les intervenants dans le gold.

Le détail du format de collecte est dans `poc-l0/data/README.md`.

---

## 4. Création du gold set (transcription de référence)

Réalisée par un **locuteur natif** par langue. Règles :

1. Transcription **verbatim** (ce qui est dit, pas ce qui aurait dû être dit).
2. **Marques tonales conservées** (le script produit aussi la version sans tons).
3. Orthographe : convention **latine standard** de la langue (cohérente sur tout le set).
4. Segments code-switchés : transcrire chaque langue dans sa graphie.
5. Bruits non verbaux ignorés (musique, applaudissements).

Format = colonne `reference` du manifeste (voir §6).

---

## 5. Installation

```bash
cd factcheck-mvp/poc-l0

# Environnement Python 3.12
python -m venv .venv
# Windows PowerShell :
.venv\Scripts\Activate.ps1
# Linux/macOS/Git Bash :
# source .venv/bin/activate

pip install -r requirements.txt
```

**Prérequis système :** `ffmpeg` installé et dans le PATH (conversion audio + Whisper).

**Clé API (optionnelle, pour `05_extract_claim.py`) :**
```bash
# PowerShell
$env:ANTHROPIC_API_KEY = "sk-ant-..."
```

> Premier lancement : les modèles se téléchargent depuis Hugging Face (plusieurs Go).
> Tout tourne sur **CPU** ; une note de 15 s se traite en quelques secondes à quelques dizaines de secondes.

---

## 6. Déroulé exécutable

### Étape 1 — Préparer l'audio et le manifeste
Placer l'audio brut dans `data/audio/fon/` et `data/audio/yor/` (et `data/audio/fr/` pour un contrôle).
```bash
python 01_prepare_audio.py --lang fon
python 01_prepare_audio.py --lang yor
```
Sortie : clips 16 kHz mono dans `data/prepared/<lang>/` + un `manifest.csv` avec colonnes
`id, audio_path, lang, reference` (colonne `reference` **vide, à remplir** par le natif).

### Étape 2 — Remplir le gold
Le locuteur natif remplit la colonne `reference` de `data/manifest.csv` (§4).

### Étape 3 — Lancer la chaîne complète
```bash
python run_pipeline.py --manifest data/manifest.csv
```
Enchaîne :
- `02_transcribe.py` → `data/transcripts.csv` (colonne `hypothesis`)
- `03_translate.py` → `data/translations.csv` (colonne `french_hyp`)
- `04_evaluate.py` → `data/report.md` + `data/report.json`

### Étape 4 — (option) extraire le claim français
```bash
python 05_extract_claim.py --input data/translations.csv --output data/claims.csv
```
Produit, pour chaque clip, le **claim en français** que le bot tenterait de matcher.
C'est ce claim que l'humain juge pour la métrique « compréhension end-to-end ».

### Étape 5 — Scorer la compréhension end-to-end (manuel)
Ouvrir `data/claims.csv`, et pour chaque clip noter dans une colonne `e2e_ok` : `1` si le
claim français correspond au sens réel de l'audio, `0` sinon. Le taux = moyenne de `e2e_ok`.

### Étape 6 — Décision
Reporter les chiffres de `report.md` + le taux end-to-end dans `docs/go-no-go-template.md`,
comparer aux seuils du §2, trancher.

---

## 7. Lecture des résultats

`04_evaluate.py` produit, par langue :

```
Langue : fon
  Clips évalués      : 168
  WER (avec tons)    : 0.231   → seuil < 0.25  ✅
  WER (sans tons)    : 0.187
  CER (avec tons)    : 0.094   → seuil < 0.12  ✅
  chrF traduction    : 47.2    → (si gold FR fourni)
```

Le rapport marque automatiquement chaque seuil ASR `✅`/`❌`. Les métriques humaines
(adéquation traduction, end-to-end) se reportent à la main.

---

## 8. Rôles & planning (2-3 semaines)

| Semaine | Tâche | Qui |
|---|---|---|
| S1 | Collecte audio (2-3 h/langue) + install env | Ing. IA + relais terrain |
| S1-S2 | Découpage + transcription gold (30 min/langue) | 2 locuteurs natifs |
| S2 | Run pipeline + WER/CER | Ing. IA |
| S2-S3 | Scoring end-to-end + adéquation traduction | Locuteurs natifs + fact-checker |
| S3 | Rédaction décision Go/No-Go | Chef de projet |

---

## 9. Ce que le POC débloque

- **Go** → on écrit le L3 (voix) avec des KPI **mesurés**, pas promis. Argument massue devant le partenaire.
- **No-Go** → on l'apprend en 3 semaines et pour un coût quasi nul, avant d'avoir engagé le développement du canal vocal. Le MVP pivote proprement vers le texte.

Dans les deux cas, on **sait** au lieu de parier.
