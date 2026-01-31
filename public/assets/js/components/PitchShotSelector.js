// PitchShotSelector.js
// Reusable SVG football pitch shot selector component
// Usage: new PitchShotSelector(svgElement, { onChange, initialLocation })

class PitchShotSelector {
          constructor(svg, { onChange, initialLocation } = {}) {
                    this.svg = svg;
                    this.onChange = onChange;
                    this.state = { start: null, end: null };
                    this._bindEvents();
                    this._renderPitch();
                    if (initialLocation) this.setLocation(initialLocation);
          }

          // Convert client click to normalized SVG coordinates (0-1)
          _getNormalizedCoords(evt) {
                    const pt = this.svg.createSVGPoint();
                    pt.x = evt.clientX;
                    pt.y = evt.clientY;
                    const svgP = pt.matrixTransform(this.svg.getScreenCTM().inverse());
                    // Clamp to [0, 100] then normalize
                    let x = Math.max(0, Math.min(100, svgP.x)) / 100;
                    let y = Math.max(0, Math.min(100, svgP.y)) / 100;
                    return { x, y };
          }

          _bindEvents() {
                    this.svg.addEventListener('click', (evt) => {
                              const { x, y } = this._getNormalizedCoords(evt);
                              if (!this.state.start) {
                                        this.state = { start: { x, y }, end: null };
                              } else if (!this.state.end) {
                                        this.state = { ...this.state, end: { x, y } };
                                        if (this.onChange) this.onChange({ ...this.state });
                              } else {
                                        // Third click resets
                                        this.state = { start: { x, y }, end: null };
                              }
                              this._renderPitch();
                    });
          }

          // Draw the pitch and shot visuals (half-pitch, broadcast style)
          _renderPitch() {
                    // Clear SVG
                    while (this.svg.firstChild) this.svg.removeChild(this.svg.firstChild);
                    // Set up SVG viewBox for half-pitch (0 -12 100 112)
                    this.svg.setAttribute('viewBox', '0 -12 100 112');
                    // Draw pitch
                    this._drawHalfPitch();
                    // Draw shot overlay
                    const { start, end } = this.state;
                    if (start && end) {
                              // Determine outcome color (default: orange)
                              const outcome = this.state.outcome || 'on_target';
                              const colors = {
                                        goal: '#a1121a', // dark red
                                        on_target: '#ff9800', // orange
                                        off_target: '#ffe066', // yellow
                                        blocked: '#b0b0b0'  // grey
                              };
                              const color = colors[outcome] || '#ff9800';
                              this._drawShotArrow(start, end, color);
                              this._drawBallMarker(start, color);
                    } else if (start) {
                              // Show ball marker at start if only start exists
                              this._drawBallMarker(start, '#ff9800');
                    }
          }

          // Draw a professional half-pitch (viewBox 0 -12 100 112)
          _drawHalfPitch() {
                    // Pitch background
                    this._drawRect(0, -12, 100, 112, '#b6e388', 'none'); // light green
                    // Penalty area
                    this._drawRect(21.1, 0, 57.8, 18, 'none', '#fff');
                    // 6-yard box
                    this._drawRect(36.5, 0, 27, 6, 'none', '#fff');
                    // Penalty spot
                    this._drawCircleRaw(50, 12, 0.8, '#fff', true);
                    // Centre arc (top of penalty area)
                    this._drawArc(50, 18, 9.15, Math.PI, 2 * Math.PI, '#fff');
                    // Touchlines
                    this._drawLine(0, 0, 0, 100, '#fff', 2);
                    this._drawLine(100, 0, 100, 100, '#fff', 2);
                    this._drawLine(0, 100, 100, 100, '#fff', 2);
                    // Goal line
                    this._drawLine(0, 0, 100, 0, '#fff', 2.5);
                    // Goal posts
                    this._drawLine(44, -4, 44, 0, '#e0e0e0', 2.5);
                    this._drawLine(56, -4, 56, 0, '#e0e0e0', 2.5);
                    this._drawLine(44, -4, 56, -4, '#e0e0e0', 2.5);
                    // Net (simple lines)
                    for (let i = 0; i <= 4; i++) {
                              const x = 44 + (i * 3);
                              this._drawLine(x, -4, x, -2, '#e0e0e0', 1);
                    }
                    for (let i = 0; i <= 2; i++) {
                              const y = -4 + (i * 1);
                              this._drawLine(44, y, 56, y, '#e0e0e0', 1);
                    }
          }

