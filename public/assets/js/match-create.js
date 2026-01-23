/**
 * Match Create - Accordion Navigation & Event Tabs
 * For the Create Match page (not Edit).
 * Features:
 * - Tab navigation (no localStorage persistence)
 * - Form change detection with unsaved changes warning
 * - Loading state management for form submissions
 * - All event/lineup actions disabled until match is created
 */

(function () {
          'use strict';

          // ====================================
          // Configuration
          // ====================================
          const config = window.MatchCreateConfig || {};

          // ====================================
          // Form Change Detection
          // ====================================
          let formDirty = false;
          const detailsForm = document.getElementById('match-details-form');
          const dirtyIndicator = document.getElementById('form-dirty-indicator');

          if (detailsForm) {
                    // Track all form field changes
                    const formInputs = detailsForm.querySelectorAll('input, select, textarea');
                    formInputs.forEach(field => {
                              field.addEventListener('change', () => {
                                        formDirty = true;
                                        if (dirtyIndicator) dirtyIndicator.classList.remove('hidden');
                              });
                              field.addEventListener('input', () => {
                                        formDirty = true;
                                        if (dirtyIndicator) dirtyIndicator.classList.remove('hidden');
                              });
                    });

                    // Warn before navigating away with unsaved changes
                    window.addEventListener('beforeunload', (e) => {
                              if (formDirty) {
                                        e.preventDefault();
                                        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                                        return 'You have unsaved changes. Are you sure you want to leave?';
                              }
                    });

                    // Clear dirty flag on successful submit
                    detailsForm.addEventListener('submit', () => {
                              formDirty = false;
                              if (dirtyIndicator) dirtyIndicator.classList.add('hidden');
                    });
          }

          // ====================================
          // Loading State Management
          // ====================================
          const submitBtn = document.querySelector('.match-details-submit');
          if (submitBtn && detailsForm) {
                    detailsForm.addEventListener('submit', (e) => {
                              submitBtn.disabled = true;
                              const loading = submitBtn.querySelector('.submit-loading');
                              const text = submitBtn.querySelector('.submit-text');
                              if (loading) loading.classList.remove('hidden');
                              if (text) text.classList.add('hidden');
                    });
          }

          // ====================================
          // Tab Navigation (no localStorage)
          // ====================================
          const navItems = document.querySelectorAll('.create-nav-item');
          const sections = document.querySelectorAll('.create-section');
          navItems.forEach(btn => {
                    btn.addEventListener('click', function () {
                              navItems.forEach(b => b.classList.remove('active'));
                              this.classList.add('active');
                              const section = this.getAttribute('data-section');
                              sections.forEach(sec => {
                                        if (sec.id === 'section-' + section) {
                                                  sec.classList.add('active');
                                                  sec.style.display = '';
                                        } else {
                                                  sec.classList.remove('active');
                                                  sec.style.display = 'none';
                                        }
                              });
                              // Update progress bar
                              const num = this.getAttribute('data-section-num');
                              const progressText = document.getElementById('section-progress-text');
                              const progressBar = document.getElementById('section-progress-bar');
                              const sectionName = document.getElementById('section-name');
                              if (progressText) progressText.textContent = num + ' of 4';
                              if (progressBar) progressBar.style.width = (25 * (parseInt(num) || 1)) + '%';
                              if (sectionName) sectionName.textContent = this.textContent.trim();
                    });
          });

          // All event/lineup actions are disabled in create mode (handled in markup)

})();
