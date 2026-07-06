# Données du POC L0 — guide de collecte

## Où déposer l'audio brut

```
data/audio/fon/   ← extraits en fon   (.wav .mp3 .m4a .ogg .opus .flac ...)
data/audio/yor/   ← extraits en yoruba
data/audio/fr/    ← (facultatif) extraits en français pour contrôle
```

Le format d'entrée n'a pas d'importance : `01_prepare_audio.py` convertit tout en
WAV 16 kHz mono via ffmpeg.

## Volumes cibles

| | Fon | Yoruba |
|---|---|---|
| Audio brut | 2–3 h | 2–3 h |
| Gold transcrit | 30 min | 30 min |
| Clips (5–15 s) | ~120–200 | ~120–200 |

## Découpage en clips

Découpez l'audio en **segments courts de 5 à 15 s**, un segment = une prise de parole
cohérente (idéalement une phrase / une affirmation). Un fichier = un clip.
Outils possibles : Audacity, ffmpeg (`-ss`/`-t`), ou un VAD (Silero) si vous automatisez.

## Diversité obligatoire (sinon le WER ment)

Le gold set **doit** mélanger :
- studio radio propre,
- radio avec fond musical / jingle,
- qualité téléphone / appel à l'antenne,
- parole spontanée / débit rapide,
- **code-switching** fon↔français / yoruba↔français.

## Sources autorisées

- Streams IP des FM béninoises (celles qui en ont un).
- Lives / rediffusions Facebook de radios et TV.
- Enregistrements terrain (micro téléphone) de locuteurs volontaires.

**Éthique :** diffusion publique uniquement · pas de conversation privée · pas de donnée
personnelle sensible · anonymiser les intervenants dans le gold.

## Remplir le gold set (`manifest.csv`)

Après `01_prepare_audio.py`, le fichier `data/manifest.csv` contient une ligne par clip :

| colonne | qui remplit | contenu |
|---|---|---|
| `id` | auto | identifiant du clip |
| `audio_path` | auto | chemin du WAV préparé |
| `lang` | auto | `fon` / `yor` / `fr` |
| `reference` | **locuteur natif** | transcription **verbatim, avec marques tonales** |
| `french_reference` | facultatif | traduction française de référence (active le score chrF) |

### Règles de transcription
1. Verbatim (ce qui est dit, pas ce qui aurait dû être dit).
2. Marques tonales conservées (le script produit aussi la version sans tons).
3. Orthographe latine standard, cohérente sur tout le set.
4. Segments code-switchés : chaque langue dans sa graphie.
5. Bruits non verbaux ignorés.

## Fichiers générés (ne pas versionner)

`prepared/`, `transcripts.csv`, `translations.csv`, `claims.csv`,
`report.md`, `report.json` sont produits par les scripts.
