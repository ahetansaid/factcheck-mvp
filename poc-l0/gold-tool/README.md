# Outil de saisie du gold set

Petite application web (PHP + SQLite, sans dépendance) qui permet à un locuteur natif
d'écouter chaque clip et de taper sa transcription, avec les caractères et marques
tonales absents du clavier.

> Outil **interne et jetable** du POC L0. Ce n'est pas la plateforme de fact-checking.

## Pourquoi une base SQLite et pas le CSV directement

Deux locuteurs peuvent saisir en même temps ; écrire concurremment dans `manifest.csv`
le corromprait. Les saisies vont donc dans `gold.sqlite`, puis `merge_gold.py` les
fusionne dans le manifeste **par `id`** (jamais par position).

## Lancer en local

Deux options.

**A. Avec XAMPP** (Apache doit tourner, DocumentRoot = `C:/xampp/htdocs`) :

```
http://localhost/webalizer/poc-l0/gold-tool/
```

**B. Sans Apache**, avec le serveur intégré de PHP :

```bash
cd poc-l0/gold-tool
C:/xampp/php/php.exe -S 0.0.0.0:8080
# puis http://localhost:8080/
```

L'écran d'accueil propose un lien par langue. Donnez à chaque locuteur **son** lien :

- Fon : `.../gold-tool/?lang=fon`
- Yoruba : `.../gold-tool/?lang=yor`

L'outil reprend automatiquement au premier clip non traité.

## Exposer sur Internet (locuteurs à distance)

L'outil est utilisable derrière un tunnel sans modification. **Protégez-le d'abord**
avec un jeton : créez `gold-tool/config.php` (non versionné) :

```php
<?php const GOLD_TOKEN = 'choisissez-un-secret-long';
```

Puis lancez un tunnel, par exemple :

```bash
cloudflared tunnel --url http://localhost:8080
```

Transmettez le lien avec le jeton :
`https://<url-du-tunnel>/?lang=fon&t=choisissez-un-secret-long`

Sans `config.php`, l'accès est libre (usage local uniquement).

## Récupérer les saisies

À faire régulièrement — `gold.sqlite` n'est pas versionné, donc **rien n'est sauvegardé
tant que la fusion n'est pas faite et commitée** :

```bash
cd poc-l0
python merge_gold.py --dry-run   # voir ce qui serait fusionné
python merge_gold.py             # fusionner dans data/manifest.csv
git add data/manifest.csv && git commit -m "gold: nouveau lot"
```

Puis, comme l'ASR et la traduction sont pré-calculés, le WER sort immédiatement :

```bash
python 04_evaluate.py --input data/translations.csv
```

## Consignes données aux locuteurs (rappelées dans l'interface)

1. Transcription **verbatim** : ce qui est dit, pas ce qui aurait dû être dit.
2. **Conserver les marques tonales** (boutons dédiés sous le champ de saisie).
3. Orthographe latine standard de la langue, cohérente sur tout le set.
4. Segment code-switché : chaque langue dans sa graphie.
5. Clip sans parole (musique, jingle, inaudible) → bouton **« Pas de parole »**.
   Ces clips sont exclus du calcul du WER.
