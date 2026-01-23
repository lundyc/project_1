(() => {
          const cfg = window.MatchPeriodsConfig || null;
          if (!cfg) return;

          const periodList = document.getElementById('periodList');
          const flash = document.getElementById('periodsFlash');
          const currentSecondInput = document.getElementById('currentSecondInput');
          const periodForm = document.getElementById('periodForm');
          const formRows = Array.from(document.querySelectorAll('.period-form-row'));
          const presetButtons = Array.from(document.querySelectorAll('[data-period-preset]'));

          const endpoints = cfg.endpoints || {};
          let periods = cfg.periods || [];
          let activePeriodId = (periods.find((p) => p.status === 'active') || {}).id || null;

          function setFlash(type, message) {
                    if (!flash || !message) return;
                    flash.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info');
                    flash.classList.add('alert', type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : 'alert-info');
                    flash.textContent = message;
          }

          function clearFlash() {
                    if (!flash) return;
                    flash.classList.add('d-none');
                    flash.textContent = '';
          }

          function formatSeconds(sec) {
                    if (sec === null || sec === undefined || sec === '') return '--';
                    const value = Number(sec);
                    const minutes = Math.floor(value / 60);
                    const seconds = value % 60;
                    return `${minutes}:${String(seconds).padStart(2, '0')}`;
          }

          function statusBadge(status) {
                    const cls =
                              status === 'completed'
                                        ? 'period-badge period-badge-completed'
                                        : status === 'active'
                                                  ? 'period-badge period-badge-active'
                                                  : 'period-badge period-badge-pending';
                    const label = status === 'completed' ? 'Completed' : status === 'active' ? 'Active' : 'Pending';
                    return `<span class="${cls}">${label}</span>`;
          }

          function formatMeta(period) {
                    const planned =
                              period.minutes_planned !== null && period.minutes_planned !== undefined
                                        ? `${period.minutes_planned} min planned`
                                        : 'No planned length';
                    const start = formatSeconds(period.start_second ?? period.startSecond ?? null);
                    const end = formatSeconds(period.end_second ?? period.endSecond ?? null);
                    return `${planned} • Start ${start} • End ${end}`;
          }

          function renderPeriods() {
                    if (!periodList) return;
                    if (!periods.length) {
                              periodList.innerHTML = '<div class="text-muted-alt">No periods defined yet.</div>';
                              return;
                    }

                    activePeriodId = (periods.find((p) => p.status === 'active') || {}).id || null;

                    const html = periods
                              .map((period) => {
                                        const startDisabled =
                                                  !cfg.canManage ||
                                                  !period.id ||
                                                  period.status === 'active' ||
                                                  period.status === 'completed' ||
                                                  (!!activePeriodId && activePeriodId !== period.id);
                                        const endDisabled = !cfg.canManage || !period.id || period.status !== 'active';
                                        const actions = cfg.canManage
                                                  ? `<div class="period-actions">
                                                                <button type="button" class="btn btn-secondary-soft btn-sm" data-period-action="start" data-period-id="${period.id || ''}" ${startDisabled ? 'disabled' : ''}>Start</button>
                                                                <button type="button" class="btn btn-primary-soft btn-sm" data-period-action="end" data-period-id="${period.id || ''}" ${endDisabled ? 'disabled' : ''}>End</button>
                                                     </div>`
                                                  : '';

                                        return `<div class="period-card">
                                                       <div>
                                                                 <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
                                                                           <div class="period-title">${period.label || `Period ${period.period_index}`}</div>
                                                                           ${statusBadge(period.status || 'pending')}
                                                                 </div>
                                                                 <div class="period-meta">${formatMeta(period)}</div>
                                                       </div>
                                                       ${actions}
                                              </div>`;
                              })
                              .join('');

                    periodList.innerHTML = html;
          }

          async function refreshPeriods() {
                    if (!endpoints.list) return;
                    try {
                              const res = await fetch(endpoints.list, { headers: { Accept: 'application/json' } });
                              const data = await res.json().catch(() => ({}));
                              if (!res.ok || !data.ok) {
                                        throw new Error(data.error || 'Unable to load periods');
                              }
                              periods = data.periods || [];
                              activePeriodId = data.active_period_id || null;
                              renderPeriods();
                    } catch (e) {
                              setFlash('error', e.message || 'Unable to refresh periods');
                    }
          }

          async function postJson(url, payload) {
                    const res = await fetch(url, {
                              method: 'POST',
                              headers: {
                                        'Content-Type': 'application/json',
                                        Accept: 'application/json',
                              },
                              body: JSON.stringify(payload),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                              throw new Error(data.error || 'Request failed');
                    }
                    return data;
          }

          async function startPeriod(id) {
                    if (!endpoints.start) return;
                    const second = currentSecondInput ? Number(currentSecondInput.value) || 0 : 0;
                    try {
                              const data = await postJson(endpoints.start, {
                                        match_id: cfg.matchId,
                                        period_id: id,
                                        current_second: second,
                              });
                              periods = data.periods || periods;
                              activePeriodId = data.active_period_id || id;
                              setFlash('success', 'Period started');
                              renderPeriods();
                    } catch (e) {
                              setFlash('error', e.message || 'Unable to start period');
                    }
          }

          async function endPeriod(id) {
                    if (!endpoints.end) return;
                    const second = currentSecondInput ? Number(currentSecondInput.value) || 0 : 0;
                    try {
                              const data = await postJson(endpoints.end, {
                                        match_id: cfg.matchId,
                                        period_id: id,
                                        current_second: second,
                              });
                              periods = data.periods || periods;
                              activePeriodId = data.active_period_id || null;
                              setFlash('success', 'Period ended');
                              renderPeriods();
                    } catch (e) {
                              setFlash('error', e.message || 'Unable to end period');
                    }
          }

          function fillRow(index, label, start, end) {
                    const row = formRows[index];
                    if (!row) return;
                    const inputs = row.querySelectorAll('input');
                    if (inputs[0]) inputs[0].value = label;
                    if (inputs[1]) inputs[1].value = start !== null && start !== undefined ? start : '';
                    if (inputs[2]) inputs[2].value = end !== null && end !== undefined ? end : '';
          }

          function handlePreset(button) {
                    const label = button.getAttribute('data-label') || '';
                    const start = button.getAttribute('data-start');
                    const end = button.getAttribute('data-end');
                    const startVal = start !== null && start !== undefined && start !== '' ? Number(start) : '';
                    const endVal = end !== null && end !== undefined && end !== '' ? Number(end) : '';

                    const presetOrder = { fh: 0, sh: 1, et1: 2, et2: 3 };
                    const targetIndex = presetOrder[button.getAttribute('data-period-preset')] ?? 0;
                    fillRow(targetIndex, label, startVal, endVal);
          }

          function serializeForm() {
                    const rows = [];
                    formRows.forEach((row, idx) => {
                              const inputs = row.querySelectorAll('input');
                              const label = inputs[0] ? inputs[0].value.trim() : '';
                              if (!label) return;
                              const start = inputs[1] && inputs[1].value !== '' ? Number(inputs[1].value) : null;
                              const end = inputs[2] && inputs[2].value !== '' ? Number(inputs[2].value) : null;
                              rows.push({
                                        period_index: idx,
                                        label,
                                        start_minute: start,
                                        end_minute: end,
                              });
                    });
                    return rows;
          }

          async function savePeriods(event) {
                    if (event) event.preventDefault();
                    if (!endpoints.custom) return;
                    const payload = {
                              match_id: cfg.matchId,
                              periods: serializeForm(),
                    };
                    try {
                              const data = await postJson(endpoints.custom, payload);
                              periods = data.periods || periods;
                              activePeriodId = (periods.find((p) => p.status === 'active') || {}).id || null;
                              setFlash('success', 'Periods updated');
                              renderPeriods();
                    } catch (e) {
                              setFlash('error', e.message || 'Unable to save periods');
                    }
          }

          function bindEvents() {
                    if (presetButtons.length) {
                              presetButtons.forEach((btn) =>
                                        btn.addEventListener('click', () => {
                                                  clearFlash();
                                                  handlePreset(btn);
                                        })
                              );
                    }

                    if (periodForm) {
                              periodForm.addEventListener('submit', savePeriods);
                    }

                    if (periodList) {
                              periodList.addEventListener('click', (e) => {
                                        const btn = e.target.closest('[data-period-action]');
                                        if (!btn) return;
                                        const id = Number(btn.getAttribute('data-period-id'));
                                        if (!id) return;
                                        clearFlash();
                                        const action = btn.getAttribute('data-period-action');
                                        if (action === 'start') {
                                                  startPeriod(id);
                                        } else if (action === 'end') {
                                                  endPeriod(id);
                                        }
                              });
                    }
          }

          function init() {
                    renderPeriods();
                    bindEvents();
          }

          init();
})();
