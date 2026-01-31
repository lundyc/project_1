// PitchOriginSelector.js
// Standalone SVG pitch selector for shot origin (top section)
// Usage: new PitchOriginSelector(svgElement, { onChange, initialLocation })

class PitchOriginSelector {
          constructor(svg, { onChange, initialLocation } = {}) {
                    this.svg = svg;
                    this.onChange = onChange;
                    this.state = { start: null };
                    this._bindEvents();
                    this._renderPitch();
                    if (initialLocation) this.setLocation(initialLocation);
          }

          _getNormalizedCoords(evt) {
                    const pt = this.svg.createSVGPoint();
                    pt.x = evt.clientX;
                    pt.y = evt.clientY;
                    const svgP = pt.matrixTransform(this.svg.getScreenCTM().inverse());
                    let x = Math.max(0, Math.min(100, svgP.x)) / 100;
                    let y = Math.max(0, Math.min(100, svgP.y)) / 100;
                    return { x, y };
          }

          _bindEvents() {
                    this.svg.addEventListener('click', (evt) => {
                              const { x, y } = this._getNormalizedCoords(evt);
                              this.state = { start: { x, y } };
                              if (this.onChange) this.onChange({ ...this.state });
                              this._renderPitch();
                    });
          }

          _renderPitch() {
                    while (this.svg.firstChild) this.svg.removeChild(this.svg.firstChild);
                    this.svg.setAttribute('viewBox', '0 -12 100 112');
                    this._drawOptaHalfPitch();
                    const { start } = this.state;
                    if (start) this._drawOriginDot(start, '#e4572e');
          }

          _drawOptaHalfPitch() {
                    // Light background, thin neutral lines, goal implied
                    this._drawRect(0, -12, 100, 112, '#f7f7f7', 'none');
                    this._drawRect(0, 0, 100, 100, 'none', '#b5b5b5', 0.4);
                    this._drawRect(21.1, 0, 57.8, 32, 'none', '#b5b5b5', 0.4);
                    this._drawRect(36.6, 0, 26.8, 10.5, 'none', '#b5b5b5', 0.4);
                    this._drawCircleRaw(50, 12, 1.1, '#b5b5b5', true);
                    this._drawArc(50, 18, 11, Math.PI, 2 * Math.PI, '#b5b5b5', 0.4);
                    this._drawRect(44.5, -3.5, 11, 3.5, 'none', '#e0e0e0', 0.3); // small goal
          }

          _drawOriginDot(pos, color) {
                    const cx = pos.x * 100;
                    const cy = pos.y * 100;
                    const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    c.setAttribute('cx', cx);
                    c.setAttribute('cy', cy);
                    c.setAttribute('r', 1.5);
                    c.setAttribute('stroke', color);
                    c.setAttribute('stroke-width', 0.7);
                    c.setAttribute('fill', '#fff');
                    c.setAttribute('opacity', 1);
                    this.svg.appendChild(c);
          }

          _drawRect(x, y, w, h, fill, stroke, strokeWidth = 0.4) {
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

          _drawCircleRaw(cx, cy, r, color, solid) {
                    const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    c.setAttribute('cx', cx);
                    c.setAttribute('cy', cy);
                    c.setAttribute('r', r);
                    c.setAttribute('stroke', color);
                    c.setAttribute('stroke-width', 0.4);
                    c.setAttribute('fill', solid ? color : 'none');
                    this.svg.appendChild(c);
          }

          _drawArc(cx, cy, r, startAngle, endAngle, color, strokeWidth = 0.4) {
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
                    arc.setAttribute('stroke-width', strokeWidth);
                    arc.setAttribute('fill', 'none');
                    this.svg.appendChild(arc);
          }

          setLocation(start) {
                    if (start) {
                              this.state = { start: { x: Math.max(0, Math.min(1, start.x)), y: Math.max(0, Math.min(1, start.y)) } };
                              this._renderPitch();
                    }
          }

          clearLocation() {
                    this.state = { start: null };
                    this._renderPitch();
          }
}

window.PitchOriginSelector = PitchOriginSelector;
