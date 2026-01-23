/* global jQuery */
(function ($) {
          const cfg = window.DeskConfig;
          if (!cfg) return;

          const $lockStatus = $('#lockStatus');
          const $status = $('#deskStatus');
          const $btnAcquire = $('#btnAcquireLock');
          const $editable = $('.desk-editable');

          let heartbeatTimer = null;
          let lockOwned = cfg.lock && cfg.lock.is_owner && cfg.lock.fresh && cfg.canEditRole;

          function setMode(mode, info) {
                    if (mode === 'edit') {
                              lockOwned = true;
                              $editable.prop('disabled', false);
                              $lockStatus.removeClass().addClass('text-success fw-semibold').text('Locked by you');
                              $status.removeClass().addClass('text-success').text('Edit mode');
                    } else {
                              lockOwned = false;
                              $editable.prop('disabled', true);
                              const owner = info && info.locked_by ? (info.locked_by.display_name || `User ${info.locked_by.id}`) : 'Unlocked';
                              const label = info && info.locked_by ? `Read-only - locked by ${owner}` : 'Unlocked';
                              $lockStatus.removeClass().addClass(info && info.locked_by ? 'text-warning fw-semibold' : 'text-muted').text(label);
                              $status.removeClass().addClass('text-muted').text('Read-only mode');
                    }
          }

          function startHeartbeat() {
                    if (heartbeatTimer) clearInterval(heartbeatTimer);
                    if (!lockOwned) return;
                    heartbeatTimer = setInterval(() => {
                              $.post(cfg.endpoints.lockHeartbeat, { match_id: cfg.matchId })
                                        .done((res) => {
                                                  if (!res.ok) {
                                                            clearInterval(heartbeatTimer);
                                                            heartbeatTimer = null;
                                                            setMode('readonly', res);
                                                  }
                                        })
                                        .fail(() => {
                                                  clearInterval(heartbeatTimer);
                                                  heartbeatTimer = null;
                                                  setMode('readonly', null);
                                        });
                    }, 10000);
          }

          function acquireLock() {
                    $.post(cfg.endpoints.lockAcquire, { match_id: cfg.matchId })
                              .done((res) => {
                                        if (res.ok && res.mode === 'edit') {
                                                  setMode('edit', res);
                                                  startHeartbeat();
                                        } else {
                                                  setMode('readonly', res);
                                        }
                              })
                              .fail(() => setMode('readonly', null));
          }

          function init() {
                    setMode(lockOwned ? 'edit' : 'readonly', { locked_by: cfg.lock ? cfg.lock.locked_by : null });
                    if (lockOwned) startHeartbeat();
                    if ($btnAcquire.length) {
                              $btnAcquire.on('click', function (e) {
                                        e.preventDefault();
                                        acquireLock();
                              });
                    }
          }

          $(init);
})(jQuery);
