# TexpertDeals — cPanel Deployment Guide

This deploys the Laravel app to **cPanel shared hosting** and imports your local database.

> Requirements on the host: **PHP 8.2+**, MySQL/MariaDB, and the extensions
> `pdo_mysql, mbstring, openssl, fileinfo, curl, gd, tokenizer, ctype, json`.
> In cPanel use **"Select PHP Version"** to set 8.2/8.3 and enable those extensions.

---

## 1. Prepare the upload package (on your PC)

These are already done in this project — just confirm:

```bash
# (optional, slims vendor for production — only if Composer is installed locally)
composer install --no-dev --optimize-autoloader

# build front-end assets (creates public/build)
npm run build
```

Then **ZIP the whole project folder** `texperdeals-custom` EXCEPT these (not needed live):
- `node_modules/`  ← never upload (huge, dev only)
- `.git/`, `tests/`
- `texperdeals_db.sql` will be uploaded separately to phpMyAdmin (not into the web folder)

✅ DO include: `vendor/`, `public/` (with `public/build` and `public/uploads`), `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `artisan`, `composer.json`.

---

## 2. Create the database (cPanel → MySQL® Databases)

1. **Create a database** e.g. `cpaneluser_texperdeals`
2. **Create a user** + password, **Add user to the database** with **ALL PRIVILEGES**
3. Open **phpMyAdmin** → select the new database → **Import** → choose `texperdeals_db.sql` → Go
   - (This loads all products, categories, brands, orders, settings, and the admin login.)

---

## 3. Upload the files

### Option A — domain points to `/public` (best; use if your host lets you set the document root)
1. Upload the ZIP to your home dir (e.g. `/home/cpaneluser/texperdeals`) and **Extract**.
2. In cPanel → **Domains**, set the domain's **Document Root** to `/home/cpaneluser/texperdeals/public`.

### Option B — classic `public_html` (use if you canNOT change the document root)
1. Extract the app to a folder OUTSIDE the web root, e.g. `/home/cpaneluser/texperdeals_app`.
2. Move EVERYTHING from `texperdeals_app/public/` INTO `public_html/`
   (so `public_html/index.php`, `public_html/build/`, `public_html/uploads/`, `public_html/.htaccess` exist).
3. Edit `public_html/index.php` — update the two require paths to point to the app folder:
   ```php
   require __DIR__.'/../texperdeals_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../texperdeals_app/bootstrap/app.php';
   ```

---

## 4. Configure `.env`

1. Copy `.env.production` → `.env` (in the app root).
2. Edit it:
   - `APP_URL=https://your-domain.com`
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` = the values from step 2
   - Keep the existing `APP_KEY` (already filled). If it's blank, run `php artisan key:generate`.

---

## 5. Permissions

Set these folders to **775** (cPanel File Manager → select → Permissions), recursively:
- `storage/`
- `bootstrap/cache/`
- `public/uploads/`  ← so admin image/logo uploads work

---

## 6. Finalize (cPanel → Terminal, if available)

```bash
cd ~/texperdeals        # or your app folder
php artisan config:clear && php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force   # only if you did NOT import the .sql; skip if imported
```

> No Terminal/SSH? Skip the cache commands — the app runs without them. If `migrate`
> can't be run, just make sure you imported `texperdeals_db.sql` (step 2).

---

## 7. Verify

- Visit `https://your-domain.com` → storefront loads
- `https://your-domain.com/admin/login` → **admin@texperdeals.com / password**
  ⚠️ **Change this password immediately** after first login (or via phpMyAdmin / a tinker command).

---

## Notes
- **Node.js is NOT needed on the server** — assets are pre-built in `public/build`.
- Uploaded images live in `public/uploads/` and are included in the ZIP.
- For large CSV imports on shared hosting, increase `max_execution_time` / `memory_limit`
  in cPanel **Select PHP Version → Options**, or import in smaller batches.
- HTTPS: enable the free **AutoSSL / Let's Encrypt** in cPanel.
