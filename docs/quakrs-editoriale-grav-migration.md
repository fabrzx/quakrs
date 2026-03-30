# Quakrs Editoriale -> Grav (solo `/editoriale`)

Questa guida migra la sezione editoriale di Quakrs su un CMS leggero (Grav), lasciando invariato il resto del sito.

## Obiettivo

- Tenere `quakrs.com` principale sull'app attuale.
- Servire il blog Grav solo sotto `https://quakrs.com/editoriale`.
- Conservare SEO/URL con redirect automatici da vecchie URL `*.php`.

## Perche Grav

- Flat-file: nessun database (niente MySQL/PostgreSQL da gestire).
- PHP + filesystem: adatto a hosting Linux standard.
- Deploy/backup semplici: cartelle e file Markdown.

## Prerequisiti server (Linux)

- Nginx o Apache (esempi sotto per Nginx)
- PHP-FPM 8.1+
- Estensioni PHP richieste da Grav (tipicamente `mbstring`, `xml`, `gd`, `curl`, `zip`, `openssl`, `json`)
- `unzip`, `rsync` opzionale

## Struttura consigliata

```text
/var/www/quakrs-site                 # sito attuale (immutato)
/var/www/quakrs-editoriale-grav      # installazione Grav dedicata
```

## 1) Installare Grav in cartella dedicata

Esempio rapido (adatta al tuo server):

```bash
cd /var/www
curl -L -o grav-admin.zip https://getgrav.org/download/core/grav-admin/latest
unzip grav-admin.zip
mv grav-admin quakrs-editoriale-grav
chown -R www-data:www-data quakrs-editoriale-grav
```

Note:
- Se preferisci no-admin, usa package Grav core.
- Mantieni Grav separato dal codice Quakrs per ridurre rischio di regressioni.

## 2) Esportare articoli Quakrs in formato Grav

Nel repository Quakrs e disponibile lo script:
- [`scripts/export-editoriale-to-grav.php`](/Users/fabrizio/Documents/Quakewatch/quakrs-site/scripts/export-editoriale-to-grav.php)

Esecuzione locale:

```bash
cd /Users/fabrizio/Documents/Quakewatch/quakrs-site
php scripts/export-editoriale-to-grav.php
```

Output generato:
- `tmp/grav-editoriale/user/pages/01.blog/blog.md`
- `tmp/grav-editoriale/user/pages/01.blog/<slug>/item.md`
- `tmp/grav-editoriale/user/pages/_quakrs_editoriale_redirects.csv`

Sync verso server Grav:

```bash
rsync -az --delete \
  /Users/fabrizio/Documents/Quakewatch/quakrs-site/tmp/grav-editoriale/user/pages/ \
  deploy@SERVER:/var/www/quakrs-editoriale-grav/user/pages/
```

## 3) Configurare Grav per subdirectory `/editoriale`

In Grav:

- `system.yaml`

```yaml
custom_base_url: 'https://quakrs.com/editoriale'
absolute_urls: false
```

Se il tuo tema/plugin ha opzioni URL proprie, imposta sempre il base path su `/editoriale`.

## 4) Nginx: instradare solo `/editoriale` a Grav

Nel vhost `quakrs.com`, lascia invariato il blocco del sito attuale e aggiungi:

```nginx
# Redirect legacy Quakrs editorial URLs (.php) -> slug pulito
location ~ ^/editoriale/(.+)\.php$ {
    return 301 /editoriale/$1;
}

# Canonical index.php
location = /editoriale/index.php {
    return 301 /editoriale/;
}

# Grav sotto /editoriale
location ^~ /editoriale {
    alias /var/www/quakrs-editoriale-grav;
    index index.php;
    try_files $uri $uri/ /index.php?$query_string;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_param SCRIPT_FILENAME /var/www/quakrs-editoriale-grav$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

Attenzione:
- Se usi `alias`, verifica con `nginx -t` e un test su file statici + PHP.
- In alternativa puoi usare un sub-vhost interno e reverse proxy su `/editoriale`.

## 5) Cron editoriale

Se vuoi continuare a generare articoli Quakrs e riversarli in Grav:

1. genera bundle Quakrs (`refresh-editorial.sh`)
2. esegui export Grav
3. `rsync` verso `quakrs-editoriale-grav/user/pages`

Esempio job:

```cron
*/30 * * * * cd /var/www/quakrs-site && \
  php -r '$appConfig=require "config/app.php"; require "lib/editorial_engine.php"; qk_editorial_generate($appConfig,120);' && \
  php scripts/export-editoriale-to-grav.php --output=/tmp/grav-export/user/pages && \
  rsync -az --delete /tmp/grav-export/user/pages/ /var/www/quakrs-editoriale-grav/user/pages/ >> /var/log/quakrs/editoriale-grav-sync.log 2>&1
```

## 6) Rollback rapido

In caso di problemi:

1. commenta blocco Nginx `/editoriale` Grav
2. ripristina routing attuale verso `quakrs-site/editoriale`
3. `nginx -t && systemctl reload nginx`

Tempo rollback: pochi minuti.

## Note SEO

- Le vecchie URL `.../editoriale/<slug>.php` sono redirette in 301 a `.../editoriale/<slug>`.
- Mantieni sitemap aggiornata con nuove URL editoriali.
- Mantieni canonical coerente col nuovo path.

## Comando rapido di test locale export

```bash
cd /Users/fabrizio/Documents/Quakewatch/quakrs-site
php -l scripts/export-editoriale-to-grav.php
php scripts/export-editoriale-to-grav.php
```
