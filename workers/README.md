# Worker d'embeddings

Micro-service FastAPI qui vectorise textes et requêtes pour la **recherche
sémantique** du bot (L2 v3). Précurseur du service IA du README (le canal voix
du L3 viendra s'y ajouter, à partir de `poc-l0/`).

- Modèle : `intfloat/multilingual-e5-small` (multilingue, 384 dim, CPU).
- Vecteurs **normalisés** → similarité cosinus = produit scalaire (calculé en PHP
  côté Laravel ; **pgvector n'est pas requis à l'échelle MVP**).

## Installation (une fois)

Réutilise le venv de `poc-l0` (torch + transformers déjà présents) :

```bash
../poc-l0/.venv/Scripts/python.exe -m pip install -r requirements.txt
```

## Lancer

```bash
cd workers
../poc-l0/.venv/Scripts/python.exe -m uvicorn embed_service:app --host 127.0.0.1 --port 8100
```

- `GET  /health` → état + modèle
- `POST /embed`  → `{ "texts": [...], "kind": "query" | "passage" }` → `{ "vectors": [...] }`

## Côté Laravel

```bash
cd ../core
php artisan embeddings:build        # vectorise les vérifications publiées
php artisan embeddings:build --all  # recalcule tout
```

`EMBED_WORKER_URL` (défaut `http://127.0.0.1:8100`) et `EMBED_THRESHOLD`
(défaut `0.84`) se règlent dans `core/.env`.

Si le worker est **arrêté**, la recherche du bot **retombe automatiquement** sur
l'appariement par mots-clés : rien ne casse.