          // Draw a filled or outlined circle at absolute SVG coords
          _drawCircleRaw(cx, cy, r, color, solid) {
                    const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    c.setAttribute('cx', cx);
                    c.setAttribute('cy', cy);
                    c.setAttribute('r', r);
                    c.setAttribute('stroke', color);
                    c.setAttribute('stroke-width', 1.2);
                    c.setAttribute('fill', solid ? color : 'none');
                    this.svg.appendChild(c);
          }

          // Draw an arc (for penalty arc)
          _drawArc(cx, cy, r, startAngle, endAngle, color) {
                    const x1 = cx + r * Math.cos(startAngle);
                    const y1 = cy + r * Math.sin(startAngle);
                    const x2 = cx + r * Math.cos(endAngle);
                    const y2 = cy + r * Math.sin(endAngle);
                    const arc = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    const d = [
                              'M', x1, y1,
                              'A', r, r, 0, 0, 1, x2, y2
                    ].join(' ');
                    arc.setAttribute('d', d);
                    arc.setAttribute('stroke', color);
                    arc.setAttribute('stroke-width', 1.2);
                    arc.setAttribute('fill', 'none');
                    this.svg.appendChild(arc);
          }

          // Draw a ball marker at normalized pos (0-1)
          _drawBallMarker(pos, color) {
                    const cx = pos.x * 100;
                    const cy = pos.y * 100;
                    const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    c.setAttribute('cx', cx);
                    c.setAttribute('cy', cy);
                    c.setAttribute('r', 2.2);
                    c.setAttribute('stroke', '#fff');
                    c.setAttribute('stroke-width', 1.3);
                    c.setAttribute('fill', color);
                    c.setAttribute('opacity', 0.98);
                    c.style.filter = 'drop-shadow(0 0 1.5px #fff8)';
                    this.svg.appendChild(c);
          }

          // Draw a shot arrow with SVG marker arrowhead
          _drawShotArrow(start, end, color) {
                    // Define marker for arrowhead if not present
                    if (!this.svg.querySelector('marker#shotArrowHead')) {
                              const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
                              marker.setAttribute('id', 'shotArrowHead');
                              marker.setAttribute('markerWidth', '7');
                              marker.setAttribute('markerHeight', '7');
                              marker.setAttribute('refX', '3.5');
                              marker.setAttribute('refY', '3.5');
                              marker.setAttribute('orient', 'auto');
                              marker.setAttribute('markerUnits', 'strokeWidth');
                              const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                              arrow.setAttribute('points', '0,0 7,3.5 0,7 2.1,3.5');
                              arrow.setAttribute('fill', color);
                              arrow.setAttribute('opacity', '0.85');
                              marker.appendChild(arrow);
                              // Add marker to <defs>
                              let defs = this.svg.querySelector('defs');
                              if (!defs) {
                                        defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                                        this.svg.insertBefore(defs, this.svg.firstChild);
                              }
                              defs.appendChild(marker);
                    }
                    // Draw the arrow line
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', start.x * 100);
                    line.setAttribute('y1', start.y * 100);
                    line.setAttribute('x2', end.x * 100);
                    line.setAttribute('y2', end.y * 100);
                    line.setAttribute('stroke', color);
                    line.setAttribute('stroke-width', 2.1);
                    line.setAttribute('stroke-linecap', 'round');
                    line.setAttribute('opacity', '0.82');
                    line.setAttribute('marker-end', 'url(#shotArrowHead)');
                    this.svg.appendChild(line);
          }

