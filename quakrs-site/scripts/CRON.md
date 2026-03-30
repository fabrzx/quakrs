# Quakrs Cron Warmup

Use these jobs in production to keep data preloaded and avoid empty/slow blocks.

## Recommended Schedule

1. Fast feed warmup every 3 minutes

```cron
*/3 * * * * QUAKRS_REFRESH_TOKEN=YOUR_REFRESH_TOKEN /bin/sh /var/www/quakrs-site/scripts/refresh-feeds.sh https://www.quakrs.com >> /var/log/quakrs/refresh-feeds.log 2>&1
```

2. Deep warmup every 15 minutes (includes event-history prewarm)

```cron
*/15 * * * * QUAKRS_REFRESH_TOKEN=YOUR_REFRESH_TOKEN HISTORY_POINTS=18 /bin/sh /var/www/quakrs-site/scripts/prewarm-all.sh https://www.quakrs.com >> /var/log/quakrs/prewarm-all.log 2>&1
```

3. Italy statistics refresh every 30 minutes (for instant recap on page load)

```cron
*/30 * * * * QUAKRS_REFRESH_TOKEN=YOUR_REFRESH_TOKEN /bin/sh /var/www/quakrs-site/scripts/refresh-italy-statistics.sh https://www.quakrs.com >> /var/log/quakrs/refresh-italy-statistics.log 2>&1
```

4. Editorial automation refresh every 30 minutes (new + historical articles)

```cron
*/30 * * * * QUAKRS_REFRESH_TOKEN=YOUR_REFRESH_TOKEN /bin/sh /var/www/quakrs-site/scripts/refresh-editorial.sh https://www.quakrs.com >> /var/log/quakrs/refresh-editorial.log 2>&1
```

Optional GPT-assisted writing (same job):

```cron
*/30 * * * * QUAKRS_REFRESH_TOKEN=YOUR_REFRESH_TOKEN QUAKRS_EDITORIAL_USE_GPT=1 QUAKRS_OPENAI_API_KEY=YOUR_OPENAI_KEY QUAKRS_OPENAI_MODEL=gpt-5.4 /bin/sh /var/www/quakrs-site/scripts/refresh-editorial.sh https://www.quakrs.com >> /var/log/quakrs/refresh-editorial.log 2>&1
```

## Notes

- `refresh-feeds.sh` refreshes core/hazard/derived feeds and tectonic cache.
- `prewarm-all.sh` also preloads `event-history` for active seismic zones.
- `refresh-editorial.sh` triggers fully automatic editorial generation and article page publishing.
- Keep both jobs; they serve different latencies and payload weights.

## Cache Garbage Collection (query-based files)

Use this lightweight job to prevent unbounded disk growth from query-shaped cache files:
- `data/event_history_*.json` (older than 24h)
- `data/archive_meta_cache/agg_*.json` (older than 3h)
- `data/archive_meta_cache/facets_*.json` (older than 3h)

For shared hosting / no shell access, use the protected HTTP endpoint:
- `/api/cache-gc.php?force_refresh=1&token=YOUR_REFRESH_TOKEN`

cronjob.org example (every hour):

```text
https://www.quakrs.com/api/cache-gc.php?force_refresh=1&token=YOUR_REFRESH_TOKEN
```

Recommended cronjob.org settings:
- Schedule: every 1 hour
- Request method: GET
- Timeout: at least 30s
- Notify on failure: enabled

Safety notes:
- Endpoint is protected by refresh token.
- No external cleanup parameters are accepted.
- Cleanup scope is limited to strict cache filename prefixes only.
- A non-blocking lock prevents overlapping executions.

If you do have server cron access, you can still use:

```cron
20 * * * * /bin/sh /var/www/quakrs-site/scripts/cache-gc.sh >> /var/log/quakrs/cache-gc.log 2>&1
```

## Historical Archive Backfill (MySQL)

Use this once to build a long-term earthquake archive for `data-archive` filters (period, place, magnitude).

1. Initial historical load (one-shot, resumable)

```bash
cd /var/www/quakrs-site
php scripts/backfill-earthquakes-history.php --start=1900-01-01 --resume >> /var/log/quakrs/backfill-earthquakes.log 2>&1
```

2. Resume after interruptions

```bash
cd /var/www/quakrs-site
php scripts/backfill-earthquakes-history.php --resume >> /var/log/quakrs/backfill-earthquakes.log 2>&1
```

3. Optional maintenance window (nightly catch-up)

```cron
20 2 * * * cd /var/www/quakrs-site && php scripts/backfill-earthquakes-history.php --start=1900-01-01 --resume >> /var/log/quakrs/backfill-earthquakes.log 2>&1
```

Checkpoint file:
- `data/backfill_earthquakes_usgs_checkpoint.json` (override with `--checkpoint=...`)

## Historical Backfill Without SSH (cron-job.org)

If SSH is unavailable on your hosting plan, run backfill in HTTP batches with:
- `/api/backfill-earthquakes.php`
- one USGS page per run (resume/checkpoint automatic)

Example URL (every 1-5 minutes):

```text
https://www.quakrs.com/api/backfill-earthquakes.php?force_refresh=1&token=YOUR_REFRESH_TOKEN&start=1900-01-01
```

Optional query params:
- `end=YYYY-MM-DD` (default today UTC)
- `min_magnitude=2.5`
- `max_window_days=14`
- `max_events_per_window=12000`
- `page_size=300`
- `reset=1` (reset HTTP checkpoint and restart from `start`)

HTTP checkpoint file:
- `data/backfill_earthquakes_http_checkpoint.json`

## Live -> Archive Rollover (DB1 -> DB2)

Use this recurring job to keep `live` slim and automatically move old rows to `archive`.

Phase A (temporary catch-up, then disable):

```cron
*/5 * * * * curl -fsS "https://www.quakrs.com/api/rollover-earthquakes.php?force_refresh=1&token=YOUR_REFRESH_TOKEN&retention_days=90&limit=20000" >/dev/null
```

Run this until `eligible_rows` drops below ~`50000` in dry-run output.

Phase B (steady-state maintenance):

```cron
35 2 * * * curl -fsS "https://www.quakrs.com/api/rollover-earthquakes.php?force_refresh=1&token=YOUR_REFRESH_TOKEN&retention_days=90&limit=5000" >/dev/null
```

Dry run (manual check):

```text
https://www.quakrs.com/api/rollover-earthquakes.php?force_refresh=1&token=YOUR_REFRESH_TOKEN&retention_days=90&limit=5000&dry_run=1
```
