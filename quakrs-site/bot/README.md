# Quakrs Telegram Bot (MVP v1)

Questo modulo implementa un bot Telegram filtrato per:
- earthquakes
- volcanoes
- tsunami
- space weather

## Struttura

- `bot/webhook.php`: endpoint Telegram webhook (comandi base)
- `bot/jobs/bot_ingest.php`: normalizza feed locali `data/*.json`, calcola score, accoda alert
- `bot/jobs/bot_dispatch.php`: invia queue Telegram con retry
- `bot/jobs/bot_daily_digest.php`: genera e accoda daily brief
- `bot/sql/schema.sql`: schema database iniziale

## Variabili ambiente richieste

- `QUAKRS_TELEGRAM_BOT_TOKEN`
- `QUAKRS_DB_BOT_HOST`
- `QUAKRS_DB_BOT_PORT`
- `QUAKRS_DB_BOT_NAME`
- `QUAKRS_DB_BOT_USER`
- `QUAKRS_DB_BOT_PASS`

Opzionali:
- `QUAKRS_TELEGRAM_API_BASE` (default `https://api.telegram.org`)
- `QUAKRS_TELEGRAM_MAX_DAILY_ESSENTIAL` (default `4`)
- `QUAKRS_TELEGRAM_MAX_DAILY_BALANCED` (default `8`)
- `QUAKRS_TELEGRAM_MAX_DAILY_MONITOR` (default `20`)

## Setup rapido

1. Applicare schema SQL:

```bash
mysql -u USER -p DBNAME < /var/www/quakrs-site/bot/sql/schema.sql
```

2. Configurare webhook Telegram:

```bash
curl -sS "https://api.telegram.org/bot${QUAKRS_TELEGRAM_BOT_TOKEN}/setWebhook" \
  -d "url=https://www.quakrs.com/bot/webhook.php"
```

3. Cron consigliati:

```cron
*/2 * * * * cd /var/www/quakrs-site && php bot/jobs/bot_ingest.php >> /var/log/quakrs/bot-ingest.log 2>&1
*/1 * * * * cd /var/www/quakrs-site && php bot/jobs/bot_dispatch.php >> /var/log/quakrs/bot-dispatch.log 2>&1
40 7 * * * cd /var/www/quakrs-site && php bot/jobs/bot_daily_digest.php >> /var/log/quakrs/bot-digest.log 2>&1
```

## Comandi supportati

- `/start`
- `/help`
- `/latest`
- `/earthquakes`
- `/volcanoes`
- `/tsunami`
- `/spaceweather`
- `/dailybrief`
- `/profile`
- `/subscriptions`
- `/mode essential|balanced|monitor`

## Note operative

- Il bot usa i feed locali già prodotti da Quakrs in `data/*.json`.
- Il filtro è score-based con soglie diverse per mode utente.
- La deduplica primaria è su `event_key` canonica per categoria.
