// GoalTargetSelector.js
// Standalone SVG goal/net selector for shot target (bottom section)
// Usage: new GoalTargetSelector(svgElement, { onChange, initialLocation })

class GoalTargetSelector {
          constructor(svg, { onChange, initialLocation } = {}) {
                    this.svg = svg;
                    this.onChange = onChange;
                    this.state = { end: null };
                    this._bindEvents();
                    this._renderGoal();
                    if (initialLocation) this.setLocation(initialLocation);
          }

          // Normalize click to goal area (x: 0-1 left-right, y: 0-1 crossbar to ground)
          _getNormalizedCoords(evt) {
                    const pt = this.svg.createSVGPoint();
                    pt.x = evt.clientX;
                    pt.y = evt.clientY;
                    const svgP = pt.matrixTransform(this.svg.getScreenCTM().inverse());
                    // Goal area: x 0-100, y 0-50 (net below crossbar)
                    let x = Math.max(0, Math.min(100, svgP.x)) / 100;
                    let y = Math.max(0, Math.min(50, svgP.y)) / 50;
                    return { x, y };
          }

          _bindEvents() {
                    this.svg.addEventListener('click', (evt) => {
                              const { x, y } = this._getNormalizedCoords(evt);
                              this.state = { end: { x, y } };
                              if (this.onChange) this.onChange({ ...this.state });
                              this._renderGoal();
                    });
          }

          _renderGoal() {
                    while (this.svg.firstChild) this.svg.removeChild(this.svg.firstChild);
                    this.svg.setAttribute('viewBox', '0 0 100 50');
                    this._drawOptaGoal();
                    const { end } = this.state;
                    if (end) this._drawTargetDot(end, '#e4572e');
          }

          _drawOptaGoal() {
                    // Large, clear goal/net, minimal pitch context
                    // Goal frame
                    this._drawRect(0, 0, 100, 8, 'none', '#b5b5b5', 0.7); // crossbar + posts
                    // Net grid (verticals)
                    for (let i = 1; i < 10; i++) {
                              this._drawLine(i * 10, 8, i * 10, 50, '#e0e0e0', 0.3);
                    }
                    // Net grid (horizontals)
                    for (let j = 1; j < 6; j++) {
                              this._drawLine(0, 8 + j * 7, 100, 8 + j * 7, '#e0e0e0', 0.3);
                    }
                    // Posts (thicker)
                    this._drawLine(0, 0, 0, 50, '#b5b5b5', 0.7);
                    this._drawLine(100, 0, 100, 50, '#b5b5b5', 0.7);
                    // Crossbar (thicker)
                    this._drawLine(0, 0, 100, 0, '#b5b5b5', 0.7);
                    // Ground line (thin)
                    this._drawLine(0, 50, 100, 50, '#b5b5b5', 0.3);
          }

          _drawTargetDot(pos, color) {
                    const cx = pos.x * 100;
                    const cy = pos.y * 50;
                    const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    c.setAttribute('cx', cx);
                    c.setAttribute('cy', cy);
                    c.setAttribute('r', 2);
                    c.setAttribute('stroke', color);
                    c.setAttribute('stroke-width', 0.8);
                    c.setAttribute('fill', '#fff');
                    c.setAttribute('opacity', 1);
                    this.svg.appendChild(c);
          }

          _drawRect(x, y, w, h, fill, stroke, strokeWidth = 0.7) {
                    const r = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    r.setAttribute('x', x);
                    r.setAttribute('y', y);
                    r.setAttribute('width', w);
                    r.setAttribute('height', h);
                    r.setAttribute('fill', fill);
                    r.setAttribute('stroke', stroke);
                    r.setAttribute('stroke-width', stroke !== 'none' ? strokeWidth : 0);
                    this.svg.appendChild(r);
          }

          _drawLine(x1, y1, x2, y2, stroke, width = 0.3) {
                    const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    l.setAttribute('x1', x1);
                    l.setAttribute('y1', y1);
                    l.setAttribute('x2', x2);
                    l.setAttribute('y2', y2);
                    l.setAttribute('stroke', stroke);
                    l.setAttribute('stroke-width', width);
                    this.svg.appendChild(l);
          }

          setLocation(end) {
                    if (end) {
                              this.state = { end: { x: Math.max(0, Math.min(1, end.x)), y: Math.max(0, Math.min(1, end.y)) } };
                              this._renderGoal();
                    }
          }

          clearLocation() {
                    this.state = { end: null };
                    this._renderGoal();
          }
}

window.GoalTargetSelector = GoalTargetSelector;
