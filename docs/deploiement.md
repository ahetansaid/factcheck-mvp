# Déploiement en production — Vérifon

Deux applications à déployer, plus une base PostgreSQL.

```
web/   Next.js   → Vercel (site public SSR)
core/  Laravel   → hébergement PHP 8.2+ / VPS (API + administration)
       PostgreSQL 16+ (avec pgvector pour le L2)
```

> Note CORS : le front n'appelle jamais l'API depuis le navigateur — il passe par
> ses propres routes serveur (`/api/ask`, `/api/submissions`) qui relaient vers Laravel.
> Aucune configuration CORS n'est donc nécessaire tant qu'on garde ce proxy.

---

## 1. Base de données PostgreSQL

Chez un hébergeur managé (Neon, Supabase, Render…) ou sur le VPS :

```sql
CREATE ROLE verifon LOGIN PASSWORD 'un-mot-de-passe-fort';
CREATE DATABASE verifon OWNER verifon;
-- pour le L2 (recherche sémantique), quand on y arrivera :
-- CREATE EXTENSION IF NOT EXISTS vector;
```

---

## 2. Backend Laravel (`core/`)

Sur un hébergement PHP 8.2+ (Nginx + PHP-FPM) ou un PaaS.

```bash
cd core
composer install --no-dev --optimize-autoloader

cp .env.example .env
# Éditer .env :
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://api.votre-domaine
#   FRONT_URL=https://votre-domaine
#   Décommenter le bloc PostgreSQL (DB_CONNECTION=pgsql, DB_*)
php artisan key:generate

php artisan migrate --force            # + --seed au premier déploiement (données de démo)
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

- Racine web du serveur → `core/public`.
- Administration : `https://api.votre-domaine/admin`.
- **Créer un compte rédacteur** : `php artisan tinker` puis
  `App\Models\User::create(['name'=>'…','email'=>'…','password'=>bcrypt('…')]);`
  (ou changer le mot de passe du compte de démo `admin@verifon.bj`).

### Checklist sécurité
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Mot de passe rédacteur fort (pas celui de démo)
- [ ] HTTPS (certificat TLS)
- [ ] Les endpoints publics d'écriture sont déjà limités (`throttle:20,1`)
- [ ] Sauvegardes régulières de PostgreSQL

---

## 3. Frontend Next.js (`web/`) sur Vercel

1. **vercel.com → Add New → Project** → importer le dépôt.
2. **Root Directory = `web`** (sinon Vercel tente de builder tout le dépôt).
3. **Framework** : Next.js (détecté automatiquement).
4. **Environment Variables** (voir `web/.env.production.example`) :
   - `API_URL` = `https://api.votre-domaine/api`
   - `NEXT_PUBLIC_SITE_URL` = `https://votre-domaine`
5. **Deploy**. Chaque `git push` redéploie automatiquement.

---

## 4. Après déploiement — vérifier
- Site public accessible, vérifications listées.
- Une page de vérification contient bien le `<script type="application/ld+json">`
  (tester sur https://search.google.com/test/rich-results).
- Le widget « Vérifier une info » répond.
- `/sitemap.xml` et `/rss.xml` répondent.
- La CI GitHub (`.github/workflows/ci.yml`) est verte.

---

## 5. Étapes ultérieures (non couvertes ici)
- **L2 v2** : renseigner `ANTHROPIC_API_KEY` dans `core/.env` (reformulation Claude).
- **L2 v3** : activer l'extension `vector`, worker d'embeddings (à partir de `poc-l0/`).
- **L3** : brancher le pipeline voix du POC via un service FastAPI.