          _drawRect(x, y, w, h, fill, stroke) {
                    const r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    r.setAttribute('x', x);
                    r.setAttribute('y', y);
                    r.setAttribute('width', w);
                    r.setAttribute('height', h);
                    r.setAttribute('fill', fill);
                    r.setAttribute('stroke', stroke);
                    r.setAttribute('stroke-width', 0.7);
                    this.svg.appendChild(r);
          }

          _drawLine(x1, y1, x2, y2, stroke, width = 1) {
                    const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    l.setAttribute('x1', x1);
                    l.setAttribute('y1', y1);
                    l.setAttribute('x2', x2);
                    l.setAttribute('y2', y2);
                    l.setAttribute('stroke', stroke);
                    l.setAttribute('stroke-width', width);
                    this.svg.appendChild(l);
          }

          _drawCircle(pos, r, color, solid) {
                    _drawCircle(pos, r, border, solid, fill, opacity = 1) {
                              const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                              c.setAttribute('cx', pos.x * 100);
                              c.setAttribute('cy', pos.y * 100);
                              c.setAttribute('r', r);
                              c.setAttribute('stroke', border);
                              c.setAttribute('stroke-width', 1.5);
                              c.setAttribute('fill', solid ? fill : 'none');
                              c.setAttribute('opacity', opacity);
                              c.style.transition = 'opacity 0.25s cubic-bezier(.4,1.6,.6,1)';
                              this.svg.appendChild(c);
                    }

                    // Draw arrow from start to end
                    _drawArrow(start, end, color) {
                              // Smooth line with subtle fade
                              const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                              line.setAttribute('x1', start.x * 100);
                              line.setAttribute('y1', start.y * 100);
                              line.setAttribute('x2', end.x * 100);
                              line.setAttribute('y2', end.y * 100);
                              line.setAttribute('stroke', color);
                              line.setAttribute('stroke-width', 2.1);
                              line.setAttribute('stroke-linecap', 'round');
                              line.setAttribute('opacity', '0.88');
                              line.style.transition = 'opacity 0.25s cubic-bezier(.4,1.6,.6,1)';
                              this.svg.appendChild(line);
                              // Arrowhead
                              const dx = end.x - start.x, dy = end.y - start.y;
                              const len = Math.sqrt(dx * dx + dy * dy);
                              if (len < 0.01) return;
                              const ux = dx / len, uy = dy / len;
                              const size = 2.5;
                              const px = end.x * 100, py = end.y * 100;
                              const left = { x: px - size * (ux + uy / 2), y: py - size * (uy - ux / 2) };
                              const right = { x: px - size * (ux - uy / 2), y: py - size * (uy + ux / 2) };
                              const ah = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
                              ah.setAttribute('points', `${px},${py} ${left.x},${left.y} ${right.x},${right.y} ${px},${py}`);
                              ah.setAttribute('stroke', color);
                              ah.setAttribute('stroke-width', 1.3);
                              ah.setAttribute('fill', 'none');
                              ah.setAttribute('opacity', '0.88');
                              ah.style.transition = 'opacity 0.25s cubic-bezier(.4,1.6,.6,1)';
                              this.svg.appendChild(ah);
                    }

                    // Public API
                    setLocation({ start, end }) {
                              if (start && end) {
                                        this.state = {
                                                  start: { x: Math.max(0, Math.min(1, start.x)), y: Math.max(0, Math.min(1, start.y)) },
                                                  end: { x: Math.max(0, Math.min(1, end.x)), y: Math.max(0, Math.min(1, end.y)) }
                                        };
                                        this._renderPitch();
                              }
                    }

                    clearLocation() {
                              this.state = { start: null, end: null };
                              this._renderPitch();
                    }
          }

// Export for use in browser
window.PitchShotSelector = PitchShotSelector;
