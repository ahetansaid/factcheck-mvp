# Plateforme Vérifon — Socle L1 (AI-native)

Socle de la plateforme de fact-checking : back-office de rédaction, API publique,
site public en SSR avec balisage **ClaimReview** lisible par Google et les IA.

```
web/   Next.js 15  — site public SSR (accueil, vérifications, personnalités, RSS, sitemap)
core/  Laravel 12  — API publique + administration Filament + génération ClaimReview
       base        — SQLite en dev · PostgreSQL 18 en cible (pgvector prêt pour le L2)
```

## Prérequis
- PHP 8.2+ et Composer · Node 20+ · (PostgreSQL 18 pour la cible)

## Lancer en développement

**1. API + administration (Laravel)**
```bash
cd core
composer install
cp .env.example .env          # déjà fait ; adapter si besoin
php artisan key:generate      # si nouvelle install
php artisan migrate --seed    # crée le schéma + données de démo
php artisan serve --port=8000
```
- Administration : http://localhost:8000/admin
- Connexion de démo : **admin@verifon.bj** / **verifon2026**
- API : http://localhost:8000/api/verifications

**2. Site public (Next.js)**
```bash
cd web
npm install
cp .env.example .env.local     # API_URL pointe sur le Laravel
npm run dev                    # http://localhost:3000
```

## Ce que le socle fait
- **Rédaction** (Filament) : créer/publier des vérifications (affirmation, verdict
  Vrai/Faux/Trompeur/Non vérifié, résumé, corps, sources, personnalité liée).
- **API publique** : `/api/verifications`, `/api/verifications/{slug}` (avec ClaimReview),
  `/api/personalities`, `/api/personalities/{slug}`.
- **Site public SSR** : accueil, page de vérification (JSON-LD ClaimReview injecté),
  annuaire des personnalités avec compteurs Vrai/Faux.
- **SEO / AI-native** : `/sitemap.xml`, `/rss.xml`, balisage `ClaimReview` + `Article`
  (validable au Google Rich Results Test).

## Bascule SQLite → PostgreSQL (cible)

L1 tourne sur SQLite en dev. Pour passer à PostgreSQL (nécessaire au L2 / pgvector) :

1. Créer la base et le rôle (une fois) :
   ```powershell
   & "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -c "CREATE ROLE verifon LOGIN PASSWORD 'verifon_dev';"
   & "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres -c "CREATE DATABASE verifon OWNER verifon;"
   ```
2. Dans `core/.env`, commenter le bloc SQLite et réactiver le bloc PostgreSQL.
3. `php artisan migrate:fresh --seed`

Les migrations sont portables : aucun code à modifier.

## Prochaines étapes (hors L1)
- **L2** : bot RAG (embeddings pgvector, réponses citées, garde-fou).
- **L3** : canal vocal — branche le pipeline du POC (`poc-l0/`) via un worker FastAPI.
- **L4** : détecteur de claims (veille multi-médias).
