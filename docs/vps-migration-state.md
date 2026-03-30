# VPS Migration State

Updated: 2026-03-28

## Runtime target
- VPS: `167.235.248.54`
- OS: Ubuntu 24.04
- Web: Nginx + PHP-FPM 8.3
- SSL: Let's Encrypt active for `quakrs.com` and `www.quakrs.com`
- App path: `/var/www/quakrs-site`

## Security baseline
- SSH hardening:
  - `PermitRootLogin no`
  - `PasswordAuthentication no`
- User: `deploy` with sudo
- Firewall: UFW enabled (`OpenSSH`, `Nginx Full`)
- Fail2ban: active for sshd

## Database (MariaDB on VPS)
- Engine: MariaDB 10.11
- Databases:
  - `quakrs_live`
  - `quakrs_archive`
  - `quakrs_ingest`
  - `quakrs_stats`
- App DB host: `127.0.0.1`
- Credentials file: `/etc/quakrs-db-credentials.env`

## Current archive migration mode
- Primary archive table in app config: `earthquake_events_v2`
- Shadow table: `earthquake_events`
- Dual-write enabled for archive role (primary + shadow)
- Backfill in progress with resume and periodic auto-restart

## App config status
- Active config file: `/var/www/quakrs-site/config/app.local.php`
- Archive role includes:
  - `table = earthquake_events_v2`
  - `shadow_table = earthquake_events`

## Cron jobs (deploy user)
- Feed refresh: every 3 minutes
- Deep prewarm: every 15 minutes
- Italy stats refresh: every 30 minutes
- Cache GC: hourly
- Backfill resume nightly: `20 2 * * *`
- Backfill resume periodic with lock: every 20 minutes

## Backup and monitoring
- DB backup script: `/usr/local/bin/quakrs-db-backup.sh`
- Backup cron (root): `15 3 * * *`
- Backup dir: `/var/backups/quakrs`
- Monitor script: `/usr/local/bin/quakrs-monitor.sh`
- Monitor cron (root): every 5 minutes
- Monitor log: `/var/log/quakrs/monitor.log`

## Performance/system
- MariaDB tuning file: `/etc/mysql/mariadb.conf.d/60-quakrs-tuning.cnf`
- Slow query log: `/var/log/mysql/mariadb-slow.log`
- Swap: `/swapfile` 2GB
- Kernel updated and reboot performed

## Health status
- API health currently reachable and healthy
- DB roles (`live/archive/ingest/stats`) available

## Repo files changed in this migration window
- `quakrs-site/api/earthquakes-archive-lib.php`
- `quakrs-site/api/earthquakes.php`
- `quakrs-site/api/rollover-earthquakes.php`
- `quakrs-site/api/backfill-earthquakes.php`
- `quakrs-site/scripts/backfill-earthquakes-history.php`
- `quakrs-site/scripts/deploy-excludes.txt`
- `quakrs-site/scripts/deploy.env`
- `docs/mariadb-growth-plan.md`

## Important operational note
- Keep `scripts/deploy-excludes.txt` entries for backfill checkpoints/locks to avoid resetting import progress during deploy.
