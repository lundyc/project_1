/**
 * Vanilla JavaScript Component Library
 * Replacement for Bootstrap JS components
 */

(function (window) {
          'use strict';

          // Modal Component
          class Modal {
                    constructor(element) {
                              if (!element) {
                                        throw new Error('Modal element is required');
                              }
                              this.element = element;
                              this.backdrop = null;
                              this.isVisible = false;

                              this._init();
                    }

                    _init() {
                              // Bind close button
                              const closeBtn = this.element.querySelector('[data-bs-dismiss="modal"]');
                              if (closeBtn) {
                                        closeBtn.addEventListener('click', () => this.hide());
                              }

                              // Close on backdrop click
                              this.element.addEventListener('click', (e) => {
                                        if (e.target === this.element) {
                                                  this.hide();
                                        }
                              });

                              // Close on Escape key
                              document.addEventListener('keydown', (e) => {
                                        if (e.key === 'Escape' && this.isVisible) {
                                                  this.hide();
                                        }
                              });
                    }

                    show() {
                              if (this.isVisible) return;

                              this.isVisible = true;
                              this.element.style.display = 'block';
                              this.element.classList.add('show');
                              this.element.setAttribute('aria-hidden', 'false');
                              document.body.style.overflow = 'hidden';

                              // Create backdrop
                              this._showBackdrop();

                              // Trigger animation
                              setTimeout(() => {
                                        this.element.classList.add('modal-fade-in');
                              }, 10);
                    }

                    hide() {
                              if (!this.isVisible) return;

                              this.isVisible = false;
                              this.element.classList.remove('modal-fade-in');

                              setTimeout(() => {
                                        this.element.style.display = 'none';
                                        this.element.classList.remove('show');
                                        this.element.setAttribute('aria-hidden', 'true');
                                        document.body.style.overflow = '';
                                        this._hideBackdrop();
                              }, 300);
                    }

                    toggle() {
                              if (this.isVisible) {
                                        this.hide();
                              } else {
                                        this.show();
                              }
                    }

                    _showBackdrop() {
                              if (!this.backdrop) {
                                        this.backdrop = document.createElement('div');
                                        this.backdrop.className = 'modal-backdrop fade';
                                        document.body.appendChild(this.backdrop);

                                        setTimeout(() => {
                                                  this.backdrop.classList.add('show');
                                        }, 10);

                                        this.backdrop.addEventListener('click', () => this.hide());
                              }
                    }

                    _hideBackdrop() {
                              if (this.backdrop) {
                                        this.backdrop.classList.remove('show');
                                        setTimeout(() => {
                                                  if (this.backdrop && this.backdrop.parentNode) {
                                                            this.backdrop.parentNode.removeChild(this.backdrop);
                                                            this.backdrop = null;
                                                  }
                                        }, 300);
                              }
                    }
          }

          // Tooltip Component
          class Tooltip {
                    constructor(element, options = {}) {
                              if (!element) {
                                        throw new Error('Tooltip element is required');
                              }

                              this.element = element;
                              this.options = {
                                        placement: options.placement || element.getAttribute('data-bs-placement') || 'top',
                                        content: options.content || element.getAttribute('data-bs-title') || element.getAttribute('title') || '',
                                        ...options
                              };

                              this.tooltip = null;
                              this.isVisible = false;

                              // Remove title attribute to prevent native tooltip
                              if (this.element.hasAttribute('title')) {
                                        this.element.removeAttribute('title');
                              }

                              this._init();
                    }

                    _init() {
                              this.element.addEventListener('mouseenter', () => this.show());
                              this.element.addEventListener('mouseleave', () => this.hide());
                              this.element.addEventListener('focus', () => this.show());
                              this.element.addEventListener('blur', () => this.hide());
                    }

                    show() {
                              if (this.isVisible || !this.options.content) return;

                              this.isVisible = true;
                              this._createTooltip();
                              this._positionTooltip();

                              setTimeout(() => {
                                        if (this.tooltip) {
                                                  this.tooltip.classList.add('show');
                                        }
                              }, 10);
                    }

                    hide() {
                              if (!this.isVisible) return;

                              this.isVisible = false;

                              if (this.tooltip) {
                                        this.tooltip.classList.remove('show');
                                        setTimeout(() => {
                                                  if (this.tooltip && this.tooltip.parentNode) {
                                                            this.tooltip.parentNode.removeChild(this.tooltip);
                                                            this.tooltip = null;
                                                  }
                                        }, 300);
                              }
                    }

                    _createTooltip() {
                              this.tooltip = document.createElement('div');
                              this.tooltip.className = `tooltip bs-tooltip-${this.options.placement}`;
                              this.tooltip.setAttribute('role', 'tooltip');
                              this.tooltip.innerHTML = `
                                        <div class="tooltip-arrow"></div>
                                        <div class="tooltip-inner">${this.options.content}</div>
                              `;
                              document.body.appendChild(this.tooltip);
                    }

                    _positionTooltip() {
                              if (!this.tooltip) return;

                              const elementRect = this.element.getBoundingClientRect();
                              const tooltipRect = this.tooltip.getBoundingClientRect();
                              const offset = 8;

                              let top = 0;
                              let left = 0;

                              switch (this.options.placement) {
                                        case 'top':
                                                  top = elementRect.top - tooltipRect.height - offset;
                                                  left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
                                                  break;
                                        case 'bottom':
                                                  top = elementRect.bottom + offset;
                                                  left = elementRect.left + (elementRect.width - tooltipRect.width) / 2;
                                                  break;
                                        case 'left':
                                                  top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
                                                  left = elementRect.left - tooltipRect.width - offset;
                                                  break;
                                        case 'right':
                                                  top = elementRect.top + (elementRect.height - tooltipRect.height) / 2;
                                                  left = elementRect.right + offset;
                                                  break;
                              }

                              this.tooltip.style.position = 'fixed';
                              this.tooltip.style.top = `${top}px`;
                              this.tooltip.style.left = `${left}px`;
                    }

                    dispose() {
                              this.hide();
                              this.element = null;
                              this.options = null;
                    }
          }

          // Dropdown Component
          class Dropdown {
                    constructor(toggle) {
                              if (!toggle) {
                                        throw new Error('Dropdown toggle element is required');
                              }

                              this.toggle = toggle;
                              this.menu = null;
                              this.isOpen = false;

                              this._init();
                    }

                    _init() {
                              // Find associated dropdown menu
                              const menuId = this.toggle.getAttribute('aria-labelledby');
                              if (menuId) {
                                        const parent = this.toggle.closest('.dropdown');
                                        this.menu = parent?.querySelector('.dropdown-menu');
                              } else {
                                        const parent = this.toggle.closest('.dropdown');
                                        this.menu = parent?.querySelector('.dropdown-menu');
                              }

                              if (!this.menu) {
                                        console.warn('Dropdown menu not found for toggle:', this.toggle);
                                        return;
                              }

                              // Bind click event
                              this.toggle.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        this.toggle();
                              });

                              // Close on outside click
                              document.addEventListener('click', (e) => {
                                        if (this.isOpen && !this.toggle.contains(e.target) && !this.menu.contains(e.target)) {
                                                  this.hide();
                                        }
                              });

                              // Close on Escape key
                              document.addEventListener('keydown', (e) => {
                                        if (e.key === 'Escape' && this.isOpen) {
                                                  this.hide();
                                        }
                              });
                    }

                    show() {
                              if (this.isOpen || !this.menu) return;

                              this.isOpen = true;
                              this.menu.classList.add('show');
                              this.toggle.setAttribute('aria-expanded', 'true');
                              this._positionMenu();
                    }

                    hide() {
                              if (!this.isOpen || !this.menu) return;

                              this.isOpen = false;
                              this.menu.classList.remove('show');
                              this.toggle.setAttribute('aria-expanded', 'false');
                    }

                    toggle() {
                              if (this.isOpen) {
                                        this.hide();
                              } else {
                                        this.show();
                              }
                    }

                    _positionMenu() {
                              if (!this.menu) return;

                              const toggleRect = this.toggle.getBoundingClientRect();
                              const menuRect = this.menu.getBoundingClientRect();
                              const viewportHeight = window.innerHeight;

                              // Check if dropdown should open upward
                              const spaceBelow = viewportHeight - toggleRect.bottom;
                              const spaceAbove = toggleRect.top;

                              if (spaceBelow < menuRect.height && spaceAbove > spaceBelow) {
                                        this.menu.style.bottom = '100%';
                                        this.menu.style.top = 'auto';
                              } else {
                                        this.menu.style.top = '100%';
                                        this.menu.style.bottom = 'auto';
                              }
                    }
          }

          // Initialize dropdowns automatically
          function initDropdowns() {
                    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
                              new Dropdown(toggle);
                    });
          }

          // Auto-initialize on DOM ready
          if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initDropdowns);
          } else {
                    initDropdowns();
          }

          // Export to window for backward compatibility with bootstrap namespace
          window.bootstrap = window.bootstrap || {};
          window.bootstrap.Modal = Modal;
          window.bootstrap.Tooltip = Tooltip;
          window.bootstrap.Dropdown = Dropdown;

          // Also export to window directly
          window.Modal = Modal;
          window.Tooltip = Tooltip;
          window.Dropdown = Dropdown;

})(window);
