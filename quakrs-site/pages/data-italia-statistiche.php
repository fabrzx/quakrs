<?php
declare(strict_types=1);

$pageTitle = 'Quakrs.com - Statistiche Italia';
$pageDescription = 'Statistiche sismiche Italia: andamento mensile, annuale e regioni più attive.';
$currentPage = 'data-italia-statistiche';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/topbar.php';
?>
<style>
  .itstats-chart-card {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    min-height: 28rem;
  }

  .itstats-chart-card.is-compact {
    min-height: 21.5rem;
  }

  .itstats-bars {
    --bar-thickness: clamp(16px, 2.1vw, 24px);
    min-height: 23.8rem;
    display: grid;
    grid-template-columns: repeat(var(--bar-count), var(--bar-thickness));
    justify-content: space-between;
    gap: 0;
  }

  .itstats-bars .bar-col {
    width: var(--bar-thickness);
  }

  .itstats-bars .bar-col-value {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--text-1);
  }

  .itstats-bars .bar-col-label {
    font-size: 0.62rem;
    color: var(--muted);
  }

  .itstats-bars-yearly {
    --bar-thickness: clamp(22px, 3.6vw, 40px);
  }

  .itstats-top-charts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.72rem;
  }

  .itstats-source {
    margin-top: 0.5rem;
  }

  .itstats-rank {
    margin-top: 0.24rem;
    display: grid;
    gap: 0.46rem;
  }

  .itstats-rank-row {
    display: grid;
    grid-template-columns: minmax(90px, 0.4fr) minmax(120px, 1fr) auto;
    gap: 0.56rem;
    align-items: center;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.46rem 0.56rem;
    background: color-mix(in srgb, var(--surface-2) 88%, #000000);
  }

  .itstats-rank-region {
    color: var(--text-1);
    font-weight: 650;
    font-size: 0.82rem;
  }

  .itstats-rank-bar {
    height: 9px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: color-mix(in srgb, var(--surface-2) 78%, transparent);
    overflow: hidden;
  }

  .itstats-rank-fill {
    height: 100%;
    min-width: 6px;
    background: linear-gradient(90deg, #20e0ff 0%, #b7ff00 100%);
  }

  .itstats-rank-meta {
    color: var(--text-2);
    font-size: 0.75rem;
    text-align: right;
    font-variant-numeric: tabular-nums;
  }

  .itstats-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }

  .itstats-rank-scope {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  .itstats-rank-scope select {
    border-radius: 8px;
    border: 1px solid color-mix(in srgb, var(--line) 66%, transparent);
    background: color-mix(in srgb, var(--surface-2) 84%, transparent);
    color: var(--text-1);
    padding: 0.3rem 0.42rem;
    font: 700 0.7rem/1.1 "Space Grotesk", sans-serif;
  }

  @media (max-width: 980px) {
    .itstats-top-charts {
      grid-template-columns: 1fr;
    }

    .itstats-rank-row {
      grid-template-columns: minmax(0, 1fr);
      gap: 0.34rem;
    }

    .itstats-rank-meta {
      text-align: left;
    }

    .itstats-bars {
      --bar-thickness: clamp(12px, 2.5vw, 18px);
    }

  }
</style>

<main class="hero compact-hero">
  <div>
    <p class="eyebrow">Data / Italia</p>
    <h1>Statistiche sismiche Italia</h1>
    <p class="sub">Andamento per mese, andamento per anno e ranking delle regioni più attive dal dataset disponibile.</p>
  </div>
</main>

<section class="panel panel-kpi">
  <article class="card kpi-card">
    <p class="kpi-label">Mese corrente</p>
    <p id="itstats-kpi-month" class="kpi-value">--</p>
    <p id="itstats-kpi-month-note" class="kpi-note">Caricamento...</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Trend vs mese precedente</p>
    <p id="itstats-kpi-delta" class="kpi-value">--</p>
    <p class="kpi-note">Differenza assoluta eventi</p>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Media giornaliera</p>
    <p id="itstats-kpi-dailyavg" class="kpi-value">--</p>
    <p id="itstats-kpi-dailyavg-note" class="kpi-note">Caricamento...</p>
    <label class="itstats-rank-scope" style="margin-top:0.34rem;">
      <span class="sr-only">Tipo media giornaliera</span>
      <select id="itstats-kpi-dailyavg-scope">
        <option value="year" selected>Media annuale</option>
        <option value="month">Media mensile</option>
      </select>
    </label>
  </article>
  <article class="card kpi-card">
    <p class="kpi-label">Regione più sismica</p>
    <p id="itstats-kpi-region" class="kpi-value">--</p>
    <p id="itstats-kpi-region-note" class="kpi-note">Quota sul totale: --</p>
  </article>
</section>

<section class="panel itstats-top-charts">
  <article class="card itstats-chart-card is-compact">
    <div class="feed-head">
      <h3>Terremoti per mese</h3>
      <p class="feed-meta">Ultimi 12 mesi</p>
    </div>
    <div id="itstats-chart-monthly" class="bars-vertical itstats-bars"></div>
  </article>
  <article class="card itstats-chart-card is-compact">
    <div class="feed-head">
      <h3>Terremoti per anno</h3>
      <p class="feed-meta">Ultimi 10 anni</p>
    </div>
    <div id="itstats-chart-yearly" class="bars-vertical itstats-bars itstats-bars-yearly"></div>
  </article>
</section>

<section class="panel">
  <article class="card itstats-chart-card">
    <div class="feed-head">
      <h3>Regioni più sismiche</h3>
      <label class="itstats-rank-scope">
        <span class="sr-only">Periodo ranking regioni</span>
        <select id="itstats-rank-scope">
          <option value="month_current">Mese corrente</option>
          <option value="year_current">Anno corrente</option>
          <option value="all_time">Storico dataset</option>
        </select>
      </label>
    </div>
    <p id="itstats-rank-meta" class="feed-meta">Top 12 per numero eventi (mese corrente)</p>
    <div id="itstats-regions" class="itstats-rank">
      <div class="event-item">Caricamento classifica regioni...</div>
    </div>
  </article>
</section>

<section class="panel page-grid">
  <article class="card page-card">
    <h3>Lettura rapida</h3>
    <p id="itstats-story" class="insight-lead">Preparazione sintesi statistica...</p>
  </article>
  <article class="card page-card">
    <h3>Origine dati</h3>
    <p id="itstats-source" class="insight-lead">Provider: --</p>
  </article>
  <article class="card page-card">
    <h3>Azioni</h3>
    <div class="itstats-actions">
      <a class="btn btn-primary" href="<?= htmlspecialchars(qk_localized_url('/data-italia.php'), ENT_QUOTES, 'UTF-8'); ?>">Torna a Data Italia</a>
      <button id="itstats-refresh" class="btn btn-ghost" type="button">Aggiorna statistiche</button>
    </div>
  </article>
</section>

<script>
  (() => {
    const kpiMonth = document.querySelector('#itstats-kpi-month');
    const kpiMonthNote = document.querySelector('#itstats-kpi-month-note');
    const kpiDelta = document.querySelector('#itstats-kpi-delta');
    const kpiDailyAvg = document.querySelector('#itstats-kpi-dailyavg');
    const kpiDailyAvgNote = document.querySelector('#itstats-kpi-dailyavg-note');
    const kpiDailyAvgScope = document.querySelector('#itstats-kpi-dailyavg-scope');
    const kpiRegion = document.querySelector('#itstats-kpi-region');
    const kpiRegionNote = document.querySelector('#itstats-kpi-region-note');
    const chartMonthly = document.querySelector('#itstats-chart-monthly');
    const chartYearly = document.querySelector('#itstats-chart-yearly');
    const regionsList = document.querySelector('#itstats-regions');
    const rankScopeSelect = document.querySelector('#itstats-rank-scope');
    const rankMeta = document.querySelector('#itstats-rank-meta');
    const story = document.querySelector('#itstats-story');
    const source = document.querySelector('#itstats-source');
    const refreshButton = document.querySelector('#itstats-refresh');
    const dailyAvgScopeStorageKey = 'itstats_dailyavg_scope';
    let rankScopeRows = {
      month_current: [],
      year_current: [],
      all_time: [],
    };
    let rankScopeLabels = {
      month_current: 'mese corrente',
      year_current: 'anno corrente',
      all_time: 'storico dataset',
    };
    let dailyAvgMetrics = {
      monthAvg: null,
      monthCount: 0,
      monthDays: 1,
      monthLabel: '',
      yearAvg: null,
      yearCount: 0,
      yearDays: 1,
      yearLabel: '',
    };

    const formatNum = (value) => {
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      return num.toLocaleString('it-IT');
    };

    const formatMonthLabelShort = (value) => {
      if (typeof value !== 'string' || value.length !== 7) return value || '--';
      const [yearRaw, monthRaw] = value.split('-');
      const year = Number(yearRaw);
      const month = Number(monthRaw);
      if (!Number.isFinite(year) || !Number.isFinite(month) || month < 1 || month > 12) return value;
      const date = new Date(Date.UTC(year, month - 1, 1));
      return date.toLocaleDateString('it-IT', { month: 'short', timeZone: 'UTC' });
    };

    const formatMonthLabelLong = (value) => {
      if (typeof value !== 'string' || value.length !== 7) return value || '--';
      const [yearRaw, monthRaw] = value.split('-');
      const year = Number(yearRaw);
      const month = Number(monthRaw);
      if (!Number.isFinite(year) || !Number.isFinite(month) || month < 1 || month > 12) return value;
      const date = new Date(Date.UTC(year, month - 1, 1));
      return date.toLocaleDateString('it-IT', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    };

    const formatUtcDate = (value) => {
      if (typeof value !== 'string' || value === '') return '--';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return '--';
      return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    const formatSigned = (value) => {
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      if (num > 0) return `+${formatNum(num)}`;
      return formatNum(num);
    };

    const renderDailyAverageCard = () => {
      const scope = String(kpiDailyAvgScope?.value || 'month');
      const isYear = scope === 'year';
      const avg = isYear ? dailyAvgMetrics.yearAvg : dailyAvgMetrics.monthAvg;
      const count = isYear ? dailyAvgMetrics.yearCount : dailyAvgMetrics.monthCount;
      const days = isYear ? dailyAvgMetrics.yearDays : dailyAvgMetrics.monthDays;
      const label = isYear ? dailyAvgMetrics.yearLabel : dailyAvgMetrics.monthLabel;
      if (kpiDailyAvg) {
        kpiDailyAvg.textContent = Number.isFinite(Number(avg))
          ? `${Number(avg).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}/g`
          : '--';
      }
      if (kpiDailyAvgNote) {
        const periodLabel = isYear ? `anno ${label}` : `mese ${label}`;
        kpiDailyAvgNote.textContent = Number.isFinite(Number(avg))
          ? `${formatNum(count)} eventi / ${formatNum(days)} giorni (${periodLabel})`
          : 'Media non disponibile';
      }
    };

    const buildLast12Months = (rows) => {
      const map = new Map();
      (Array.isArray(rows) ? rows : []).forEach((row) => {
        const month = String(row?.month || '');
        if (/^\d{4}-\d{2}$/.test(month)) {
          map.set(month, Number(row?.count || 0));
        }
      });
      const out = [];
      const now = new Date();
      const base = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), 1));
      for (let i = 11; i >= 0; i -= 1) {
        const d = new Date(Date.UTC(base.getUTCFullYear(), base.getUTCMonth() - i, 1));
        const key = `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`;
        out.push({ month: key, count: Number(map.get(key) || 0) });
      }
      return out;
    };

    const buildLast10Years = (rows) => {
      const map = new Map();
      (Array.isArray(rows) ? rows : []).forEach((row) => {
        const year = String(row?.year || '');
        if (/^\d{4}$/.test(year)) {
          map.set(year, Number(row?.count || 0));
        }
      });
      const out = [];
      const nowYear = new Date().getUTCFullYear();
      for (let year = nowYear - 9; year <= nowYear; year += 1) {
        const key = String(year);
        out.push({ year: key, count: Number(map.get(key) || 0) });
      }
      return out;
    };

    const renderVerticalChart = (container, rows, options = {}) => {
      if (!container) return;
      const maxValue = rows.reduce((max, row) => Math.max(max, Number(row.value || 0)), 0) || 1;
      const forcedMax = Number(options.maxValue || 0);
      const cap = forcedMax > 0 ? Math.max(maxValue, forcedMax) : maxValue;
      container.style.setProperty('--bar-count', String(rows.length));
      container.innerHTML = rows.map((row) => {
        const value = Number(row.value || 0);
        const percent = Math.max(4, Math.round((Math.max(0, value) / cap) * 100));
        return `
          <div class="bar-col">
            <div class="bar-col-value">${row.display || ''}</div>
            <div class="bar-col-track">
              <div class="bar-col-fill" style="height:${percent}%;background:${row.color || '#5de4c7'}"></div>
            </div>
            <div class="bar-col-label">${row.label || ''}</div>
          </div>
        `;
      }).join('');
    };

    const renderRegions = (rows, scopeLabel = 'mese corrente') => {
      if (!regionsList) return;
      if (rankMeta) rankMeta.textContent = `Top 12 per numero eventi (${scopeLabel})`;
      if (!Array.isArray(rows) || rows.length === 0) {
        regionsList.innerHTML = '<div class="event-item">Nessuna regione disponibile nel dataset.</div>';
        return;
      }
      const top = rows.slice(0, 12);
      const maxCount = top.reduce((max, row) => Math.max(max, Number(row.count || 0)), 0) || 1;
      regionsList.innerHTML = top.map((row) => {
        const count = Number(row.count || 0);
        const share = Number(row.share_pct || 0);
        const maxMag = Number(row.max_magnitude);
        const width = Math.max(6, Math.round((count / maxCount) * 100));
        const maxMagText = Number.isFinite(maxMag) ? ` · max M${maxMag.toFixed(1)}` : '';
        const valueText = `${formatNum(count)} eventi · ${share.toFixed(1)}%`;
        return `
          <div class="itstats-rank-row">
            <div class="itstats-rank-region">${String(row.region || '--')}</div>
            <div class="itstats-rank-bar"><div class="itstats-rank-fill" style="width:${width}%"></div></div>
            <div class="itstats-rank-meta">${valueText}${maxMagText}</div>
          </div>
        `;
      }).join('');
    };

    const setError = () => {
      if (kpiMonth) kpiMonth.textContent = '--';
      if (kpiMonthNote) kpiMonthNote.textContent = 'Statistiche non disponibili';
      if (kpiDelta) kpiDelta.textContent = '--';
      if (kpiDailyAvg) kpiDailyAvg.textContent = '--';
      if (kpiDailyAvgNote) kpiDailyAvgNote.textContent = 'Media non disponibile';
      if (kpiRegion) kpiRegion.textContent = '--';
      if (kpiRegionNote) kpiRegionNote.textContent = 'Quota sul totale: --';
      if (story) story.textContent = 'Impossibile costruire il riepilogo statistico ora.';
      if (source) source.textContent = 'Provider: non disponibile';
      if (chartMonthly) chartMonthly.innerHTML = '<div class="event-item">Dati mensili non disponibili.</div>';
      if (chartYearly) chartYearly.innerHTML = '<div class="event-item">Dati annuali non disponibili.</div>';
      if (regionsList) regionsList.innerHTML = '<div class="event-item">Classifica regioni non disponibile.</div>';
      if (rankMeta) rankMeta.textContent = 'Top 12 per numero eventi';
    };

    const load = async (forceRefresh = false) => {
      try {
        if (refreshButton) {
          refreshButton.disabled = true;
          refreshButton.textContent = 'Aggiornamento...';
        }
        const suffix = forceRefresh ? '?force_refresh=1' : '';
        const response = await fetch(`/api/italy-statistics.php${suffix}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Request failed');
        const payload = await response.json();
        if (!payload || payload.ok !== true) throw new Error('Payload invalid');

        const monthlyRaw = Array.isArray(payload.monthly_counts_last12)
          ? payload.monthly_counts_last12
          : (Array.isArray(payload.monthly_counts) ? payload.monthly_counts : []);
        const yearlyRaw = Array.isArray(payload.yearly_counts_last10)
          ? payload.yearly_counts_last10
          : (Array.isArray(payload.yearly_counts) ? payload.yearly_counts : []);
        const monthly = buildLast12Months(monthlyRaw);
        const yearly = buildLast10Years(yearlyRaw);
        const topRegions = Array.isArray(payload.top_regions) ? payload.top_regions : [];
        const recap = payload.recap && typeof payload.recap === 'object' ? payload.recap : {};
        const rankings = payload.region_rankings && typeof payload.region_rankings === 'object' ? payload.region_rankings : {};
        const currentMonth = monthly.length > 0 ? monthly[monthly.length - 1] : null;
        const previousMonth = monthly.length > 1 ? monthly[monthly.length - 2] : null;
        const topRegion = recap.top_region && typeof recap.top_region === 'object' ? recap.top_region : null;
        const monthKey = String(rankings.month_key || currentMonth?.month || '');
        const yearKey = String(rankings.year_key || String(new Date().getUTCFullYear()));

        const currentCount = Number(currentMonth?.count || 0);
        const currentMonthLabel = typeof currentMonth?.month === 'string' ? formatMonthLabelLong(currentMonth.month) : '--';
        const delta = Number(currentMonth?.count || 0) - Number(previousMonth?.count || 0);
        const totalEvents = Number(payload.meta?.events_total || 0);
        const now = new Date();
        const yearNow = now.getUTCFullYear();
        const monthDaysElapsed = Math.max(1, now.getUTCDate());
        const yearDaysElapsed = Math.max(1, Math.floor((Date.UTC(yearNow, now.getUTCMonth(), now.getUTCDate()) - Date.UTC(yearNow, 0, 1)) / 86400000) + 1);
        const currentYearRow = yearly.find((row) => Number(row.year) === yearNow) || { count: 0, year: String(yearNow) };
        dailyAvgMetrics = {
          monthAvg: monthDaysElapsed > 0 ? (currentCount / monthDaysElapsed) : null,
          monthCount: currentCount,
          monthDays: monthDaysElapsed,
          monthLabel: String(currentMonth?.month || ''),
          yearAvg: yearDaysElapsed > 0 ? (Number(currentYearRow.count || 0) / yearDaysElapsed) : null,
          yearCount: Number(currentYearRow.count || 0),
          yearDays: yearDaysElapsed,
          yearLabel: String(currentYearRow.year || yearNow),
        };

        if (kpiMonth) kpiMonth.textContent = formatNum(currentCount);
        if (kpiMonthNote) kpiMonthNote.textContent = currentMonthLabel !== '--' ? `Eventi in ${currentMonthLabel}` : 'Mese corrente';
        if (kpiDelta) {
          kpiDelta.textContent = formatSigned(delta);
          kpiDelta.style.color = delta > 0 ? '#ff7a00' : (delta < 0 ? '#20e0ff' : 'var(--text-1)');
        }
        renderDailyAverageCard();
        if (kpiRegion) kpiRegion.textContent = String(topRegion?.region || '--');
        if (kpiRegionNote) {
          const share = Number(topRegion?.share_pct || 0);
          const count = Number(topRegion?.count || 0);
          kpiRegionNote.textContent = count > 0 ? `${formatNum(count)} eventi · ${share.toFixed(1)}%` : 'Quota sul totale: --';
        }

        renderVerticalChart(
          chartMonthly,
          monthly.map((row) => {
            const value = Number(row.count || 0);
            return {
              label: formatMonthLabelShort(String(row.month || '')),
              value,
              display: String(value),
              color: '#5de4c7',
            };
          }),
          { maxValue: 1 }
        );

        renderVerticalChart(
          chartYearly,
          yearly.map((row) => {
            const value = Number(row.count || 0);
            const yearLabel = String(row.year || '');
            return {
              label: yearLabel,
              value,
              display: String(value),
              color: '#20e0ff',
            };
          }),
          { maxValue: 1 }
        );

        rankScopeRows = {
          month_current: Array.isArray(rankings.month_current) ? rankings.month_current : topRegions,
          year_current: Array.isArray(rankings.year_current) ? rankings.year_current : topRegions,
          all_time: Array.isArray(rankings.all_time) ? rankings.all_time : topRegions,
        };
        rankScopeLabels = {
          month_current: monthKey ? `mese ${monthKey}` : 'mese corrente',
          year_current: yearKey ? `anno ${yearKey}` : 'anno corrente',
          all_time: 'storico dataset',
        };
        const scope = String(rankScopeSelect?.value || 'month_current');
        const scopeLabel = rankScopeLabels[scope] || 'mese corrente';
        renderRegions(rankScopeRows[scope] || [], scopeLabel);

        const bestYear = yearly.reduce((best, row) => {
          const count = Number(row.count || 0);
          if (!best || count > best.count) {
            return { year: String(row.year || '--'), count };
          }
          return best;
        }, null);

        if (story) {
          const regionName = String(topRegion?.region || 'n/d');
          const regionShare = Number(topRegion?.share_pct || 0);
          const monthText = currentMonthLabel !== '--' ? currentMonthLabel : 'mese corrente';
          const bestYearText = bestYear ? `${bestYear.year} (${formatNum(bestYear.count)} eventi)` : 'n/d';
          story.textContent = `Nel ${monthText} risultano ${formatNum(currentCount)} eventi. Nel dataset totale: ${formatNum(totalEvents)}. Regione più attiva: ${regionName} (${regionShare.toFixed(1)}%). Anno con più eventi: ${bestYearText}.`;
        }

        if (source) {
          const generated = formatUtcDate(payload.generated_at);
          source.textContent = `Provider: ${payload.provider || 'Unknown'} · aggiornato ${generated}`;
        }
      } catch (_) {
        setError();
      } finally {
        if (refreshButton) {
          refreshButton.disabled = false;
          refreshButton.textContent = 'Aggiorna statistiche';
        }
      }
    };

    refreshButton?.addEventListener('click', () => {
      void load(true);
    });
    kpiDailyAvgScope?.addEventListener('change', () => {
      try {
        const value = String(kpiDailyAvgScope.value || '');
        if (value === 'month' || value === 'year') {
          window.localStorage.setItem(dailyAvgScopeStorageKey, value);
        }
      } catch (_) {
      }
      renderDailyAverageCard();
    });

    rankScopeSelect?.addEventListener('change', () => {
      const scope = String(rankScopeSelect.value || 'month_current');
      const rows = rankScopeRows[scope] || [];
      const scopeLabel = rankScopeLabels[scope] || 'mese corrente';
      renderRegions(rows, scopeLabel);
    });

    try {
      const savedScope = window.localStorage.getItem(dailyAvgScopeStorageKey);
      if (kpiDailyAvgScope && (savedScope === 'month' || savedScope === 'year')) {
        kpiDailyAvgScope.value = savedScope;
      } else if (kpiDailyAvgScope) {
        kpiDailyAvgScope.value = 'year';
      }
    } catch (_) {
      if (kpiDailyAvgScope) kpiDailyAvgScope.value = 'year';
    }

    void load(false);
    window.setInterval(() => {
      if (document.hidden) return;
      void load(false);
    }, 5 * 60 * 1000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
