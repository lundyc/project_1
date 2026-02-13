// JS for League Intelligence Team page (CSP-compliant)

document.addEventListener('DOMContentLoaded', function () {
          // Team select navigation
          var teamSelect = document.querySelector('[data-team-select]');
          if (teamSelect) {
                    teamSelect.addEventListener('change', function (e) {
                              if (e.target.value) {
                                        window.location.href = e.target.value;
                              }
                    });
          }

          // H2H select auto-submit
          var h2hSelect = document.querySelector('[data-h2h-select]');
          if (h2hSelect && h2hSelect.form) {
                    h2hSelect.addEventListener('change', function () {
                              h2hSelect.form.submit();
                    });
          }

          // Pager logic (migrated from inline script)
          function initPager(key) {
                    var body = document.querySelector('[data-pager="' + key + '"]');
                    var controls = document.querySelector('[data-pager-controls="' + key + '"]');
                    if (!body || !controls) return;
                    var perPage = parseInt(body.getAttribute('data-per-page'), 10) || 6;
                    var rows = Array.from(body.querySelectorAll('tr'));
                    var totalPages = Math.max(1, Math.ceil(rows.length / perPage));
                    var currentPage = 1;
                    function render() {
                              rows.forEach(function (row, i) {
                                        row.style.display = (i >= (currentPage - 1) * perPage && i < currentPage * perPage) ? '' : 'none';
                              });
                              var pageEl = controls.querySelector('[data-pager-page]');
                              var totalEl = controls.querySelector('[data-pager-total]');
                              var prevEl = controls.querySelector('[data-pager-prev]');
                              var nextEl = controls.querySelector('[data-pager-next]');
                              if (pageEl) pageEl.textContent = currentPage;
                              if (totalEl) totalEl.textContent = totalPages;
                              if (prevEl) prevEl.disabled = currentPage === 1;
                              if (nextEl) nextEl.disabled = currentPage === totalPages;
                    }
                    var prevBtn = controls.querySelector('[data-pager-prev]');
                    var nextBtn = controls.querySelector('[data-pager-next]');
                    if (prevBtn) {
                              prevBtn.addEventListener('click', function () {
                                        if (currentPage > 1) { currentPage--; render(); }
                              });
                    }
                    if (nextBtn) {
                              nextBtn.addEventListener('click', function () {
                                        if (currentPage < totalPages) { currentPage++; render(); }
                              });
                    }
                    render();
          }
          // Initialize pagers if present
          document.querySelectorAll('[data-pager]').forEach(function (el) {
                    var key = el.getAttribute('data-pager');
                    if (key) initPager(key);
          });
});
