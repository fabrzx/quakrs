# Quakrs MariaDB Growth Plan (No-Downtime)

## Current state (2026-03-28)
- Production DB engine: MariaDB 10.11 on VPS `167.235.248.54`
- Active databases: `quakrs_live`, `quakrs_archive`, `quakrs_ingest`, `quakrs_stats`
- Active tables:
  - `quakrs_live.earthquake_events`
  - `quakrs_archive.earthquake_events`
  - `quakrs_ingest.earthquake_events_raw`
  - `quakrs_stats.earthquake_daily_stats`
- Backfill process: running in resume mode from checkpoint

## Goal
Move to a single archive-grade events table with monthly partitions and keep live queries fast, without service interruption.

## Phase 1 (already done)
1. Separate DBs by role (`live/archive/ingest/stats`)
2. Add runtime-compatible indexes
3. Enable slow query log + baseline tuning

## Phase 2 (next)
### 2.1 Add new canonical table
Create `quakrs_archive.earthquake_events_v2` with:
- surrogate key `id BIGINT AUTO_INCREMENT`
- unique business key `(event_key)`
- partition key `event_time_ts`
- monthly `RANGE` partitions on `event_time_ts`

Important MariaDB rule:
- every UNIQUE key must include partition key.
- implement uniqueness as `UNIQUE(event_key, event_time_ts)` and keep a non-unique `INDEX(event_key)` for fast lookups.

### 2.2 Dual-write window
For a transition period:
1. keep current writes on `earthquake_events`
2. mirror writes to `earthquake_events_v2` (app-level dual write)
3. monitor row drift every 5 minutes

### 2.3 Read switch
1. switch read endpoints to v2 in this order:
   - archive endpoints
   - analytics/statistics endpoints
   - rollover helpers
2. keep fallback flag to old table for instant rollback

### 2.4 Cutover
1. stop dual write
2. rename tables:
   - `earthquake_events` -> `earthquake_events_legacy`
   - `earthquake_events_v2` -> `earthquake_events`
3. keep legacy table read-only for 7-14 days

### 2.5 Rollback
- if query errors spike, revert read flag to legacy table and re-enable old path.

## Phase 3 (scaling)
1. Add monthly partition maintenance job (create next 3 months in advance)
2. Add archive retention policy (optional, if needed)
3. Add query digest review from slow log weekly
4. Add replica for read-heavy analytics (optional)

## Operational checks
- Backfill progress: `/var/log/quakrs/backfill-earthquakes.log`
- DB credentials file: `/etc/quakrs-db-credentials.env`
- MariaDB tuning file: `/etc/mysql/mariadb.conf.d/60-quakrs-tuning.cnf`
- Slow log file: `/var/log/mysql/mariadb-slow.log`

## Suggested immediate next actions
1. Let backfill reach at least 1970 before enabling heavy archive pages
2. Review slow log after first 24h of real traffic
3. Then implement Phase 2.1 with a dedicated migration script and controlled dual-write
