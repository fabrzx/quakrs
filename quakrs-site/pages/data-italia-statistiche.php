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
    border: 1px solid color-mix(in srgb, var(--line-strong, var(--line)) 94%, transparent);
    border-radius: 0;
    padding: 0.44rem 0.54rem;
    background:
      linear-gradient(180deg, color-mix(in srgb, var(--surface-2) 90%, #000000), color-mix(in srgb, var(--surface-1) 90%, #000000));
    box-shadow:
      inset 0 0 0 1px color-mix(in srgb, var(--line-soft, var(--line)) 56%, transparent),
      inset 0 -2px 0 color-mix(in srgb, var(--line-soft, var(--line)) 40%, transparent);
  }

  .itstats-rank-region {
    color: var(--text-1);
    font: 700 0.74rem/1.2 "Space Grotesk", sans-serif;
    letter-spacing: 0.065em;
    text-transform: uppercase;
  }

  .itstats-rank-bar {
    height: 11px;
    border-radius: 0;
    border: 1px solid color-mix(in srgb, var(--line-soft, var(--line)) 96%, transparent);
    background:
      repeating-linear-gradient(
        90deg,
        color-mix(in srgb, var(--line-soft, var(--line)) 36%, transparent) 0 10px,
        transparent 10px 12px
      ),
      color-mix(in srgb, var(--surface-2) 80%, transparent);
    overflow: hidden;
  }

  .itstats-rank-fill {
    height: 100%;
    min-width: 6px;
    background: linear-gradient(
      90deg,
      color-mix(in srgb, var(--acid-cyan, #20e0ff) 92%, #ffffff) 0%,
      var(--itstats-fill-end, color-mix(in srgb, var(--acid-cyan, #20e0ff) 92%, #ffffff)) 100%
    );
    border-right: 2px solid color-mix(in srgb, var(--bg-0, #050816) 84%, transparent);
  }

  .itstats-rank-meta {
    color: var(--text-2);
    font: 700 0.7rem/1.2 "Space Grotesk", sans-serif;
    text-align: right;
    letter-spacing: 0.045em;
    text-transform: uppercase;
    font-variant-numeric: tabular-nums;
  }

  .itstats-bottom {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.72rem;
    align-items: stretch;
  }

  .itstats-story-meta {
    margin-top: 0.72rem;
    padding-top: 0.62rem;
    border-top: 1px solid color-mix(in srgb, var(--line-soft, var(--line)) 75%, transparent);
    color: var(--text-3);
    font: 700 0.72rem/1.3 "Space Grotesk", sans-serif;
    letter-spacing: 0.03em;
    text-transform: uppercase;
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

  .itstats-kpi-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
  }

  .itstats-kpi-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.16rem;
    border: 1px solid color-mix(in srgb, var(--line) 70%, transparent);
    background: color-mix(in srgb, var(--surface-2) 85%, transparent);
    border-radius: 8px;
  }

  .itstats-kpi-toggle-btn {
    appearance: none;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--text-2);
    padding: 0.24rem 0.46rem;
    font: 700 0.66rem/1 "Space Grotesk", sans-serif;
    letter-spacing: 0.02em;
    cursor: pointer;
  }

  .itstats-kpi-toggle-btn.is-active {
    color: var(--text-1);
    background: color-mix(in srgb, var(--acid-cyan, #20e0ff) 18%, var(--surface-1));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--acid-cyan, #20e0ff) 46%, transparent);
  }

  #itstats-kpi-region.kpi-value {
    font: 800 clamp(1.02rem, 1.8vw, 1.62rem) / 1.04 "Space Grotesk", sans-serif;
    letter-spacing: 0.01em;
    overflow-wrap: anywhere;
    word-break: break-word;
    text-wrap: balance;
  }

  .itstats-period-controls {
    margin-top: 0.58rem;
    display: grid;
    grid-template-columns: minmax(130px, 170px) minmax(130px, 170px) auto;
    gap: 0.46rem;
    align-items: center;
  }

  .itstats-period-controls[hidden] {
    display: none !important;
  }

  .itstats-period-controls input[type="date"] {
    border-radius: 6px;
    border: 1px solid color-mix(in srgb, var(--line) 70%, transparent);
    background: color-mix(in srgb, var(--surface-2) 86%, transparent);
    color: var(--text-1);
    padding: 0.34rem 0.44rem;
    font: 700 0.68rem/1.1 "Space Grotesk", sans-serif;
  }

  .itstats-period-controls .btn {
    white-space: nowrap;
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

    .itstats-kpi-head {
      align-items: flex-start;
      flex-direction: column;
      gap: 0.38rem;
    }

    .itstats-period-controls {
      grid-template-columns: 1fr;
      align-items: stretch;
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
    <div class="itstats-kpi-head">
      <p class="kpi-label">Media giornaliera</p>
      <div class="itstats-kpi-toggle" role="tablist" aria-label="Tipo media giornaliera">
        <button class="itstats-kpi-toggle-btn" data-itstats-daily-scope="year" type="button">Annua</button>
        <button class="itstats-kpi-toggle-btn" data-itstats-daily-scope="month" type="button">Mensile</button>
      </div>
    </div>
    <p id="itstats-kpi-dailyavg" class="kpi-value">--</p>
    <p id="itstats-kpi-dailyavg-note" class="kpi-note">Caricamento...</p>
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
          <option value="preset_7d">Ultimi 7 giorni</option>
          <option value="preset_30d">Ultimi 30 giorni</option>
          <option value="preset_90d">Ultimi 90 giorni</option>
          <option value="preset_365d">Ultimi 365 giorni</option>
          <option value="custom_period">Personalizzato...</option>
        </select>
      </label>
    </div>
    <p id="itstats-rank-meta" class="feed-meta">Top 12 per numero eventi (mese corrente)</p>
    <div id="itstats-period-controls" class="itstats-period-controls" hidden>
      <input id="itstats-period-from" type="date" aria-label="Data inizio" />
      <input id="itstats-period-to" type="date" aria-label="Data fine" />
      <button id="itstats-period-apply" class="btn btn-ghost" type="button">Applica</button>
    </div>
    <div id="itstats-regions" class="itstats-rank">
      <div class="event-item">Caricamento classifica regioni...</div>
    </div>
  </article>
</section>

<section class="panel itstats-bottom">
  <article class="card page-card">
    <h3>Lettura rapida</h3>
    <p id="itstats-story" class="insight-lead">Preparazione sintesi statistica...</p>
    <p id="itstats-updated" class="itstats-story-meta">Aggiornamento dati: --</p>
  </article>
</section>

<script>
  (() => {
    const kpiMonth = document.querySelector('#itstats-kpi-month');
    const kpiMonthNote = document.querySelector('#itstats-kpi-month-note');
    const kpiDelta = document.querySelector('#itstats-kpi-delta');
    const kpiDailyAvg = document.querySelector('#itstats-kpi-dailyavg');
    const kpiDailyAvgNote = document.querySelector('#itstats-kpi-dailyavg-note');
    const kpiDailyAvgButtons = Array.from(document.querySelectorAll('[data-itstats-daily-scope]'));
    const kpiRegion = document.querySelector('#itstats-kpi-region');
    const kpiRegionNote = document.querySelector('#itstats-kpi-region-note');
    const chartMonthly = document.querySelector('#itstats-chart-monthly');
    const chartYearly = document.querySelector('#itstats-chart-yearly');
    const regionsList = document.querySelector('#itstats-regions');
    const rankScopeSelect = document.querySelector('#itstats-rank-scope');
    const rankMeta = document.querySelector('#itstats-rank-meta');
    const periodControls = document.querySelector('#itstats-period-controls');
    const periodFromInput = document.querySelector('#itstats-period-from');
    const periodToInput = document.querySelector('#itstats-period-to');
    const periodApplyButton = document.querySelector('#itstats-period-apply');
    const story = document.querySelector('#itstats-story');
    const updatedMeta = document.querySelector('#itstats-updated');
    const dailyAvgScopeStorageKey = 'itstats_dailyavg_scope';
    let dailyAvgScope = 'year';
    let rankScopeRows = {
      month_current: [],
      year_current: [],
      all_time: [],
      preset_7d: [],
      preset_30d: [],
      preset_90d: [],
      preset_365d: [],
      custom_period: [],
    };
    let rankScopeLabels = {
      month_current: 'mese corrente',
      year_current: 'anno corrente',
      all_time: 'storico dataset',
      preset_7d: 'ultimi 7 giorni',
      preset_30d: 'ultimi 30 giorni',
      preset_90d: 'ultimi 90 giorni',
      preset_365d: 'ultimi 365 giorni',
      custom_period: 'periodo personalizzato',
    };
    let customScopeDays = 30;
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

    const formatYmdUtc = (date) => {
      const y = date.getUTCFullYear();
      const m = String(date.getUTCMonth() + 1).padStart(2, '0');
      const d = String(date.getUTCDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };

    const dayDiffInclusive = (fromYmd, toYmd) => {
      const fromTs = Date.parse(`${fromYmd}T00:00:00Z`);
      const toTs = Date.parse(`${toYmd}T00:00:00Z`);
      if (!Number.isFinite(fromTs) || !Number.isFinite(toTs) || toTs < fromTs) return 0;
      return Math.floor((toTs - fromTs) / 86400000) + 1;
    };

    const buildPresetRange = (presetKey) => {
      const now = new Date();
      const to = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()));
      const daysMap = { '7d': 7, '30d': 30, '90d': 90, '365d': 365 };
      const days = Number(daysMap[presetKey] || 30);
      const from = new Date(to.getTime() - ((days - 1) * 86400000));
      return {
        from: formatYmdUtc(from),
        to: formatYmdUtc(to),
        days,
      };
    };

    const scopeColorProfile = (scope, customDays = 0) => {
      if (scope === 'all_time') return 'all_time';
      if (scope === 'year_current' || scope === 'preset_365d') return 'year_current';
      if (scope === 'preset_90d') return 'quarter';
      if (scope === 'custom_period') {
        if (customDays > 366) return 'all_time';
        if (customDays > 120) return 'year_current';
        if (customDays > 31) return 'quarter';
        return 'month_current';
      }
      return 'month_current';
    };

    const formatSigned = (value) => {
      const num = Number(value);
      if (!Number.isFinite(num)) return '--';
      if (num > 0) return `+${formatNum(num)}`;
      return formatNum(num);
    };

    const renderDailyAverageCard = () => {
      const scope = String(dailyAvgScope || 'year');
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

    const syncDailyAvgToggle = () => {
      kpiDailyAvgButtons.forEach((button) => {
        const scope = String(button.dataset.itstatsDailyScope || '');
        const active = scope === dailyAvgScope;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
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

    const getRankColorByCount = (scope, countRaw) => {
      const count = Number(countRaw || 0);
      if (!Number.isFinite(count) || count <= 0) {
        return '#20e0ff';
      }

      if (scope === 'all_time') {
        if (count >= 4000) return '#ff4d6d';
        if (count >= 2000) return '#ff9a00';
        if (count >= 1000) return '#ffd400';
        if (count >= 500) return '#7dff3a';
        return '#20e0ff';
      }

      if (scope === 'year_current') {
        if (count >= 400) return '#ff4d6d';
        if (count >= 200) return '#ff9a00';
        if (count >= 100) return '#ffd400';
        if (count >= 50) return '#7dff3a';
        return '#20e0ff';
      }

      if (scope === 'quarter') {
        if (count >= 240) return '#ff4d6d';
        if (count >= 160) return '#ff9a00';
        if (count >= 80) return '#ffd400';
        if (count >= 40) return '#7dff3a';
        return '#20e0ff';
      }

      if (count >= 81) return '#ff4d6d';
      if (count >= 61) return '#ff9a00';
      if (count >= 41) return '#ffd400';
      if (count >= 21) return '#7dff3a';
      return '#20e0ff';
    };

    const renderRegions = (rows, scope = 'month_current', scopeLabel = 'mese corrente', periodDays = 0) => {
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
        const colorProfile = scopeColorProfile(scope, periodDays);
        const endColor = getRankColorByCount(colorProfile, count);
        const maxMagText = Number.isFinite(maxMag) ? ` · max M${maxMag.toFixed(1)}` : '';
        const valueText = `${formatNum(count)} eventi · ${share.toFixed(1)}%`;
        return `
          <div class="itstats-rank-row">
            <div class="itstats-rank-region">${String(row.region || '--')}</div>
            <div class="itstats-rank-bar"><div class="itstats-rank-fill" style="width:${width}%;--itstats-fill-end:${endColor}"></div></div>
            <div class="itstats-rank-meta">${valueText}${maxMagText}</div>
          </div>
        `;
      }).join('');
    };

    const loadPeriodRanking = async (scopeKey, fromYmd, toYmd, label) => {
      const from = String(fromYmd || '').trim();
      const to = String(toYmd || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to)) {
        return;
      }
      const days = dayDiffInclusive(from, to);
      if (days <= 0) {
        return;
      }

      if (periodApplyButton) {
        periodApplyButton.disabled = true;
        periodApplyButton.textContent = 'Caricamento...';
      }

      try {
        const url = `/api/italy-statistics.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
          throw new Error('period request failed');
        }
        const payload = await response.json();
        if (!payload || payload.ok !== true) {
          throw new Error('period payload invalid');
        }
        const rows = Array.isArray(payload.top_regions_period) ? payload.top_regions_period : [];
        const scopedLabel = `${label} (${from}..${to})`;
        rankScopeRows[scopeKey] = rows;
        rankScopeLabels[scopeKey] = scopedLabel;
        customScopeDays = days;
        renderRegions(rows, scopeKey, scopedLabel, days);
      } catch (_) {
        if (rankMeta) rankMeta.textContent = `Top 12 per numero eventi (${label})`;
        if (regionsList) {
          regionsList.innerHTML = '<div class="event-item">Impossibile caricare il periodo selezionato.</div>';
        }
      } finally {
        if (periodApplyButton) {
          periodApplyButton.disabled = false;
          periodApplyButton.textContent = 'Applica';
        }
      }
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
      if (updatedMeta) updatedMeta.textContent = 'Aggiornamento dati: non disponibile';
      if (chartMonthly) chartMonthly.innerHTML = '<div class="event-item">Dati mensili non disponibili.</div>';
      if (chartYearly) chartYearly.innerHTML = '<div class="event-item">Dati annuali non disponibili.</div>';
      if (regionsList) regionsList.innerHTML = '<div class="event-item">Classifica regioni non disponibile.</div>';
      if (rankMeta) rankMeta.textContent = 'Top 12 per numero eventi';
    };

    const load = async (forceRefresh = false) => {
      try {
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
        const monthTopRegion = Array.isArray(rankings.month_current) && rankings.month_current.length > 0
          ? rankings.month_current[0]
          : null;
        const yearTopRegion = Array.isArray(rankings.year_current) && rankings.year_current.length > 0
          ? rankings.year_current[0]
          : null;
        const topRegion = monthTopRegion
          || yearTopRegion
          || (recap.top_region && typeof recap.top_region === 'object' ? recap.top_region : null);
        const monthKey = String(rankings.month_key || currentMonth?.month || '');
        const yearKey = String(rankings.year_key || String(new Date().getUTCFullYear()));

        const currentCount = Number(currentMonth?.count || 0);
        const currentMonthLabel = typeof currentMonth?.month === 'string' ? formatMonthLabelLong(currentMonth.month) : '--';
        const delta = Number(currentMonth?.count || 0) - Number(previousMonth?.count || 0);
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
        renderRegions(rankScopeRows[scope] || [], scope, scopeLabel, scope === 'custom_period' ? customScopeDays : 0);

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
          story.textContent = `Nel ${monthText} risultano ${formatNum(currentCount)} eventi. Regione più attiva: ${regionName} (${regionShare.toFixed(1)}%). Anno con più eventi: ${bestYearText}.`;
        }

        if (updatedMeta) {
          const generated = formatUtcDate(payload.generated_at);
          updatedMeta.textContent = generated !== '--'
            ? `Aggiornamento dati: ${generated}`
            : 'Aggiornamento dati: non disponibile';
        }
      } catch (_) {
        setError();
      } finally {
      }
    };

    kpiDailyAvgButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const scope = String(button.dataset.itstatsDailyScope || '');
        if (scope !== 'month' && scope !== 'year') return;
        dailyAvgScope = scope;
        try {
          window.localStorage.setItem(dailyAvgScopeStorageKey, scope);
        } catch (_) {
        }
        syncDailyAvgToggle();
        renderDailyAverageCard();
      });
    });

    rankScopeSelect?.addEventListener('change', () => {
      const scope = String(rankScopeSelect.value || 'month_current');
      const isCustom = scope === 'custom_period';
      if (periodControls) {
        periodControls.hidden = !isCustom;
      }

      if (scope === 'preset_7d' || scope === 'preset_30d' || scope === 'preset_90d' || scope === 'preset_365d') {
        const presetKey = scope.replace('preset_', '');
        const range = buildPresetRange(presetKey);
        const scopeLabel = rankScopeLabels[scope] || 'periodo';
        void loadPeriodRanking(scope, range.from, range.to, scopeLabel);
        return;
      }

      if (scope === 'custom_period') {
        const from = String(periodFromInput?.value || '').trim();
        const to = String(periodToInput?.value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(from) && /^\d{4}-\d{2}-\d{2}$/.test(to) && dayDiffInclusive(from, to) > 0) {
          const scopeLabel = rankScopeLabels[scope] || 'periodo personalizzato';
          void loadPeriodRanking(scope, from, to, scopeLabel);
          return;
        }
      }

      const rows = rankScopeRows[scope] || [];
      const scopeLabel = rankScopeLabels[scope] || 'mese corrente';
      renderRegions(rows, scope, scopeLabel, scope === 'custom_period' ? customScopeDays : 0);
    });

    periodApplyButton?.addEventListener('click', () => {
      const from = String(periodFromInput?.value || '').trim();
      const to = String(periodToInput?.value || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to) || dayDiffInclusive(from, to) <= 0) {
        if (rankMeta) rankMeta.textContent = 'Intervallo non valido (usa date corrette)';
        return;
      }
      if (rankScopeSelect) rankScopeSelect.value = 'custom_period';
      if (periodControls) periodControls.hidden = false;
      rankScopeLabels.custom_period = 'periodo personalizzato';
      void loadPeriodRanking('custom_period', from, to, 'periodo personalizzato');
    });

    try {
      const savedScope = window.localStorage.getItem(dailyAvgScopeStorageKey);
      if (savedScope === 'month' || savedScope === 'year') {
        dailyAvgScope = savedScope;
      } else {
        dailyAvgScope = 'year';
      }
    } catch (_) {
      dailyAvgScope = 'year';
    }
    syncDailyAvgToggle();

    const defaultRange = buildPresetRange('30d');
    if (periodFromInput && !periodFromInput.value) periodFromInput.value = defaultRange.from;
    if (periodToInput && !periodToInput.value) periodToInput.value = defaultRange.to;
    if (periodControls) periodControls.hidden = String(rankScopeSelect?.value || 'month_current') !== 'custom_period';

    void load(false);
    window.setInterval(() => {
      if (document.hidden) return;
      void load(false);
    }, 5 * 60 * 1000);
  })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
