(function () {
  'use strict';

  const app = document.querySelector('[data-yoga-app]');
  if (!app || typeof YOGA_CONFIG === 'undefined') return;

  const form = app.querySelector('[data-yoga-form]');
  const urlInput = app.querySelector('#yoga-video-url');
  const postPanel = app.querySelector('[data-yoga-post-panel]');
  const prePanel = app.querySelector('[data-yoga-pre-panel]');
  const statusBox = app.querySelector('[data-yoga-status]');
  const statusTitle = app.querySelector('[data-yoga-status-title]');
  const statusText = app.querySelector('[data-yoga-status-text]');
  const errorBox = app.querySelector('[data-yoga-error]');
  const results = app.querySelector('[data-yoga-results]');
  const modeButtons = app.querySelectorAll('[data-yoga-mode]');

  let activeToken = '';
  let activeReport = null;
  let loadingTimer = null;

  modeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      modeButtons.forEach((item) => {
        const selected = item === button;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-selected', selected ? 'true' : 'false');
      });
      const isPost = button.dataset.yogaMode === 'post';
      postPanel.hidden = !isPost;
      prePanel.hidden = isPost;
      hideError();
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const url = urlInput.value.trim();
    if (!url) {
      showError('Paste a YouTube video URL first.');
      urlInput.focus();
      return;
    }

    setLoading(true);
    results.hidden = true;
    results.innerHTML = '';
    hideError();

    try {
      const data = await api('analyze', { url });
      activeToken = data.token;
      activeReport = null;
      renderPreview(data.preview, data.token);
    } catch (error) {
      showError(error.message || 'YOGA could not complete the analysis.');
    } finally {
      setLoading(false);
    }
  });

  function setLoading(isLoading) {
    const button = form.querySelector('button[type="submit"]');
    button.disabled = isLoading;
    statusBox.hidden = !isLoading;
    clearInterval(loadingTimer);

    if (!isLoading) return;

    const steps = [
      ['Reading the video…', 'Collecting public metadata and current settings.'],
      ['Checking accessibility…', 'Reviewing embedding, age and country availability.'],
      ['Comparing channel context…', 'Looking at recent public video performance.'],
      ['Preparing practical actions…', 'Turning signals into clear next steps.']
    ];
    let index = 0;
    statusTitle.textContent = steps[0][0];
    statusText.textContent = steps[0][1];
    loadingTimer = setInterval(() => {
      index = Math.min(index + 1, steps.length - 1);
      statusTitle.textContent = steps[index][0];
      statusText.textContent = steps[index][1];
    }, 1150);
  }

  async function api(path, payload) {
    const response = await fetch(YOGA_CONFIG.restUrl + path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || 'Unexpected server response.');
    }
    return data;
  }

  function renderPreview(preview, token) {
    const shot = preview.snapshot || {};
    const access = preview.accessibility || {};
    const opportunity = preview.top_opportunity || {};
    const strengths = Array.isArray(preview.strengths) ? preview.strengths : [];

    results.innerHTML = `
      <article class="yoga-result-card">
        <div class="yoga-video-head">
          ${shot.thumbnail ? `<img class="yoga-video-thumb" src="${escAttr(shot.thumbnail)}" alt="YouTube video thumbnail">` : '<div class="yoga-video-thumb"></div>'}
          <div class="yoga-video-meta">
            <h2>${esc(shot.title || 'YouTube video')}</h2>
            <p>${esc(shot.channel_title || '')}</p>
            <div class="yoga-stat-row">
              <div class="yoga-stat"><span>Views</span><strong>${formatNumber(shot.views || 0)}</strong></div>
              <div class="yoga-stat"><span>Format</span><strong>${esc(shot.video_type || 'Video')}</strong></div>
              <div class="yoga-stat"><span>Actions ready</span><strong>${formatNumber(preview.action_count || 0)}</strong></div>
            </div>
          </div>
        </div>
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title">
          <h2>Accessibility &amp; Reach</h2>
          <p>Verified from public YouTube data</p>
        </div>
        <div class="yoga-check-grid">
          ${checkCard('Website compatibility', access.website_compatibility || 'Unknown', !!access.embeddable)}
          ${checkCard('Global reach', access.global_reach || 'Unknown', !String(access.global_reach || '').includes('selected') && !String(access.global_reach || '').includes('blocked'))}
          ${checkCard('Audience access', access.age_restricted ? 'Adults only' : 'Broad access', !access.age_restricted)}
        </div>
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title">
          <h2>Your strongest signals</h2>
          <p>Positive foundations to build on</p>
        </div>
        <div class="yoga-strength-grid">
          ${strengths.map((item) => `
            <div class="yoga-strength">
              <h3>${esc(item.title || '')}</h3>
              <p>${esc(item.text || '')}</p>
            </div>`).join('')}
        </div>
      </article>

      <article class="yoga-result-card yoga-opportunity-preview">
        <div class="yoga-section-title">
          <h2>Top growth opportunity</h2>
          <p>Preview</p>
        </div>
        <div class="yoga-action-preview">
          <div class="yoga-badges">
            <span class="yoga-badge is-priority">${esc(opportunity.priority || 'Do next')}</span>
            <span class="yoga-badge">Impact: ${esc(opportunity.impact || 'Medium')}</span>
          </div>
          <h3>${esc(opportunity.title || 'Your action plan is ready')}</h3>
          <p>${esc(opportunity.reason || 'Unlock the complete report to see all recommended next steps.')}</p>
        </div>
      </article>

      <article class="yoga-result-card yoga-gate">
        <div class="yoga-gate-icon" aria-hidden="true">✉</div>
        <h2>Your complete YOGA Action Plan is ready</h2>
        <p>Enter your email to unlock every recommendation, save this analysis and export the data. The report opens immediately and is also sent to you.</p>
        <form class="yoga-unlock-form" data-yoga-unlock-form>
          <div class="yoga-unlock-row">
            <input type="email" name="email" placeholder="Your email address" autocomplete="email" required>
            <button class="button yoga-primary-button" type="submit">Unlock my report</button>
          </div>
          <label class="yoga-consent">
            <input type="checkbox" name="marketing" value="1">
            <span>I’d also like occasional YouTube growth tips, product updates and offers from Best YouTube Views.</span>
          </label>
        </form>
      </article>
    `;

    results.hidden = false;
    bindUnlockForm(token);
    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function bindUnlockForm(token) {
    const unlockForm = results.querySelector('[data-yoga-unlock-form]');
    if (!unlockForm) return;
    unlockForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = unlockForm.querySelector('button');
      const email = unlockForm.elements.email.value.trim();
      const marketing = unlockForm.elements.marketing.checked;
      if (!email) return;

      button.disabled = true;
      button.textContent = 'Unlocking…';
      hideError();
      try {
        const data = await api('unlock', {
          token,
          email,
          marketing,
          pageUrl: YOGA_CONFIG.pageUrl
        });
        activeReport = data.report;
        renderFullReport(data.report, data.shareUrl || '');
      } catch (error) {
        showError(error.message || 'The report could not be unlocked.');
        button.disabled = false;
        button.textContent = 'Unlock my report';
      }
    });
  }

  function renderFullReport(report, shareUrl) {
    const shot = report.snapshot || {};
    const access = report.accessibility || {};
    const perf = report.performance || {};
    const disc = report.discoverability || {};
    const audience = report.audience_signals || {};
    const strengths = Array.isArray(report.strengths) ? report.strengths : [];
    const actions = Array.isArray(report.actions) ? report.actions : [];

    results.innerHTML = `
      <article class="yoga-result-card">
        <div class="yoga-video-head">
          ${shot.thumbnail ? `<img class="yoga-video-thumb" src="${escAttr(shot.thumbnail)}" alt="YouTube video thumbnail">` : '<div class="yoga-video-thumb"></div>'}
          <div class="yoga-video-meta">
            <h2>${esc(shot.title || 'YouTube video')}</h2>
            <p>${esc(shot.channel_title || '')}</p>
            <div class="yoga-stat-row">
              <div class="yoga-stat"><span>Views</span><strong>${formatNumber(shot.views || 0)}</strong></div>
              <div class="yoga-stat"><span>Published</span><strong>${formatDate(shot.published_at)}</strong></div>
              <div class="yoga-stat"><span>Duration</span><strong>${esc(shot.duration || '—')}</strong></div>
            </div>
          </div>
        </div>
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Accessibility &amp; Reach</h2><p>Public technical status</p></div>
        <div class="yoga-check-grid">
          ${checkCard('Website compatibility', access.embeddable ? 'Embeddable' : 'Embedding limited', !!access.embeddable)}
          ${checkCard('Global reach', access.global_reach || 'Unknown', !access.age_restricted && !access.blocked_countries?.length)}
          ${checkCard('Age access', access.age_restricted ? 'Age restricted' : 'No public age restriction', !access.age_restricted)}
          ${checkCard('Captions', access.captions_available ? 'Available' : 'Opportunity to add', !!access.captions_available)}
          ${checkCard('Comments', access.comments_enabled ? 'Available' : 'Unavailable', !!access.comments_enabled)}
          ${checkCard('Definition', access.definition || 'Unknown', String(access.definition || '').toUpperCase() === 'HD')}
        </div>
        ${countrySection(access)}
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Performance context</h2><p>Compared with recent public channel data</p></div>
        <div class="yoga-metric-grid">
          ${metricCard('Public momentum', perf.momentum || 'Building')}
          ${metricCard('Views per day', formatNumber(perf.views_per_day || 0))}
          ${metricCard('Recent median / day', formatNumber(perf.median_recent_views_per_day || 0))}
          ${metricCard('Likes / 1,000 views', formatNumber(perf.like_rate_per_1000_views || 0))}
          ${metricCard('Comments / 1,000 views', formatNumber(perf.comment_rate_per_1000_views || 0))}
          ${metricCard('Recent videos checked', formatNumber(perf.recent_videos_analyzed || 0))}
        </div>
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Discoverability snapshot</h2><p>Title, description and structure</p></div>
        <div class="yoga-metric-grid">
          ${metricCard('Title length', `${disc.title_length || 0} characters`)}
          ${metricCard('Description', `${formatNumber(disc.description_length || 0)} characters`)}
          ${metricCard('Tags', formatNumber(disc.tag_count || 0))}
          ${metricCard('Chapters', formatNumber(disc.chapters_count || 0))}
          ${metricCard('Hashtags', formatNumber(disc.hashtags_count || 0))}
          ${metricCard('Clear CTA', disc.has_call_to_action ? 'Present' : 'Opportunity')}
        </div>
        ${(disc.primary_terms || []).length ? `<div class="yoga-term-list">${disc.primary_terms.map((term) => `<span class="yoga-term">${esc(term)}</span>`).join('')}</div>` : ''}
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Strongest signals</h2><p>What is already working</p></div>
        <div class="yoga-strength-grid">
          ${strengths.map((item) => `<div class="yoga-strength"><h3>${esc(item.title || '')}</h3><p>${esc(item.text || '')}</p></div>`).join('')}
        </div>
      </article>

      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Your operational action plan</h2><p>Ordered by priority</p></div>
        <div class="yoga-actions">
          ${actions.map(actionCard).join('')}
        </div>
      </article>

      ${audienceSection(audience)}

      <article class="yoga-result-card">
        <div class="yoga-export-bar">
          <div>
            <h2 style="margin:0 0 4px;font-size:20px;">Export your YOGA report</h2>
            <p style="margin:0;color:var(--yoga-muted);font-size:13px;">The first build includes JSON, CSV and copyable action plans.</p>
          </div>
          <div class="yoga-export-buttons">
            <button type="button" class="button yoga-export-button" data-yoga-export="json">Download JSON</button>
            <button type="button" class="button yoga-export-button" data-yoga-export="csv">Download CSV</button>
            <button type="button" class="button yoga-export-button" data-yoga-copy-actions>Copy actions</button>
            ${shareUrl ? `<button type="button" class="button yoga-export-button" data-yoga-copy-link data-link="${escAttr(shareUrl)}">Copy report link</button>` : ''}
          </div>
        </div>
        <p class="yoga-disclaimer">${esc(report.disclaimer || '')}</p>
      </article>

      <article class="yoga-result-card yoga-professional-cta">
        <h2>Need a deeper human evaluation?</h2>
        <p>Get a professional review tailored to your video, channel, audience and promotion goals.</p>
        <a class="button yoga-primary-button" href="${escAttr(YOGA_CONFIG.professionalUrl)}">Professional Video &amp; Channel Analysis</a>
      </article>
    `;

    results.hidden = false;
    bindExports(report);
    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function bindExports(report) {
    results.querySelectorAll('[data-yoga-export]').forEach((button) => {
      button.addEventListener('click', () => {
        const type = button.dataset.yogaExport;
        if (type === 'json') downloadJson(report);
        if (type === 'csv') downloadCsv(report);
      });
    });

    const copyActions = results.querySelector('[data-yoga-copy-actions]');
    if (copyActions) {
      copyActions.addEventListener('click', async () => {
        const text = (report.actions || []).map((action, index) =>
          `${index + 1}. ${action.title}\n${action.instruction}`
        ).join('\n\n');
        await copyText(text);
        flashButton(copyActions, 'Copied');
      });
    }

    const copyLink = results.querySelector('[data-yoga-copy-link]');
    if (copyLink) {
      copyLink.addEventListener('click', async () => {
        await copyText(copyLink.dataset.link || '');
        flashButton(copyLink, 'Copied');
      });
    }
  }

  function countrySection(access) {
    const blocked = Array.isArray(access.blocked_countries) ? access.blocked_countries : [];
    const allowed = Array.isArray(access.allowed_countries) ? access.allowed_countries : [];
    if (!blocked.length && !allowed.length) return '';
    const list = allowed.length ? allowed : blocked;
    const label = allowed.length ? 'Allowed countries' : 'Blocked countries';
    return `<div style="margin-top:15px;"><strong style="font-size:13px;">${label}</strong><div class="yoga-country-list">${list.slice(0, 80).map((code) => `<span class="yoga-country">${esc(countryName(code))}</span>`).join('')}</div></div>`;
  }

  function audienceSection(audience) {
    const questions = Array.isArray(audience.questions) ? audience.questions : [];
    const terms = Array.isArray(audience.recurring_terms) ? audience.recurring_terms : [];
    if (!questions.length && !terms.length) return '';
    return `
      <article class="yoga-result-card">
        <div class="yoga-section-title"><h2>Audience signals</h2><p>${formatNumber(audience.comments_sampled || 0)} public comments sampled</p></div>
        ${terms.length ? `<div class="yoga-term-list">${terms.map((term) => `<span class="yoga-term">${esc(term)}</span>`).join('')}</div>` : ''}
        ${questions.length ? `<div style="margin-top:15px;">${questions.map((q) => `<div class="yoga-action" style="padding:12px 14px;margin-top:8px;">${esc(q)}</div>`).join('')}</div>` : ''}
      </article>`;
  }

  function actionCard(action) {
    return `
      <div class="yoga-action">
        <div class="yoga-badges">
          <span class="yoga-badge is-priority">${esc(action.priority || '')}</span>
          <span class="yoga-badge">Impact: ${esc(action.impact || '')}</span>
          <span class="yoga-badge">Effort: ${esc(action.effort || '')}</span>
        </div>
        <h3>${esc(action.title || '')}</h3>
        <p>${esc(action.reason || '')}</p>
        <div class="yoga-action-instruction">${esc(action.instruction || '')}</div>
      </div>`;
  }

  function checkCard(label, value, good) {
    return `<div class="yoga-check ${good ? 'is-good' : 'is-attention'}"><span>${esc(label)}</span><strong>${esc(value)}</strong></div>`;
  }

  function metricCard(label, value) {
    return `<div class="yoga-metric"><span>${esc(label)}</span><strong>${esc(String(value))}</strong></div>`;
  }

  function downloadJson(report) {
    downloadBlob(JSON.stringify(report, null, 2), 'yoga-report.json', 'application/json');
  }

  function downloadCsv(report) {
    const rows = [['section', 'field', 'value']];
    flatten(report).forEach(([field, value]) => {
      const section = field.includes('.') ? field.split('.')[0] : 'report';
      rows.push([section, field, Array.isArray(value) ? value.join(' | ') : String(value ?? '')]);
    });
    const csv = rows.map((row) => row.map(csvCell).join(',')).join('\n');
    downloadBlob(csv, 'yoga-report.csv', 'text/csv;charset=utf-8');
  }

  function flatten(object, prefix = '') {
    const output = [];
    Object.entries(object || {}).forEach(([key, value]) => {
      const path = prefix ? `${prefix}.${key}` : key;
      if (value && typeof value === 'object' && !Array.isArray(value)) {
        output.push(...flatten(value, path));
      } else if (Array.isArray(value) && value.some((item) => item && typeof item === 'object')) {
        output.push([path, JSON.stringify(value)]);
      } else {
        output.push([path, value]);
      }
    });
    return output;
  }

  function csvCell(value) {
    return `"${String(value ?? '').replace(/"/g, '""')}"`;
  }

  function downloadBlob(content, filename, type) {
    const blob = new Blob([content], { type });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(link.href), 500);
  }

  async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return;
    }
    const area = document.createElement('textarea');
    area.value = text;
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    document.execCommand('copy');
    area.remove();
  }

  function flashButton(button, text) {
    const original = button.textContent;
    button.textContent = text;
    setTimeout(() => { button.textContent = original; }, 1200);
  }

  function countryName(code) {
    try {
      if (typeof Intl.DisplayNames === 'function') {
        const names = new Intl.DisplayNames([document.documentElement.lang || 'en'], { type: 'region' });
        return names.of(String(code).toUpperCase()) || code;
      }
    } catch (e) {}
    return String(code).toUpperCase();
  }

  function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
  }

  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(date);
  }

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escAttr(value) {
    return esc(value);
  }

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
  }

  function hideError() {
    errorBox.hidden = true;
    errorBox.textContent = '';
  }

  async function loadSavedReport() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('yoga_report');
    if (!token) return;
    setLoading(true);
    hideError();
    try {
      const response = await fetch(YOGA_CONFIG.restUrl + 'report/' + encodeURIComponent(token));
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.message || 'This report is unavailable.');
      activeToken = token;
      activeReport = data.report;
      renderFullReport(data.report, window.location.href);
    } catch (error) {
      showError(error.message);
    } finally {
      setLoading(false);
    }
  }

  loadSavedReport();
})();
