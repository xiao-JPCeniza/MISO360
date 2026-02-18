# Fixing Apache "Forbidden" on miso360.manolofortich.gov.ph

When you see **"Forbidden - You don't have permission to access this resource"** from Apache (not from Laravel), the server is blocking access before Laravel runs. Check the following on the **server** (Ubuntu/Apache).

## 1. DocumentRoot must be the `public` folder

Laravel must be served from the **`public`** directory, not the project root.

- **Wrong:** `DocumentRoot /var/www/miso360`
- **Correct:** `DocumentRoot /var/www/miso360/public`

Edit your site’s Apache config (e.g. under `/etc/apache2/sites-available/`), set:

```apache
DocumentRoot /path/to/your/project/public
<Directory /path/to/your/project/public>
    AllowOverride All
    Require all granted
</Directory>
```

Then reload Apache:

```bash
sudo systemctl reload apache2
```

## 2. AllowOverride and Require

- **AllowOverride All** – so `public/.htaccess` is read (required for Laravel’s routing).
- **Require all granted** – so Apache allows access to that directory.

Without these, you can get "Forbidden" or broken routes.

## 3. Permissions and ownership

The web server user (often `www-data`) must be able to read the app and write to storage/bootstrap/cache.

```bash
# From the project root (e.g. /var/www/miso360)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

If the app lives under your user’s home or another user, you can use that user for `chown` and give the web server group read/execute on the project and the above permissions on `storage` and `bootstrap/cache`.

## 4. mod_rewrite enabled

Laravel needs `mod_rewrite`:

```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

## 5. Check Apache error log

To see the exact reason for "Forbidden":

```bash
sudo tail -50 /var/log/apache2/error.log
```

Common messages:

- **"DocumentRoot does not exist"** – path in config is wrong.
- **"Options not allowed here"** – adjust `Options` in the `<Directory>` block (see example config).
- **"Permission denied"** – fix ownership/permissions as in step 3.

## 6. If you use the example config

See `apache-virtualhost-example.conf` in this folder. Copy it to `/etc/apache2/sites-available/`, change:

- `DocumentRoot` and `<Directory>` path to your real path (e.g. `/var/www/miso360/public`).
- SSL directives if you use HTTPS.

Then enable the site and reload Apache as in step 1.

After these changes, reload or restart Apache and test again; the "Forbidden" from Apache should be resolved and Laravel can run.
