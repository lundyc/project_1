// league-intelligence-trends.js
// Renders line charts for points and goal difference trends using Chart.js

document.addEventListener('DOMContentLoaded', function () {
          if (typeof window.Chart === 'undefined') return;

          // Points Trend
          var pointsCtx = document.getElementById('pointsTrendChart');
          if (pointsCtx && window.pointsTrendData) {
                    new Chart(pointsCtx, {
                              type: 'line',
                              data: {
                                        labels: window.pointsTrendLabels || window.pointsTrendData.map((_, i) => i + 1),
                                        datasets: [{
                                                  label: 'Points',
                                                  data: window.pointsTrendData,
                                                  borderColor: '#6366f1',
                                                  backgroundColor: 'rgba(99,102,241,0.1)',
                                                  fill: true,
                                                  tension: 0.3,
                                                  pointRadius: 2,
                                        }]
                              },
                              options: {
                                        responsive: true,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                                  x: { display: true, title: { display: false } },
                                                  y: { display: true, title: { display: false } }
                                        }
                              }
                    });
          }

          // Goal Difference Trend
          var goalCtx = document.getElementById('goalTrendChart');
          if (goalCtx && window.goalTrendData) {
                    new Chart(goalCtx, {
                              type: 'line',
                              data: {
                                        labels: window.goalTrendLabels || window.goalTrendData.map((_, i) => i + 1),
                                        datasets: [{
                                                  label: 'Goal Difference',
                                                  data: window.goalTrendData,
                                                  borderColor: '#10b981',
                                                  backgroundColor: 'rgba(16,185,129,0.1)',
                                                  fill: true,
                                                  tension: 0.3,
                                                  pointRadius: 2,
                                        }]
                              },
                              options: {
                                        responsive: true,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                                  x: { display: true, title: { display: false } },
                                                  y: { display: true, title: { display: false } }
                                        }
                              }
                    });
          }
});
