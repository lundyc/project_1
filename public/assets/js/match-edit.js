/**
 * Match Edit - Accordion Navigation & Event Tabs
 * Features:
 * - Tab navigation with localStorage persistence
 * - Form change detection with unsaved changes warning
 * - Loading state management for form submissions
 * - Player lineup, match events, and substitution management
 */

(function () {
       'use strict';

       // ====================================
       // Configuration
       // ====================================
       const config = window.MatchEditConfig || {};

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
                     const submitText = submitBtn.querySelector('.submit-text');
                     const submitLoading = submitBtn.querySelector('.submit-loading');

                     submitBtn.disabled = true;
                     if (submitText) submitText.classList.add('hidden');
                     if (submitLoading) submitLoading.classList.remove('hidden');

                     // Re-enable after delay if something goes wrong
                     setTimeout(() => {
                            if (submitBtn.disabled) {
                                   submitBtn.disabled = false;
                                   if (submitText) submitText.classList.remove('hidden');
                                   if (submitLoading) submitLoading.classList.add('hidden');
                            }
                     }, 5000);
              });
       }

       // ====================================
       // Field Validation with Visual Feedback & Inline Errors
       // ====================================
       function validateField(field) {
              if (!field) return;

              const isValid = field.checkValidity();
              let errorContainer = field.parentElement.querySelector('.field-error');

              // Remove existing validation classes
              field.classList.remove('border-rose-500', 'border-emerald-500');

              if (field.value === '' && !field.required) {
                     field.classList.remove('border-rose-500', 'border-emerald-500');
                     if (errorContainer) errorContainer.remove();
              } else if (field.value === '' && field.required) {
                     field.classList.add('border-rose-500');
                     field.classList.remove('border-emerald-500');
                     showFieldError(field, 'This field is required');
              } else if (isValid) {
                     field.classList.add('border-emerald-500');
                     field.classList.remove('border-rose-500');
                     if (errorContainer) errorContainer.remove();
              } else {
                     field.classList.add('border-rose-500');
                     field.classList.remove('border-emerald-500');
                     showFieldError(field, field.validationMessage || 'Invalid input');
              }
       }

       function showFieldError(field, message) {
              let errorContainer = field.parentElement.querySelector('.field-error');

              if (!errorContainer) {
                     errorContainer = document.createElement('div');
                     errorContainer.className = 'field-error text-xs text-rose-400 mt-1 flex items-center gap-1';
                     field.parentElement.appendChild(errorContainer);
              }

              errorContainer.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i><span>${message}</span>`;
       }

       // Add real-time validation to form fields
       if (detailsForm) {
              const formInputs = detailsForm.querySelectorAll('input[required], select[required], textarea[required]');
              formInputs.forEach(field => {
                     field.addEventListener('blur', () => validateField(field));
                     field.addEventListener('change', () => validateField(field));
                     field.addEventListener('input', () => validateField(field));
              });

              // Validate on form submit
              detailsForm.addEventListener('submit', (e) => {
                     let hasErrors = false;
                     formInputs.forEach(field => {
                            validateField(field);
                            if (!field.checkValidity()) hasErrors = true;
                     });

                     if (hasErrors) {
                            e.preventDefault();
                            showTooltip('Please fix validation errors before saving', 'error');
                            // Scroll to first error
                            const firstError = detailsForm.querySelector('.border-rose-500');
                            if (firstError) {
                                   firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                   firstError.focus();
                            }
                     }
              });
       }

       // Section Navigation
       const navItems = document.querySelectorAll('.edit-nav-item');
       const sections = document.querySelectorAll('.edit-section');

       navItems.forEach(item => {
              item.addEventListener('click', function () {
                     const sectionId = this.getAttribute('data-section');
                     const sectionNum = this.getAttribute('data-section-num');

                     // Update nav active state and ARIA attributes
                     navItems.forEach(nav => {
                            nav.classList.remove('active');
                            nav.setAttribute('aria-selected', 'false');
                     });
                     this.classList.add('active');
                     this.setAttribute('aria-selected', 'true');

                     // Update progress indicator
                     const progressBar = document.getElementById('section-progress-bar');
                     const progressText = document.getElementById('section-progress-text');
                     const sectionName = document.getElementById('section-name');

                     if (progressBar && sectionNum) {
                            const percentage = (parseInt(sectionNum) / 4) * 100;
                            progressBar.style.width = percentage + '%';
                     }
                     if (progressText && sectionNum) {
                            progressText.textContent = `${sectionNum} of 4`;
                     }
                     if (sectionName) {
                            sectionName.textContent = this.textContent.trim();
                     }

                     // Show selected section, hide others
                     sections.forEach(section => {
                            if (section.id === `section-${sectionId}`) {
                                   section.style.display = 'block';
                                   section.classList.add('active');
                                   // Smooth scroll to section
                                   section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            } else {
                                   section.style.display = 'none';
                                   section.classList.remove('active');
                            }
                     });

                     // Save active section to localStorage
                     localStorage.setItem('matchEditActiveSection', sectionId);
              });
       });

       // Event Tabs
       const eventTabs = document.querySelectorAll('.event-tab');
       const eventTabContents = document.querySelectorAll('.event-tab-content');

       eventTabs.forEach(tab => {
              tab.addEventListener('click', function () {
                     const tabId = this.getAttribute('data-tab');

                     // Update tab active state
                     eventTabs.forEach(t => t.classList.remove('active'));
                     this.classList.add('active');

                     // Show selected tab content
                     eventTabContents.forEach(content => {
                            if (content.id === `tab-${tabId}`) {
                                   content.style.display = 'block';
                                   content.classList.add('active');
                            } else {
                                   content.style.display = 'none';
                                   content.classList.remove('active');
                            }
                     });

                     // Save active tab to localStorage
                     localStorage.setItem('matchEditActiveTab', tabId);
              });
       });

       // Restore last active section and tab from localStorage
       const savedSection = localStorage.getItem('matchEditActiveSection');
       const savedTab = localStorage.getItem('matchEditActiveTab');

       if (savedSection) {
              const targetNav = document.querySelector(`.edit-nav-item[data-section="${savedSection}"]`);
              if (targetNav) {
                     targetNav.click();
              }
       }

       if (savedTab) {
              const targetTab = document.querySelector(`.event-tab[data-tab="${savedTab}"]`);
              if (targetTab) {
                     targetTab.click();
              }
       }

       console.log('[match-edit] Navigation initialized');

       // ====================================
       // Keyboard Shortcuts
       // ====================================
       document.addEventListener('keydown', (e) => {
              // Ctrl+S / Cmd+S to save form
              if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                     e.preventDefault();
                     if (detailsForm && !submitBtn?.disabled) {
                            detailsForm.requestSubmit();
                            showTooltip('Saving match...', 'success');
                     }
              }

              // Ctrl+1-4 to switch sections
              if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '4') {
                     e.preventDefault();
                     const sectionMap = { '1': 'details', '2': 'video', '3': 'lineups', '4': 'events' };
                     const targetNav = document.querySelector(`.edit-nav-item[data-section="${sectionMap[e.key]}"]`);
                     if (targetNav) targetNav.click();
              }

              // ESC to close modals
              if (e.key === 'Escape') {
                     const openModal = document.querySelector('.modal[style*="display: block"]');
                     if (openModal) {
                            closeModal(openModal.id);
                     }
              }
       });

       // ====================================
       // Tooltip System
       // ====================================
       function showTooltip(message, type = 'info') {
              const tooltip = document.createElement('div');
              tooltip.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-sm max-w-md animate-slideIn ${type === 'success' ? 'bg-emerald-900/90 border border-emerald-700 text-emerald-200' :
                     type === 'error' ? 'bg-rose-900/90 border border-rose-700 text-rose-200' :
                            'bg-blue-900/90 border border-blue-700 text-blue-200'
                     }`;
              tooltip.textContent = message;
              document.body.appendChild(tooltip);

              setTimeout(() => {
                     tooltip.style.opacity = '0';
                     tooltip.style.transform = 'translateX(400px)';
                     setTimeout(() => tooltip.remove(), 300);
              }, 3000);
       }

       // Add keyboard shortcut hints to buttons
       function addKeyboardHints() {
              if (submitBtn) {
                     submitBtn.setAttribute('title', 'Save changes (Ctrl+S)');
              }

              navItems.forEach((item, index) => {
                     item.setAttribute('title', `Switch to this section (Ctrl+${index + 1})`);
              });

              // Add ESC hint to modal close buttons
              document.querySelectorAll('.modal .close, .close-modal').forEach(btn => {
                     btn.setAttribute('title', 'Close (ESC)');
              });
       }

       addKeyboardHints();

       // ====================================
       // Stat Animation on Update
       // ====================================
       function animateStat(element) {
              if (!element) return;
              element.classList.add('stat-updated');
              setTimeout(() => {
                     element.classList.remove('stat-updated');
              }, 500);
       }

       // Observe changes to event lists and animate stats
       const eventObserver = new MutationObserver(() => {
              // Animate stat counters when events change
              document.querySelectorAll('.stat-value').forEach(stat => {
                     animateStat(stat);
              });
       });

       // Observe the events section for changes
       const eventsSection = document.getElementById('section-events');
       if (eventsSection) {
              eventObserver.observe(eventsSection, {
                     childList: true,
                     subtree: true
              });
       }

       // ====================================
       // Video Source Toggle (Upload vs VEO vs No Video)
       // VEO Download button logic
       // Fix: Ensure DOM elements are available before attaching listeners
       document.addEventListener('DOMContentLoaded', function () {
              const veoDownloadBtn = document.getElementById('veoDownloadBtn');
              const veoDownloadStatus = document.getElementById('veoDownloadStatus');
              const videoUrlInput = document.getElementById('video_url_input');
              if (veoDownloadBtn && videoUrlInput && window.MatchEditConfig && window.MatchEditConfig.matchId) {
                     veoDownloadBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            const veo_url = videoUrlInput.value.trim();
                            const match_id = window.MatchEditConfig.matchId;
                            if (!veo_url) {
                                   veoDownloadStatus.textContent = 'Please enter a VEO match URL.';
                                   return;
                            }
                            veoDownloadStatus.textContent = 'Starting download...';
                            veoDownloadBtn.disabled = true;
                            // Use the new public-facing endpoint
                            fetch('/api/video_veo.php', {
                                   method: 'POST',
                                   headers: { 'Content-Type': 'application/json' },
                                   body: JSON.stringify({ match_id, veo_url })
                            })
                                   .then(resp => resp.json())
                                   .then(data => {
                                          if (data.ok && (data.status === 'starting' || data.status === 'started')) {
                                                 veoDownloadStatus.textContent = 'Download started. Please wait...';
                                                 pollVeoDownloadProgress(match_id);
                                          } else {
                                                 veoDownloadStatus.textContent = data.error || 'Failed to start download.';
                                                 veoDownloadBtn.disabled = false;
                                          }
                                   })
                                   .catch(() => {
                                          veoDownloadStatus.textContent = 'Network error.';
                                          veoDownloadBtn.disabled = false;
                                   });
                     });
              }
       });

       // Poll for VEO download progress
       function pollVeoDownloadProgress(matchId) {
              let attempts = 0;
              function poll() {
                     fetch(`/api/veo-progress.php?match_id=${matchId}`)
                            .then(resp => resp.json())
                            .then(data => {
                                   if (data.ok && data.progress !== undefined) {
                                          veoDownloadStatus.textContent = `Download progress: ${data.progress}%`;
                                          if (data.progress < 100) {
                                                 setTimeout(poll, 2000);
                                          } else {
                                                 veoDownloadStatus.textContent = 'Download complete!';
                                                 veoDownloadBtn.disabled = false;
                                                 // Optionally update video file select here
                                          }
                                   } else {
                                          veoDownloadStatus.textContent = data.error || 'Download failed.';
                                          veoDownloadBtn.disabled = false;
                                   }
                            })
                            .catch(() => {
                                   attempts++;
                                   if (attempts < 10) {
                                          setTimeout(poll, 3000);
                                   } else {
                                          veoDownloadStatus.textContent = 'Download status unavailable.';
                                          veoDownloadBtn.disabled = false;
                                   }
                            });
              }
              poll();
       }
       // ====================================
       const videoModeUpload = document.getElementById('videoTypeUpload');
       const videoModeVeo = document.getElementById('videoTypeVeo');
       const videoModeNone = document.getElementById('videoTypeNone');
       const videoFileSelect = document.getElementById('video_file_select');
       const videoUrlInput = document.getElementById('video_url_input');
       const videoInputsSection = document.getElementById('videoInputsSection');

       // Dropzone and Upload Now logic
       const videoUploadDropzone = document.getElementById('video-upload-dropzone');
       const videoUploadForm = document.getElementById('videoUploadForm');
       const videoFileInput = document.getElementById('videoFileInput');
       const uploadNowBtn = document.getElementById('uploadNowBtn');
       const uploadProgressBar = document.getElementById('uploadProgressBar');
       const uploadProgress = document.getElementById('uploadProgress');
       const uploadStatus = document.getElementById('uploadStatus');
       const videoUploadPreview = document.getElementById('videoUploadPreview');

       // Show/hide dropzone based on radio
       function syncDropzoneUI() {
              if (videoUploadDropzone) {
                     videoUploadDropzone.style.display = videoModeUpload?.checked ? 'block' : 'none';
              }
       }
       if (videoModeUpload) {
              videoModeUpload.addEventListener('change', syncDropzoneUI);
              syncDropzoneUI();
       }

       // Drag & drop support
       if (videoUploadDropzone && videoFileInput) {
              videoUploadDropzone.addEventListener('dragover', function (e) {
                     e.preventDefault();
                     videoUploadDropzone.style.background = '#334155';
              });
              videoUploadDropzone.addEventListener('dragleave', function (e) {
                     e.preventDefault();
                     videoUploadDropzone.style.background = '#1e293b';
              });
              videoUploadDropzone.addEventListener('drop', function (e) {
                     e.preventDefault();
                     videoUploadDropzone.style.background = '#1e293b';
                     if (e.dataTransfer.files.length > 0) {
                            videoFileInput.files = e.dataTransfer.files;
                            showFilePreview(videoFileInput.files[0]);
                     }
              });
              videoFileInput.addEventListener('change', function () {
                     if (videoFileInput.files.length > 0) {
                            showFilePreview(videoFileInput.files[0]);
                     }
              });
       }

       function showFilePreview(file) {
              if (!file) return;
              videoUploadPreview.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
       }

       // Upload Now button logic
       if (uploadNowBtn && videoUploadForm && videoFileInput) {
              uploadNowBtn.addEventListener('click', function (e) {
                     e.preventDefault();
                     if (!videoFileInput.files.length) {
                            uploadStatus.textContent = 'Please select a video file.';
                            return;
                     }
                     const file = videoFileInput.files[0];
                     const formData = new FormData();
                     formData.append('video_file', file);
                     uploadProgressBar.style.display = 'block';
                     uploadProgress.style.width = '0%';
                     uploadStatus.textContent = 'Uploading...';

                     // AJAX upload
                     const xhr = new XMLHttpRequest();
                     xhr.open('POST', videoUploadForm.action, true);
                     xhr.upload.onprogress = function (e) {
                            if (e.lengthComputable) {
                                   const percent = Math.round((e.loaded / e.total) * 100);
                                   uploadProgress.style.width = percent + '%';
                            }
                     };
                     xhr.onload = function () {
                            if (xhr.status === 200) {
                                   let resp;
                                   try { resp = JSON.parse(xhr.responseText); } catch { resp = {}; }
                                   if (resp.ok && resp.path) {
                                          uploadStatus.textContent = 'Upload complete!';
                                          uploadProgress.style.width = '100%';
                                          // Set the raw video select to the new file
                                          if (videoFileSelect) {
                                                 const opt = document.createElement('option');
                                                 opt.value = resp.path;
                                                 opt.textContent = resp.filename || resp.path.split('/').pop();
                                                 opt.selected = true;
                                                 videoFileSelect.appendChild(opt);
                                                 videoFileSelect.value = resp.path;
                                          }
                                          // Mark form dirty
                                          formDirty = true;
                                   } else {
                                          uploadStatus.textContent = resp.error || 'Upload failed.';
                                          uploadProgressBar.style.display = 'none';
                                   }
                            } else {
                                   uploadStatus.textContent = 'Upload failed. Server error.';
                                   uploadProgressBar.style.display = 'none';
                            }
                     };
                     xhr.onerror = function () {
                            uploadStatus.textContent = 'Upload failed. Network error.';
                            uploadProgressBar.style.display = 'none';
                     };
                     xhr.send(formData);
              });
       }

       function syncVideoSourceUI() {
              const isVeo = videoModeVeo?.checked;
              const isNone = videoModeNone?.checked;

              if (videoFileSelect) videoFileSelect.disabled = Boolean(isVeo || isNone);
              if (videoUrlInput) videoUrlInput.disabled = !isVeo;
              if (videoInputsSection) videoInputsSection.style.display = isNone ? 'none' : 'grid';

              // Hide Dropzone and VEO controls if No Video
              if (videoUploadDropzone) videoUploadDropzone.style.display = (videoModeUpload?.checked && !isNone) ? 'block' : 'none';
              if (veoDownloadBtn) veoDownloadBtn.disabled = !isVeo || isNone;
              if (veoDownloadStatus) veoDownloadStatus.style.display = (isVeo && !isNone) ? 'block' : 'none';
       }

       if (videoModeUpload && videoModeVeo) {
              videoModeUpload.addEventListener('change', syncVideoSourceUI);
              videoModeVeo.addEventListener('change', syncVideoSourceUI);
              if (videoModeNone) videoModeNone.addEventListener('change', syncVideoSourceUI);
              syncVideoSourceUI();
       }

       // Player Lineup Management
       // config already declared at top of file
       const clubPlayers = window.clubPlayers || [];
       const modal = document.getElementById('addPlayerModal');
       const form = document.getElementById('addPlayerForm');
       const errorDiv = document.getElementById('player-form-error');

       let currentTeamSide = null;
       let currentIsStarting = null;

       // Open modal
       document.querySelectorAll('[data-add-player]').forEach(btn => {
              btn.addEventListener('click', function () {
                     currentTeamSide = this.getAttribute('data-add-player');
                     currentIsStarting = this.getAttribute('data-is-starting');

                     document.getElementById('player-team-side').value = currentTeamSide;
                     document.getElementById('player-is-starting').value = currentIsStarting;

                     window.populatePlayerSelect(currentTeamSide);
                     form.reset();

                     // Reset captain toggle
                     const captainInput = document.getElementById('player-is-captain');
                     const captainStar = document.getElementById('captain-star-icon');
                     if (captainInput) captainInput.value = '0';
                     if (captainStar) {
                            captainStar.classList.remove('text-yellow-400');
                            captainStar.classList.add('text-slate-600');
                     }

                     // Auto-increment shirt number
                     updateNextShirtNumber(currentTeamSide, true);

                     errorDiv.classList.add('hidden');
                     modal.style.display = 'block';

                     // Focus on player input
                     setTimeout(() => {
                            const playerInput = document.getElementById('player-id') || document.getElementById('player-name-search');
                            if (playerInput) playerInput.focus();
                     }, 100);
              });
       });

       // Captain toggle button
       const captainToggleBtn = document.getElementById('captain-toggle-btn');
       if (captainToggleBtn) {
              captainToggleBtn.addEventListener('click', (e) => {
                     e.preventDefault();
                     const captainInput = document.getElementById('player-is-captain');
                     const captainStar = document.getElementById('captain-star-icon');

                     if (captainInput.value === '1') {
                            // Unset captain
                            captainInput.value = '0';
                            captainStar.classList.remove('text-yellow-400');
                            captainStar.classList.add('text-slate-600');
                     } else {
                            // Set captain
                            captainInput.value = '1';
                            captainStar.classList.remove('text-slate-600');
                            captainStar.classList.add('text-yellow-400');
                     }
              });
       }

       // Auto-increment shirt number function
       function updateNextShirtNumber(teamSide, forceRecalculate = false) {
              const shirtNumberInput = document.getElementById('player-shirt-number');
              if (!shirtNumberInput) return;

              let maxNumber = 0;

              // If not forcing recalculation and there's a value, increment from current value
              const currentValue = parseInt(shirtNumberInput.value);
              if (!forceRecalculate && !isNaN(currentValue) && currentValue > 0) {
                     maxNumber = currentValue;
              } else {
                     // Scan DOM for the highest shirt number for this team
                     // Get the appropriate containers based on team side
                     let containers = [];
                     if (teamSide === 'home') {
                            containers = [
                                   document.getElementById('home-starters'),
                                   document.getElementById('home-subs')
                            ];
                     } else if (teamSide === 'away') {
                            containers = [
                                   document.getElementById('away-starters'),
                                   document.getElementById('away-subs')
                            ];
                     }

                     // Search for shirt numbers in the containers
                     containers.forEach(container => {
                            if (container) {
                                   container.querySelectorAll('.lineup-shirt-number').forEach(el => {
                                          const num = parseInt(el.textContent);
                                          if (!isNaN(num) && num > maxNumber) {
                                                 maxNumber = num;
                                          }
                                   });
                            }
                     });
              }

              // Calculate next number, skipping 13
              let nextNumber = maxNumber + 1;
              if (nextNumber === 13) nextNumber = 14;

              shirtNumberInput.value = nextNumber || '';
       }

       // Close modal
       document.querySelectorAll('[data-close-modal]').forEach(btn => {
              btn.addEventListener('click', () => {
                     modal.style.display = 'none';
              });
       });

       modal.querySelector('.modal-backdrop')?.addEventListener('click', (e) => {
              // Only close if the backdrop itself was clicked, not elements inside the modal
              if (e.target === modal.querySelector('.modal-backdrop')) {
                     modal.style.display = 'none';
              }
       });

       // New Player Modal handlers
       const newPlayerModal = document.getElementById('addNewPlayerModal');
       const addNewPlayerForm = document.getElementById('addNewPlayerForm');
       const newPlayerErrorDiv = document.getElementById('new-player-form-error');

       document.querySelectorAll('[data-close-new-player-modal]').forEach(btn => {
              btn.addEventListener('click', () => {
                     newPlayerModal.style.display = 'none';
              });
       });

       newPlayerModal?.querySelector('.modal-backdrop')?.addEventListener('click', (e) => {
              if (e.target === newPlayerModal.querySelector('.modal-backdrop')) {
                     newPlayerModal.style.display = 'none';
              }
       });

       // Add new player form submit
       addNewPlayerForm?.addEventListener('submit', async (e) => {
              e.preventDefault();
              newPlayerErrorDiv.classList.add('hidden');

              const formData = new FormData(addNewPlayerForm);
              const firstName = formData.get('first_name') || '';
              const lastName = formData.get('last_name') || '';

              // Generate display_name from first and last name
              // Format: "F. LastName" (e.g., "C. Lamb" from "Craig Lamb")
              let displayName = '';
              if (firstName && lastName) {
                     displayName = firstName.charAt(0).toUpperCase() + '. ' + lastName;
              } else if (firstName) {
                     displayName = firstName;
              } else if (lastName) {
                     displayName = lastName;
              }

              const data = {
                     club_id: config.clubId,
                     display_name: displayName,
                     first_name: firstName || null,
                     last_name: lastName || null,
                     primary_position: formData.get('primary_position') || null,
                     is_active: formData.get('is_active') ? 1 : 0,
              };

              try {
                     const response = await fetch(config.endpoints.playersCreate, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data),
                     });

                     const result = await response.json();

                     if (!response.ok || !result.ok) {
                            throw new Error(result.error || 'Failed to create player');
                     }

                     // Set the created player in the search field
                     const searchInput = document.getElementById('player-name-search');
                     const selectedPlayerIdInput = document.getElementById('selected-player-id');

                     if (searchInput && selectedPlayerIdInput) {
                            searchInput.value = result.player.display_name;
                            selectedPlayerIdInput.value = result.player.id;
                     }

                     newPlayerModal.style.display = 'none';
                     addNewPlayerForm.reset();
              } catch (error) {
                     document.getElementById('new-player-error-text').textContent = error.message;
                     newPlayerErrorDiv.classList.remove('hidden');
              }
       });

       // Toggle for showing inactive players
       const inactiveToggle = document.getElementById('include-inactive-players');
       if (inactiveToggle) {
              inactiveToggle.addEventListener('change', () => {
                     // Repopulate the player select when toggle changes
                     if (currentTeamSide) {
                            window.populatePlayerSelect(currentTeamSide);
                     }
              });
       }

       // Away team player search autocomplete
       let playerSearchTimeout;
       let selectedResultIndex = -1;

       function setupPlayerSearch() {
              const searchInput = document.getElementById('player-name-search');
              const resultsDiv = document.getElementById('player-search-results');
              const addNewBtn = document.getElementById('add-new-player-btn');
              const selectedPlayerIdInput = document.getElementById('selected-player-id');
              const newPlayerModal = document.getElementById('addNewPlayerModal');

              if (!searchInput) return;

              // Search on input
              searchInput.addEventListener('input', async (e) => {
                     const query = e.target.value.trim();
                     clearTimeout(playerSearchTimeout);
                     selectedResultIndex = -1;

                     if (query.length < 1) {
                            resultsDiv.classList.add('hidden');
                            return;
                     }

                     playerSearchTimeout = setTimeout(async () => {
                            try {
                                   const response = await fetch(`${config.endpoints.playerSearch}?club_id=${config.clubId}&q=${encodeURIComponent(query)}`);
                                   const result = await response.json();

                                   if (!result.ok || !result.players || result.players.length === 0) {
                                          resultsDiv.innerHTML = `
                                                 <div style="padding: 20px; text-align: center; color: rgb(148, 163, 184); font-size: 14px;">
                                                    <p style="margin-bottom: 12px;">No players found</p>
                                                    <button type="button" id="suggest-add" style="color: rgb(59, 130, 246); font-weight: 600; cursor: pointer; background: none; border: none; padding: 0; font-size: 14px;">+ Create new player</button>
                                                 </div>
                                          `;
                                          resultsDiv.classList.remove('hidden');
                                          document.getElementById('suggest-add')?.addEventListener('click', () => openNewPlayerModal(query));
                                          return;
                                   }

                                   resultsDiv.innerHTML = result.players.map(p => `
                                                         <div class="player-search-result" 
                                                              data-player-id="${p.id}" 
                                                              data-player-name="${p.display_name}"
                                                              data-player-position="${p.position || ''}">
                                                                <div class="player-result-info">
                                                                   <div class="player-result-name">${p.display_name}</div>
                                                                   <div class="player-result-position">${p.position || 'Unknown position'}</div>
                                                                </div>
                                                                <span class="player-result-status ${p.is_active ? 'active' : 'inactive'}">
                                                                   ${p.is_active ? 'Active' : 'Inactive'}
                                                                </span>
                                                         </div>
                                                  `).join('');
                                   resultsDiv.classList.remove('hidden');
                                   selectedResultIndex = -1;

                                   // Attach click and hover handlers
                                   const positionSelect = document.getElementById('player-position');
                                   attachResultClickHandlers(resultsDiv, selectedPlayerIdInput, searchInput, positionSelect);
                            } catch (error) {
                                   resultsDiv.innerHTML = `<div style="padding: 16px; color: rgb(248, 113, 113); font-size: 14px; text-align: center;">Error searching players</div>`;
                                   resultsDiv.classList.remove('hidden');
                            }
                     }, 300);
              });

              // Keyboard navigation
              searchInput.addEventListener('keydown', (e) => {
                     const resultsDiv = document.getElementById('player-search-results');
                     const results = Array.from(resultsDiv.querySelectorAll('.player-search-result'));

                     if (results.length === 0) return;

                     if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            selectedResultIndex = Math.min(selectedResultIndex + 1, results.length - 1);
                            updateSelectedResult(results, selectedResultIndex);
                     } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            selectedResultIndex = Math.max(selectedResultIndex - 1, -1);
                            updateSelectedResult(results, selectedResultIndex);
                     } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (selectedResultIndex >= 0 && results[selectedResultIndex]) {
                                   results[selectedResultIndex].click();
                            }
                     } else if (e.key === 'Escape') {
                            resultsDiv.classList.add('hidden');
                     }
              });

              // Add new player button
              addNewBtn?.addEventListener('click', (e) => {
                     e.preventDefault();
                     const query = searchInput.value.trim();
                     openNewPlayerModal(query);
              });

              // Close results when clicking elsewhere
              document.addEventListener('click', (e) => {
                     if (!e.target.closest('#player-select-wrapper')) {
                            resultsDiv.classList.add('hidden');
                     }
              });
       }

       // Helper function to update selected result styling
       function updateSelectedResult(results, index) {
              results.forEach((result, i) => {
                     if (i === index) {
                            result.classList.add('selected');
                            result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                     } else {
                            result.classList.remove('selected');
                     }
              });
       }

       // Helper function to attach click and hover handlers to results
       function attachResultClickHandlers(resultsDiv, selectedPlayerIdInput, searchInput, positionSelect) {
              resultsDiv.querySelectorAll('.player-search-result').forEach((item, index) => {
                     item.addEventListener('click', () => {
                            const playerId = item.getAttribute('data-player-id');
                            const playerName = item.getAttribute('data-player-name');
                            const playerPosition = item.getAttribute('data-player-position');

                            selectedPlayerIdInput.value = playerId;
                            searchInput.value = playerName;

                            // Auto-populate position if available
                            if (positionSelect && playerPosition) {
                                   positionSelect.value = playerPosition;
                            }

                            resultsDiv.classList.add('hidden');
                            selectedResultIndex = -1;

                            // Focus on "Save & Add Another" button
                            setTimeout(() => {
                                   const addAnotherBtn = document.getElementById('add-another-btn');
                                   if (addAnotherBtn) {
                                          addAnotherBtn.focus();
                                   }
                            }, 100);
                     });

                     // Mouse hover
                     item.addEventListener('mouseenter', () => {
                            selectedResultIndex = index;
                            const results = Array.from(resultsDiv.querySelectorAll('.player-search-result'));
                            updateSelectedResult(results, index);
                     });

                     item.addEventListener('mouseleave', () => {
                            selectedResultIndex = -1;
                            const results = Array.from(resultsDiv.querySelectorAll('.player-search-result'));
                            results.forEach(r => {
                                   r.classList.remove('selected');
                            });
                     });
              });
       }

       function openNewPlayerModal(suggestedName = '') {
              const newPlayerModal = document.getElementById('addNewPlayerModal');
              const displayNameInput = document.getElementById('new-player-display-name');
              const clubIdInput = document.getElementById('new-player-club-id');
              const teamIdInput = document.getElementById('new-player-team-id');
              newPlayerModal.style.display = 'block';
              clubIdInput.value = config.clubId;

              // Set team_id based on which team we're adding for
              if (currentTeamSide === 'away') {
                     teamIdInput.value = config.awayTeamId;
              } else if (currentTeamSide === 'home') {
                     teamIdInput.value = config.homeTeamId;
              }

              if (suggestedName) {
                     displayNameInput.value = suggestedName;
              }
              displayNameInput.focus();
       }

       // Handle modal display and tie search initialization
       const originalPopulate = populatePlayerSelect;
       window.populatePlayerSelect = function (teamSide) {
              originalPopulate(teamSide);
              // Setup player search for both home and away teams
              setTimeout(setupPlayerSearch, 0);
       };

       // Populate player select with optional inactive players
       async function populatePlayerSelect(teamSide) {
              const wrapper = document.getElementById('player-select-wrapper');

              // Both home and away teams now use the same searchable input
              wrapper.innerHTML = `
                              <div class="player-input-container w-full">
                                        <div class="player-input-group">
                                                  <div class="player-input-wrapper">
                                                            <i class="fa-solid fa-search player-input-icon"></i>
                                                            <input type="text" 
                                                                   id="player-name-search" 
                                                                   placeholder="Search player by name..." 
                                                                   class="player-input-field"
                                                                   autocomplete="off">
                                                  </div>
                                                  <button type="button" 
                                                          id="add-new-player-btn" 
                                                          class="player-action-btn"
                                                          title="Create new player">
                                                         <i class="fa-solid fa-plus"></i>
                                                         <span class="btn-text">Add Player</span>
                                                  </button>
                                        </div>
                                        <div id="player-search-results" class="player-results-dropdown hidden">
                                               <!-- Search results will appear here -->
                                        </div>
                                        <input type="hidden" id="selected-player-id" name="player_id">
                              </div>
                              
                              <style>
                                   .player-input-container {
                                          position: relative;
                                          width: 100%;
                                   }

                                   .player-input-group {
                                          display: flex;
                                          gap: 8px;
                                          width: 100%;
                                          align-items: stretch;
                                   }

                                   .player-input-wrapper {
                                          position: relative;
                                          flex: 1;
                                          display: flex;
                                          align-items: center;
                                   }

                                   .player-input-icon {
                                          position: absolute;
                                          left: 12px;
                                          top: 50%;
                                          transform: translateY(-50%);
                                          color: rgb(148, 163, 184);
                                          font-size: 14px;
                                          pointer-events: none;
                                          z-index: 10;
                                   }

                                   .player-input-field {
                                          width: 100%;
                                          padding: 10px 12px 10px 38px;
                                          border: 2px solid rgb(51, 65, 85);
                                          border-radius: 8px;
                                          background-color: rgb(30, 41, 59);
                                          color: white;
                                          font-size: 14px;
                                          transition: all 200ms ease;
                                          outline: none;
                                   }

                                   .player-input-field::placeholder {
                                          color: rgb(100, 116, 139);
                                   }

                                   .player-input-field:hover {
                                          border-color: rgb(71, 85, 105);
                                          background-color: rgb(41, 52, 73);
                                   }

                                   .player-input-field:focus {
                                          border-color: rgb(59, 130, 246);
                                          background-color: rgb(41, 52, 73);
                                          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                                   }

                                   .player-action-btn {
                                          display: flex;
                                          align-items: center;
                                          justify-content: center;
                                          gap: 8px;
                                          padding: 10px 20px;
                                          background-color: rgb(59, 130, 246);
                                          color: white;
                                          border: none;
                                          border-radius: 8px;
                                          font-size: 14px;
                                          font-weight: 600;
                                          cursor: pointer;
                                          transition: all 200ms ease;
                                          white-space: nowrap;
                                          flex-shrink: 0;
                                   }

                                   .player-action-btn i {
                                          font-size: 16px;
                                   }

                                   .player-action-btn:hover {
                                          background-color: rgb(37, 99, 235);
                                          transform: translateY(-1px);
                                          box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                                   }

                                   .player-action-btn:active {
                                          transform: translateY(0);
                                   }

                                   .player-results-dropdown {
                                          position: absolute;
                                          top: calc(100% + 8px);
                                          left: 0;
                                          right: 0;
                                          background-color: rgb(30, 41, 59);
                                          border: 2px solid rgb(51, 65, 85);
                                          border-radius: 8px;
                                          max-height: none;
                                          overflow-y: visible;
                                          z-index: 1000;
                                          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                                   }

                                   .player-results-dropdown.hidden {
                                          display: none;
                                   }

                                   .player-search-result {
                                          padding: 12px 16px;
                                          border-bottom: 1px solid rgb(51, 65, 85);
                                          cursor: pointer;
                                          transition: background-color 150ms ease;
                                          display: flex;
                                          align-items: center;
                                          justify-content: space-between;
                                          gap: 12px;
                                   }

                                   .player-search-result:last-child {
                                          border-bottom: none;
                                   }

                                   .player-search-result:hover,
                                   .player-search-result.selected {
                                          background-color: rgb(51, 65, 85);
                                   }

                                   .player-result-info {
                                          flex: 1;
                                          min-width: 0;
                                   }

                                   .player-result-name {
                                          font-size: 14px;
                                          font-weight: 500;
                                          color: white;
                                          margin-bottom: 4px;
                                   }

                                   .player-result-position {
                                          font-size: 12px;
                                          color: rgb(148, 163, 184);
                                   }

                                   .player-result-status {
                                          font-size: 11px;
                                          font-weight: 600;
                                          padding: 4px 8px;
                                          border-radius: 4px;
                                          flex-shrink: 0;
                                   }

                                   .player-result-status.active {
                                          background-color: rgb(5, 46, 22);
                                          color: rgb(134, 239, 172);
                                   }

                                   .player-result-status.inactive {
                                          background-color: rgb(51, 39, 9);
                                          color: rgb(253, 224, 71);
                                   }

                                   @media (max-width: 640px) {
                                          .player-action-btn .btn-text {
                                                 display: none;
                                          }

                                          .player-action-btn {
                                                 padding: 10px 12px;
                                          }
                                   }
                              </style>
                    `;
       }

       // Function to add a single player
       async function addSinglePlayer(playerData, reloadOnSuccess = true) {
              // Validate starting lineup limit (11 players max)
              if (playerData.is_starting === 1) {
                     const teamSide = playerData.team_side;
                     const startersContainerId = teamSide === 'home' ? 'home-starters' : 'away-starters';
                     const startersContainer = document.getElementById(startersContainerId);

                     if (startersContainer) {
                            const currentStarters = startersContainer.querySelectorAll('[data-match-player-id]').length;
                            if (currentStarters >= 11) {
                                   throw new Error('Cannot add more than 11 players to the starting lineup. Please add as a substitute instead.');
                            }
                     }
              }

              try {
                     const response = await fetch(config.endpoints.matchPlayersAdd, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(playerData),
                     });

                     const result = await response.json();

                     if (!response.ok || !result.ok) {
                            throw new Error(result.error || 'Failed to add player');
                     }

                     if (reloadOnSuccess) {
                            window.location.reload();
                     }
                     return result;
              } catch (error) {
                     throw error;
              }
       }

       // Add player form submit
       form.addEventListener('submit', async (e) => {
              e.preventDefault();
              errorDiv.classList.add('hidden');

              const formData = new FormData(form);
              const data = {
                     match_id: config.matchId,
                     team_side: formData.get('team_side'),
                     is_starting: parseInt(formData.get('is_starting')),
                     shirt_number: formData.get('shirt_number'),
                     position_label: formData.get('position_label'),
                     is_captain: parseInt(formData.get('is_captain')) === 1 ? 1 : 0,
              };

              // Both home and away teams now use selected player ID or player name
              const selectedPlayerId = document.getElementById('selected-player-id')?.value;
              if (selectedPlayerId) {
                     data.player_id = selectedPlayerId;
              } else {
                     // No player selected - use player name
                     data.player_name = document.getElementById('player-name-search')?.value || '';
              }

              try {
                     await addSinglePlayer(data, true);
              } catch (error) {
                     errorDiv.textContent = error.message;
                     errorDiv.classList.remove('hidden');
              }
       });

       // Save & Add Another button
       const addAnotherBtn = document.getElementById('add-another-btn');
       if (addAnotherBtn) {
              addAnotherBtn.addEventListener('click', async (e) => {
                     e.preventDefault();
                     errorDiv.classList.add('hidden');

                     const formData = new FormData(form);
                     const data = {
                            match_id: config.matchId,
                            team_side: formData.get('team_side'),
                            is_starting: parseInt(formData.get('is_starting')),
                            shirt_number: formData.get('shirt_number'),
                            position_label: formData.get('position_label'),
                            is_captain: parseInt(formData.get('is_captain')) === 1 ? 1 : 0,
                     };

                     // Both home and away teams now use selected player ID or player name
                     const selectedPlayerId = document.getElementById('selected-player-id')?.value;
                     if (selectedPlayerId) {
                            data.player_id = selectedPlayerId;
                     } else {
                            data.player_name = document.getElementById('player-name-search')?.value || '';
                     }

                     try {
                            const result = await addSinglePlayer(data, false);

                            // Add the newly created player to the DOM
                            if (result && result.match_player) {
                                   const mp = result.match_player;
                                   const containerId = data.is_starting === 1
                                          ? (data.team_side === 'home' ? 'home-starters' : 'away-starters')
                                          : (data.team_side === 'home' ? 'home-subs' : 'away-subs');

                                   const container = document.getElementById(containerId);
                                   if (container) {
                                          // Remove empty state message if present
                                          const emptyMsg = container.querySelector('.text-center.py-6, .text-center.py-4');
                                          if (emptyMsg) emptyMsg.remove();

                                          // Create player card
                                          const playerCard = document.createElement('div');
                                          playerCard.className = 'lineup-player-card';
                                          playerCard.setAttribute('data-match-player-id', mp.id);
                                          playerCard.innerHTML = `
                                                 <div class="flex items-center gap-3">
                                                    <div class="flex items-center gap-2">
                                                       <span class="lineup-shirt-number">${mp.shirt_number || '—'}</span>
                                                       <span class="lineup-position">${mp.position_label || '—'}</span>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                       <div class="text-sm font-medium text-white truncate">${mp.display_name || 'Unknown'}</div>
                                                    </div>
                                                    ${mp.is_captain ? '<span class="text-yellow-400 text-xs" title="Captain">⭐</span>' : ''}
                                                    <button type="button" class="lineup-delete-btn" data-delete-player="${mp.id}">
                                                       <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                 </div>
                                          `;
                                          container.appendChild(playerCard);

                                          // Re-attach delete handler to the new button
                                          playerCard.querySelector('.lineup-delete-btn')?.addEventListener('click', function () {
                                                 if (!confirm('Remove this player from the lineup?')) return;
                                                 const matchPlayerId = this.getAttribute('data-delete-player');
                                                 fetch(config.endpoints.matchPlayersDelete, {
                                                        method: 'POST',
                                                        headers: { 'Content-Type': 'application/json' },
                                                        body: JSON.stringify({
                                                               match_id: config.matchId,
                                                               id: parseInt(matchPlayerId)
                                                        }),
                                                 }).then(r => r.json()).then(result => {
                                                        if (result.ok) playerCard.remove();
                                                 });
                                          });
                                   }
                            }

                            // Reset form for next player
                            form.reset();

                            // Reset captain toggle
                            document.getElementById('player-is-captain').value = '0';
                            const captainStar = document.getElementById('captain-star-icon');
                            if (captainStar) {
                                   captainStar.classList.remove('text-yellow-400');
                                   captainStar.classList.add('text-slate-600');
                            }

                            // Re-populate player select
                            window.populatePlayerSelect(data.team_side);

                            // Auto-increment shirt number (force recalculation from DOM) AFTER form reset
                            setTimeout(() => {
                                   updateNextShirtNumber(data.team_side, true);
                            }, 50);

                            // Show success message
                            const successMsg = document.createElement('div');
                            successMsg.className = 'text-sm text-green-400 p-3 bg-green-900/20 rounded-lg border border-green-700/50';
                            successMsg.textContent = 'Player added! Ready for next player.';
                            errorDiv.parentElement.insertBefore(successMsg, errorDiv.nextSibling);
                            setTimeout(() => successMsg.remove(), 3000);

                            // Focus on player input
                            setTimeout(() => {
                                   const playerInput = document.getElementById('player-id') || document.getElementById('player-name-search');
                                   if (playerInput) playerInput.focus();
                            }, 100);
                     } catch (error) {
                            errorDiv.textContent = error.message;
                            errorDiv.classList.remove('hidden');
                     }
              });
       }
       // Delete player
       document.querySelectorAll('[data-delete-player]').forEach(btn => {
              btn.addEventListener('click', async function () {
                     if (!confirm('Remove this player from the lineup?')) return;

                     const matchPlayerId = this.getAttribute('data-delete-player');

                     try {
                            const response = await fetch(config.endpoints.matchPlayersDelete, {
                                   method: 'POST',
                                   headers: { 'Content-Type': 'application/json' },
                                   body: JSON.stringify({
                                          match_id: config.matchId,
                                          id: parseInt(matchPlayerId)
                                   }),
                            });

                            const result = await response.json();

                            if (!response.ok || !result.ok) {
                                   throw new Error(result.error || 'Failed to delete player');
                            }

                            // Reload page
                            window.location.reload();
                     } catch (error) {
                            alert('Error: ' + error.message);
                     }
              });
       });

       console.log('[match-edit] Player lineup management initialized');

       // ====================================
       // Match Events Management
       // ====================================

       const goalModal = document.getElementById('addGoalModal');
       const cardModal = document.getElementById('addCardModal');
       const subModal = document.getElementById('addSubstitutionModal');
       const goalForm = document.getElementById('addGoalForm');
       const cardForm = document.getElementById('addCardForm');
       const subForm = document.getElementById('addSubstitutionForm');
       const subAddAnotherBtn = document.getElementById('sub-add-another-btn');
       const subSuccessDiv = document.getElementById('sub-form-success');
       const goalEventIdInput = document.getElementById('goal-event-id');
       const goalEventTypeIdInput = document.getElementById('goal-event-type-id');
       const goalSubmitLabel = goalForm?.querySelector('.goal-submit-label');
       const cardEventIdInput = document.getElementById('card-event-id');
       const cardEventTypeIdInput = document.getElementById('card-event-type-id');
       const cardSubmitLabel = cardForm?.querySelector('.card-submit-label');
       const cardAddAnotherBtn = document.getElementById('card-add-another-btn');
       const cardSuccessDiv = document.getElementById('card-form-success');
       const goalMinuteInput = document.getElementById('goal-minute');
       const goalMinuteExtraInput = document.getElementById('goal-minute-extra');
       const cardMinuteInput = document.getElementById('card-minute');
       const cardMinuteExtraInput = document.getElementById('card-minute-extra');
       const cardNotesInput = document.getElementById('card-notes');

       const EVENT_TYPE_IDS = {
              goal: 16,
              yellow: 8,
              red: 9,
       };

       // ====================================
       // Team Toggle Button CSS
       // ====================================
       const styleEl = document.createElement('style');
       styleEl.textContent = `
              .team-toggle-btn {
                     flex: 1;
                     padding: 10px 16px;
                     background-color: rgb(51, 65, 85);
                     color: rgb(226, 232, 240);
                     border: 2px solid rgb(51, 65, 85);
                     border-radius: 8px;
                     font-size: 14px;
                     font-weight: 500;
                     cursor: pointer;
                     transition: all 200ms ease;
                     display: flex;
                     align-items: center;
                     justify-content: center;
              }
              
              .team-toggle-btn:hover {
                     border-color: rgb(100, 116, 139);
                     background-color: rgb(71, 85, 105);
              }
              
              .team-toggle-btn.active {
                     background-color: rgb(59, 130, 246);
                     color: white;
                     border-color: rgb(59, 130, 246);
              }

              .reason-toggle-btn {
                     width: 100%;
                     padding: 10px 14px;
                     background-color: rgb(30, 41, 59);
                     color: rgb(226, 232, 240);
                     border: 1px solid rgb(51, 65, 85);
                     border-radius: 8px;
                     font-size: 13px;
                     font-weight: 600;
                     cursor: pointer;
                     transition: all 150ms ease;
                     text-align: left;
              }

              .reason-toggle-btn:hover {
                     border-color: rgb(100, 116, 139);
                     background-color: rgb(51, 65, 85);
              }

              .reason-toggle-btn.active {
                     background-color: rgb(34, 197, 94);
                     border-color: rgb(34, 197, 94);
                     color: white;
              }
       `;
       document.head.appendChild(styleEl);

       // Helper: Get match players by team and type
       function getMatchPlayersByTeam(teamSide, isStarting = null) {
              const allPlayers = config.matchPlayers || [];
              return allPlayers.filter(p => {
                     if (p.team_side !== teamSide) return false;
                     if (isStarting !== null && Boolean(p.is_starting) !== Boolean(isStarting)) return false;
                     return true;
              });
       }

       function getMatchPlayersByTeamWithSubs(teamSide) {
              const starters = getMatchPlayersByTeam(teamSide, true);
              const subs = getMatchPlayersByTeam(teamSide, false);
              return [...starters, ...subs];
       }

       function getOppositeTeam(teamSide) {
              if (teamSide === 'home') return 'away';
              if (teamSide === 'away') return 'home';
              return 'unknown';
       }

       function refreshGoalPlayerInput(teamSide) {
              const ownGoalBtn = goalForm?.querySelector('[data-goal-type="own_goal"]');
              const isOwnGoal = ownGoalBtn?.classList.contains('active');
              const targetTeam = isOwnGoal ? getOppositeTeam(teamSide) : teamSide;
              const players = getMatchPlayersByTeam(targetTeam);
              createSearchablePlayerInput(players, 'goal-player', 'goal-player-select-wrapper', true);
              return players;
       }

       // Helper: Create searchable player input
       function createSearchablePlayerInput(players, inputId, wrapperId, includeUnknown = true) {
              const wrapper = document.getElementById(wrapperId);
              if (!wrapper) return;

              wrapper.innerHTML = `
                     <div class="player-input-container w-full">
                            <div class="player-input-group">
                                   <div class="player-input-wrapper">
                                          <i class="fa-solid fa-search player-input-icon"></i>
                                          <input type="text" 
                                                 id="${inputId}-search" 
                                                 placeholder="Search player..." 
                                                 class="player-input-field"
                                                 autocomplete="off">
                                   </div>
                            </div>
                            <div id="${inputId}-results" class="player-results-dropdown hidden">
                                   <!-- Results will appear here -->
                            </div>
                            <input type="hidden" id="${inputId}-value" name="player_id" value="">
                     </div>
                     
                     <style>
                            .player-input-container {
                                   position: relative;
                                   width: 100%;
                            }

                            .player-input-group {
                                   display: flex;
                                   gap: 8px;
                                   width: 100%;
                                   align-items: stretch;
                            }

                            .player-input-wrapper {
                                   position: relative;
                                   flex: 1;
                                   display: flex;
                                   align-items: center;
                            }

                            .player-input-icon {
                                   position: absolute;
                                   left: 12px;
                                   top: 50%;
                                   transform: translateY(-50%);
                                   color: rgb(148, 163, 184);
                                   font-size: 14px;
                                   pointer-events: none;
                                   z-index: 10;
                            }

                            .player-input-field {
                                   width: 100%;
                                   padding: 10px 12px 10px 38px;
                                   border: 2px solid rgb(51, 65, 85);
                                   border-radius: 8px;
                                   background-color: rgb(30, 41, 59);
                                   color: white;
                                   font-size: 14px;
                                   transition: all 200ms ease;
                                   outline: none;
                            }

                            .player-input-field::placeholder {
                                   color: rgb(100, 116, 139);
                            }

                            .player-input-field:hover {
                                   border-color: rgb(71, 85, 105);
                                   background-color: rgb(41, 52, 73);
                            }

                            .player-input-field:focus {
                                   border-color: rgb(59, 130, 246);
                                   background-color: rgb(41, 52, 73);
                                   box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                            }

                            .player-results-dropdown {
                                   position: absolute;
                                   top: calc(100% + 8px);
                                   left: 0;
                                   right: 0;
                                   background-color: rgb(30, 41, 59);
                                   border: 2px solid rgb(51, 65, 85);
                                   border-radius: 8px;
                                   max-height: 280px;
                                   overflow-y: auto;
                                   z-index: 1000;
                                   box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                            }

                            .player-results-dropdown.hidden {
                                   display: none;
                            }

                            .player-search-result {
                                   padding: 12px 16px;
                                   border-bottom: 1px solid rgb(51, 65, 85);
                                   cursor: pointer;
                                   transition: background-color 150ms ease;
                                   display: flex;
                                   align-items: center;
                                   justify-content: space-between;
                                   gap: 12px;
                            }

                            .player-search-result:last-child {
                                   border-bottom: none;
                            }

                            .player-search-result:hover {
                                   background-color: rgb(51, 65, 85);
                            }

                            .player-search-result.selected {
                                   background-color: rgb(59, 130, 246) !important;
                                   border-left: 3px solid rgb(96, 165, 250);
                            }

                            .player-result-info {
                                   flex: 1;
                                   min-width: 0;
                            }

                            .player-result-name {
                                   font-size: 14px;
                                   font-weight: 500;
                                   color: white;
                                   margin-bottom: 4px;
                            }

                            .player-result-position {
                                   font-size: 12px;
                                   color: rgb(148, 163, 184);
                            }
                     </style>
              `;

              // Setup search functionality
              const searchInput = document.getElementById(`${inputId}-search`);
              const resultsContainer = document.getElementById(`${inputId}-results`);
              const hiddenInput = document.getElementById(`${inputId}-value`);
              let selectedResultIndex = -1;

              searchInput.addEventListener('input', (e) => {
                     const query = e.target.value.toLowerCase();

                     if (!query) {
                            resultsContainer.classList.add('hidden');
                            hiddenInput.value = '';
                            selectedResultIndex = -1;
                            return;
                     }

                     const filtered = players.filter(p => {
                            const name = (p.full_name || p.player_name || '').toLowerCase();
                            const displayName = (p.player_name || '').toLowerCase();
                            const shirtNumber = p.shirt_number ? String(p.shirt_number) : '';
                            return name.includes(query) || displayName.includes(query) || shirtNumber.includes(query);
                     });

                     let html = '';
                     if (includeUnknown && query.length > 0) {
                            html += `
                                   <div class="player-search-result" data-player-id="unknown">
                                          <div class="player-result-info">
                                                 <div class="player-result-name">Unknown Player</div>
                                          </div>
                                   </div>
                            `;
                     }

                     filtered.forEach(p => {
                            const name = p.full_name || p.player_name || 'Unknown';
                            const label = p.shirt_number ? `#${p.shirt_number} ${name}` : name;
                            const position = p.position_label || 'Unknown Position';
                            html += `
                                   <div class="player-search-result" data-player-id="${p.id}">
                                          <div class="player-result-info">
                                                 <div class="player-result-name">${label}</div>
                                                 <div class="player-result-position">${position}</div>
                                          </div>
                                   </div>
                            `;
                     });

                     resultsContainer.innerHTML = html;
                     resultsContainer.classList.toggle('hidden', !html);
                     selectedResultIndex = -1;

                     // Handle result selection
                     attachResultHandlers();
              });

              // Keyboard navigation
              searchInput.addEventListener('keydown', (e) => {
                     const results = Array.from(resultsContainer.querySelectorAll('.player-search-result'));
                     if (results.length === 0) return;

                     if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            selectedResultIndex = Math.min(selectedResultIndex + 1, results.length - 1);
                            updateHighlightedResult(results, selectedResultIndex);
                     } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            selectedResultIndex = Math.max(selectedResultIndex - 1, -1);
                            updateHighlightedResult(results, selectedResultIndex);
                     } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (selectedResultIndex >= 0 && results[selectedResultIndex]) {
                                   results[selectedResultIndex].click();
                            }
                     } else if (e.key === 'Escape') {
                            resultsContainer.classList.add('hidden');
                            selectedResultIndex = -1;
                     }
              });

              function updateHighlightedResult(results, index) {
                     results.forEach((result, i) => {
                            if (i === index) {
                                   result.classList.add('selected');
                                   result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                            } else {
                                   result.classList.remove('selected');
                            }
                     });
              }

              function attachResultHandlers() {
                     document.querySelectorAll(`#${inputId}-results .player-search-result`).forEach((result, index) => {
                            result.addEventListener('click', () => {
                                   const playerId = result.dataset.playerId;
                                   const name = result.querySelector('.player-result-name').textContent;
                                   searchInput.value = name;
                                   hiddenInput.value = playerId;
                                   resultsContainer.classList.add('hidden');
                                   selectedResultIndex = -1;
                            });

                            result.addEventListener('mouseenter', () => {
                                   selectedResultIndex = index;
                                   updateHighlightedResult(Array.from(resultsContainer.querySelectorAll('.player-search-result')), selectedResultIndex);
                            });
                     });
              }

              // Close dropdown on outside click
              document.addEventListener('click', (e) => {
                     if (!wrapper.contains(e.target)) {
                            resultsContainer.classList.add('hidden');
                            selectedResultIndex = -1;
                     }
              });
       }

       // Helper: Create player select HTML (legacy for cards)
       function createPlayerSelect(players, includeUnknown = true) {
              let html = '<select required class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">';
              html += '<option value="">Select player</option>';
              if (includeUnknown) {
                     html += '<option value="unknown">Unknown</option>';
              }
              players.forEach(p => {
                     const name = p.full_name || p.player_name || 'Unknown';
                     const label = `#${p.shirt_number || '?'} ${name}`;
                     html += `<option value="${p.id}">${label}</option>`;
              });
              html += '</select>';
              return html;
       }

       // Close modal handlers
       document.querySelectorAll('[data-close-modal]').forEach(btn => {
              btn.addEventListener('click', function () {
                     const modalType = this.getAttribute('data-close-modal');
                     if (modalType === 'goal') goalModal.style.display = 'none';
                     if (modalType === 'card') cardModal.style.display = 'none';
                     if (modalType === 'substitution') subModal.style.display = 'none';
              });
       });

       // Close on backdrop click (only if backdrop itself was clicked)
       [goalModal, cardModal, subModal].forEach(modal => {
              if (modal) {
                     modal.querySelector('.modal-backdrop')?.addEventListener('click', (e) => {
                            if (e.target === modal.querySelector('.modal-backdrop')) {
                                   modal.style.display = 'none';
                            }
                     });
              }
       });

       // Setup team toggle buttons
       function setupTeamToggleButtons(formElement, modalType) {
              const buttons = formElement.querySelectorAll('[name="team_side_btn"]');
              const hiddenInput = formElement.querySelector(`input[name="team_side"]`);

              buttons.forEach(btn => {
                     btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const value = btn.dataset.teamValue;

                            // Update active state
                            buttons.forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');

                            // Update hidden input
                            if (hiddenInput) hiddenInput.value = value;

                            // Update player options
                            const players = modalType === 'card'
                                   ? getMatchPlayersByTeamWithSubs(value)
                                   : getMatchPlayersByTeam(value);
                            if (modalType === 'goal') {
                                   refreshGoalPlayerInput(value);
                            } else if (modalType === 'card') {
                                   createSearchablePlayerInput(players, 'card-player', 'card-player-select-wrapper', true);
                            }
                     });
              });
       }

       // Update player select when team changes
       function setupTeamChangeHandler(formElement, selectWrapperElement, includeUnknown = true) {
              const radios = formElement.querySelectorAll('input[name="team_side"]');
              radios.forEach(radio => {
                     radio.addEventListener('change', function () {
                            const teamSide = this.value;
                            const players = getMatchPlayersByTeam(teamSide);
                            selectWrapperElement.innerHTML = createPlayerSelect(players, includeUnknown);
                     });
              });
       }

       // ====================================
       // Add Goal
       // ====================================
       document.querySelectorAll('[data-add-goal]').forEach(btn => {
              btn.addEventListener('click', function () {
                     goalForm.reset();
                     if (goalEventIdInput) goalEventIdInput.value = '';
                     if (goalEventTypeIdInput) goalEventTypeIdInput.value = EVENT_TYPE_IDS.goal;

                     // Reset goal type toggle buttons
                     const ownGoalBtn = goalForm.querySelector('[data-goal-type="own_goal"]');
                     const penaltyBtn = goalForm.querySelector('[data-goal-type="penalty"]');
                     if (ownGoalBtn) ownGoalBtn.classList.remove('active');
                     if (penaltyBtn) penaltyBtn.classList.remove('active');
                     document.getElementById('goal-own-goal-hidden').value = '0';
                     document.getElementById('goal-is-penalty-hidden').value = '0';

                     document.getElementById('goal-form-error').classList.add('hidden');

                     // Set initial team and show players
                     const teamHiddenInput = goalForm.querySelector('input[name="team_side"]');
                     const initialTeam = 'home';
                     if (teamHiddenInput) teamHiddenInput.value = initialTeam;

                     const buttons = goalForm.querySelectorAll('[name="team_side_btn"]');
                     buttons.forEach(b => {
                            b.classList.remove('active');
                            if (b.dataset.teamValue === initialTeam) b.classList.add('active');
                     });

                     refreshGoalPlayerInput(initialTeam);

                     if (goalSubmitLabel) goalSubmitLabel.textContent = 'Add Goal';
                     const titleEl = goalModal.querySelector('.modal-title');
                     if (titleEl) titleEl.textContent = 'Add Goal';
                     goalModal.style.display = 'block';
              });
       });

       setupTeamToggleButtons(goalForm, 'goal');

       // Setup goal type toggle buttons (Own Goal and Penalty)
       document.querySelectorAll('[name="goal_type_btn"]').forEach(btn => {
              btn.addEventListener('click', (e) => {
                     e.preventDefault();
                     const goalType = btn.dataset.goalType;
                     const hiddenInputId = goalType === 'own_goal' ? 'goal-own-goal-hidden' : 'goal-is-penalty-hidden';
                     const hiddenInput = document.getElementById(hiddenInputId);

                     if (hiddenInput) {
                            const isActive = btn.classList.contains('active');
                            if (isActive) {
                                   btn.classList.remove('active');
                                   hiddenInput.value = '0';
                            } else {
                                   btn.classList.add('active');
                                   hiddenInput.value = '1';
                            }

                            // If toggling own goal, refresh player list
                            if (goalType === 'own_goal') {
                                   const teamHiddenInput = goalForm.querySelector('input[name="team_side"]');
                                   const teamSide = teamHiddenInput?.value || 'home';
                                   const hiddenPlayerInput = goalForm.querySelector('input[id="goal-player-value"]');
                                   const currentPlayerId = hiddenPlayerInput?.value;
                                   const searchInput = goalForm.querySelector('input[id="goal-player-search"]');
                                   const currentSearchValue = searchInput?.value;

                                   refreshGoalPlayerInput(teamSide);

                                   if (currentPlayerId && hiddenPlayerInput) {
                                          hiddenPlayerInput.value = currentPlayerId;
                                          if (searchInput) searchInput.value = currentSearchValue;
                                   }
                            }
                     }
              });
       });

       // Edit Goal
       document.querySelectorAll('[data-edit-goal]').forEach(btn => {
              btn.addEventListener('click', () => {
                     goalForm.reset();
                     const dataset = btn.dataset;
                     const teamSide = dataset.teamSide || 'home';
                     const minute = dataset.minute || '0';
                     const minuteExtra = dataset.minuteExtra || '0';
                     const matchPlayerId = dataset.matchPlayerId || '';
                     const eventId = dataset.eventId || '';
                     const eventTypeId = dataset.eventTypeId || EVENT_TYPE_IDS.goal;
                     const outcome = dataset.outcome || '';
                     const isPenalty = dataset.isPenalty === '1' || dataset.isPenalty === 1;

                     // Set toggle button states
                     const ownGoalBtn = goalForm.querySelector('[data-goal-type="own_goal"]');
                     const penaltyBtn = goalForm.querySelector('[data-goal-type="penalty"]');

                     if (ownGoalBtn) {
                            if (outcome === 'own_goal') {
                                   ownGoalBtn.classList.add('active');
                                   document.getElementById('goal-own-goal-hidden').value = '1';
                            } else {
                                   ownGoalBtn.classList.remove('active');
                                   document.getElementById('goal-own-goal-hidden').value = '0';
                            }
                     }

                     if (penaltyBtn) {
                            if (isPenalty) {
                                   penaltyBtn.classList.add('active');
                                   document.getElementById('goal-is-penalty-hidden').value = '1';
                            } else {
                                   penaltyBtn.classList.remove('active');
                                   document.getElementById('goal-is-penalty-hidden').value = '0';
                            }
                     }

                     if (goalEventIdInput) goalEventIdInput.value = eventId;
                     if (goalEventTypeIdInput) goalEventTypeIdInput.value = eventTypeId;
                     if (goalSubmitLabel) goalSubmitLabel.textContent = 'Update Goal';
                     const titleEl = goalModal.querySelector('.modal-title');
                     if (titleEl) titleEl.textContent = 'Edit Goal';

                     // Set team
                     const teamHiddenInput = goalForm.querySelector('input[name="team_side"]');
                     if (teamHiddenInput) teamHiddenInput.value = teamSide;

                     const buttons = goalForm.querySelectorAll('[name="team_side_btn"]');
                     buttons.forEach(b => {
                            b.classList.remove('active');
                            if (b.dataset.teamValue === teamSide) b.classList.add('active');
                     });

                     if (goalMinuteInput) goalMinuteInput.value = minute;
                     if (goalMinuteExtraInput) goalMinuteExtraInput.value = minuteExtra;

                     const players = refreshGoalPlayerInput(teamSide);

                     // Set selected player
                     if (matchPlayerId && matchPlayerId !== '0') {
                            const hiddenInput = goalForm.querySelector('input[id="goal-player-value"]');
                            if (hiddenInput) hiddenInput.value = matchPlayerId;
                            const player = players.find(p => p.id == matchPlayerId);
                            if (player) {
                                   const searchInput = goalForm.querySelector('input[id="goal-player-search"]');
                                   if (searchInput) searchInput.value = player.full_name || player.player_name || 'Unknown';
                            }
                     }

                     goalModal.style.display = 'block';
              });
       });

       goalForm.addEventListener('submit', async (e) => {
              e.preventDefault();
              const errorDiv = document.getElementById('goal-form-error');
              errorDiv.classList.add('hidden');

              const formData = new FormData(goalForm);
              const playerIdInput = goalForm.querySelector('input[id="goal-player-value"]');
              const playerValue = playerIdInput?.value;

              const ownGoalBtn = goalForm.querySelector('[data-goal-type="own_goal"]');
              const penaltyBtn = goalForm.querySelector('[data-goal-type="penalty"]');
              const isOwnGoal = ownGoalBtn?.classList.contains('active');
              const isPenalty = penaltyBtn?.classList.contains('active');

              const isEdit = Boolean(goalEventIdInput?.value);
              let teamSide = formData.get('team_side');

              // For own goals, flip the team_side because the selected team is the benefiting team,
              // but the player is from the opposite team
              if (isOwnGoal) {
                     teamSide = getOppositeTeam(teamSide);
              }

              const data = {
                     match_id: config.matchId,
                     event_type_id: parseInt(goalEventTypeIdInput?.value || EVENT_TYPE_IDS.goal),
                     team_side: teamSide,
                     minute: parseInt(formData.get('minute')),
                     minute_extra: parseInt(formData.get('minute_extra')) || 0,
                     outcome: isOwnGoal ? 'own_goal' : null,
                     is_penalty: isPenalty ? 1 : 0,
              };

              if (playerValue && playerValue !== 'unknown') {
                     data.match_player_id = parseInt(playerValue);
              }

              if (isEdit) {
                     data.event_id = parseInt(goalEventIdInput.value);
              }

              try {
                     const headers = { 'Content-Type': 'application/json' };
                     if (config.csrfToken) {
                            headers['X-CSRF-Token'] = config.csrfToken;
                     }

                     const response = await fetch(
                            config.basePath + (isEdit
                                   ? `/api/matches/${config.matchId}/events/update`
                                   : `/api/matches/${config.matchId}/events/create`), {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify(data),
                     });

                     const result = await response.json();
                     if (!response.ok || !result.ok) {
                            throw new Error(result.error || (isEdit ? 'Failed to update goal' : 'Failed to add goal'));
                     }

                     window.location.reload();
              } catch (error) {
                     errorDiv.textContent = error.message;
                     errorDiv.classList.remove('hidden');
              }
       });

       // ====================================
       // Add Card
       // ====================================
       let currentCardType = 'yellow';

       document.querySelectorAll('[data-add-card]').forEach(btn => {
              btn.addEventListener('click', function () {
                     currentCardType = this.getAttribute('data-add-card');
                     document.getElementById('card-type').value = currentCardType;
                     cardForm.reset();
                     if (cardEventIdInput) cardEventIdInput.value = '';
                     if (cardEventTypeIdInput) cardEventTypeIdInput.value = currentCardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red;
                     document.getElementById('card-form-error').classList.add('hidden');
                     if (cardSuccessDiv) cardSuccessDiv.classList.add('hidden');
                     if (cardAddAnotherBtn) {
                            cardAddAnotherBtn.style.display = 'inline-flex';
                            cardAddAnotherBtn.disabled = false;
                     }

                     // Set initial team and show players
                     const teamHiddenInput = cardForm.querySelector('input[name="team_side"]');
                     const initialTeam = 'home';
                     if (teamHiddenInput) teamHiddenInput.value = initialTeam;

                     const buttons = cardForm.querySelectorAll('[name="team_side_btn"]');
                     buttons.forEach(b => {
                            b.classList.remove('active');
                            if (b.dataset.teamValue === initialTeam) b.classList.add('active');
                     });

                     const players = getMatchPlayersByTeamWithSubs(initialTeam);
                     createSearchablePlayerInput(players, 'card-player', 'card-player-select-wrapper', true);

                     const title = currentCardType === 'yellow' ? 'Add Yellow Card' : 'Add Red Card';
                     cardModal.querySelector('.modal-title').textContent = title;
                     if (cardSubmitLabel) cardSubmitLabel.textContent = 'Add Card';
                     cardModal.style.display = 'block';
              });
       });

       setupTeamToggleButtons(cardForm, 'card');

       // Edit Card
       document.querySelectorAll('[data-edit-card]').forEach(btn => {
              btn.addEventListener('click', () => {
                     cardForm.reset();
                     const dataset = btn.dataset;
                     const teamSide = dataset.teamSide || 'home';
                     const minute = dataset.minute || '0';
                     const minuteExtra = dataset.minuteExtra || '0';
                     const matchPlayerId = dataset.matchPlayerId || '';
                     const notes = dataset.notes || '';
                     const eventId = dataset.eventId || '';
                     const cardType = dataset.cardType || 'yellow';
                     const eventTypeId = dataset.eventTypeId || (cardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red);

                     currentCardType = cardType;
                     document.getElementById('card-type').value = currentCardType;
                     if (cardEventIdInput) cardEventIdInput.value = eventId;
                     if (cardEventTypeIdInput) cardEventTypeIdInput.value = eventTypeId;
                     if (cardSubmitLabel) cardSubmitLabel.textContent = 'Update Card';
                     if (cardAddAnotherBtn) cardAddAnotherBtn.style.display = 'none';
                     const titleEl = cardModal.querySelector('.modal-title');
                     if (titleEl) titleEl.textContent = currentCardType === 'yellow' ? 'Edit Yellow Card' : 'Edit Red Card';

                     if (cardSuccessDiv) cardSuccessDiv.classList.add('hidden');
                     const cardErrorDiv = document.getElementById('card-form-error');
                     if (cardErrorDiv) cardErrorDiv.classList.add('hidden');

                     // Set team
                     const teamHiddenInput = cardForm.querySelector('input[name="team_side"]');
                     if (teamHiddenInput) teamHiddenInput.value = teamSide;

                     const buttons = cardForm.querySelectorAll('[name="team_side_btn"]');
                     buttons.forEach(b => {
                            b.classList.remove('active');
                            if (b.dataset.teamValue === teamSide) b.classList.add('active');
                     });

                     if (cardMinuteInput) cardMinuteInput.value = minute;
                     if (cardMinuteExtraInput) cardMinuteExtraInput.value = minuteExtra;
                     if (cardNotesInput) cardNotesInput.value = notes;

                     const players = getMatchPlayersByTeamWithSubs(teamSide);
                     createSearchablePlayerInput(players, 'card-player', 'card-player-select-wrapper', true);

                     // Set selected player
                     if (matchPlayerId && matchPlayerId !== '0') {
                            const hiddenInput = cardForm.querySelector('input[id="card-player-value"]');
                            if (hiddenInput) hiddenInput.value = matchPlayerId;
                            const player = players.find(p => p.id == matchPlayerId);
                            if (player) {
                                   const searchInput = cardForm.querySelector('input[id="card-player-search"]');
                                   if (searchInput) searchInput.value = player.full_name || player.player_name || 'Unknown';
                            }
                     }

                     cardModal.style.display = 'block';
              });
       });

       function buildCardPayload() {
              const formData = new FormData(cardForm);
              const playerIdInput = cardForm.querySelector('input[id="card-player-value"]');
              const playerValue = playerIdInput?.value;

              const isEdit = Boolean(cardEventIdInput?.value);
              const payload = {
                     match_id: config.matchId,
                     event_type_id: parseInt(cardEventTypeIdInput?.value || (currentCardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red)),
                     team_side: formData.get('team_side'),
                     minute: parseInt(formData.get('minute')),
                     minute_extra: parseInt(formData.get('minute_extra')) || 0,
                     notes: formData.get('notes') || null,
              };

              if (playerValue && playerValue !== 'unknown') {
                     payload.match_player_id = parseInt(playerValue);
              }

              if (isEdit) {
                     payload.event_id = parseInt(cardEventIdInput.value);
              }

              return { payload, isEdit, teamSide: payload.team_side || 'home' };
       }

       async function submitCard(reloadOnSuccess = true) {
              const { payload, isEdit, teamSide } = buildCardPayload();
              const headers = { 'Content-Type': 'application/json' };
              if (config.csrfToken) headers['X-CSRF-Token'] = config.csrfToken;

              const response = await fetch(
                     config.basePath + (isEdit
                            ? `/api/matches/${config.matchId}/events/update`
                            : `/api/matches/${config.matchId}/events/create`), {
                     method: 'POST',
                     headers: headers,
                     body: JSON.stringify(payload),
              });

              const result = await response.json();
              if (!response.ok || !result.ok) {
                     throw new Error(result.error || (isEdit ? 'Failed to update card' : 'Failed to add card'));
              }

              if (reloadOnSuccess) {
                     window.location.reload();
              }

              return { result, teamSide, isEdit };
       }

       function setCardTeamToggle(teamSide) {
              const buttons = cardForm.querySelectorAll('[name="team_side_btn"]');
              buttons.forEach(b => {
                     b.classList.remove('active');
                     if (b.dataset.teamValue === teamSide) b.classList.add('active');
              });
              const teamHiddenInput = cardForm.querySelector('input[name="team_side"]');
              if (teamHiddenInput) teamHiddenInput.value = teamSide;
       }

       function resetCardFormForNext(teamSide) {
              cardForm.reset();
              if (cardEventIdInput) cardEventIdInput.value = '';
              if (cardEventTypeIdInput) cardEventTypeIdInput.value = currentCardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red;
              setCardTeamToggle(teamSide);
              const players = getMatchPlayersByTeamWithSubs(teamSide);
              createSearchablePlayerInput(players, 'card-player', 'card-player-select-wrapper', true);
              const searchInput = cardForm.querySelector('input[id="card-player-search"]');
              if (searchInput) searchInput.focus();
              if (cardSubmitLabel) cardSubmitLabel.textContent = 'Add Card';
              if (cardAddAnotherBtn) cardAddAnotherBtn.style.display = 'inline-flex';
       }

       function getCardListContainer(teamSide) {
              return document.getElementById(teamSide === 'away' ? 'away-cards-list' : 'home-cards-list');
       }

       function attachCardRowHandlers(cardEl) {
              const editBtn = cardEl.querySelector('[data-edit-card]');
              if (editBtn) {
                     editBtn.addEventListener('click', () => {
                            cardForm.reset();
                            const dataset = editBtn.dataset;
                            const teamSide = dataset.teamSide || 'home';
                            const minute = dataset.minute || '0';
                            const minuteExtra = dataset.minuteExtra || '0';
                            const matchPlayerId = dataset.matchPlayerId || '';
                            const notes = dataset.notes || '';
                            const eventId = dataset.eventId || '';
                            const cardType = dataset.cardType || 'yellow';
                            const eventTypeId = dataset.eventTypeId || (cardType === 'yellow' ? EVENT_TYPE_IDS.yellow : EVENT_TYPE_IDS.red);

                            currentCardType = cardType;
                            document.getElementById('card-type').value = currentCardType;
                            if (cardEventIdInput) cardEventIdInput.value = eventId;
                            if (cardEventTypeIdInput) cardEventTypeIdInput.value = eventTypeId;
                            if (cardSubmitLabel) cardSubmitLabel.textContent = 'Update Card';
                            if (cardAddAnotherBtn) cardAddAnotherBtn.style.display = 'none';
                            const titleEl = cardModal.querySelector('.modal-title');
                            if (titleEl) titleEl.textContent = currentCardType === 'yellow' ? 'Edit Yellow Card' : 'Edit Red Card';

                            if (cardSuccessDiv) cardSuccessDiv.classList.add('hidden');
                            const cardErrorDiv = document.getElementById('card-form-error');
                            if (cardErrorDiv) cardErrorDiv.classList.add('hidden');

                            // Set team
                            const teamHiddenInput = cardForm.querySelector('input[name="team_side"]');
                            if (teamHiddenInput) teamHiddenInput.value = teamSide;

                            const buttons = cardForm.querySelectorAll('[name="team_side_btn"]');
                            buttons.forEach(b => {
                                   b.classList.remove('active');
                                   if (b.dataset.teamValue === teamSide) b.classList.add('active');
                            });

                            if (cardMinuteInput) cardMinuteInput.value = minute;
                            if (cardMinuteExtraInput) cardMinuteExtraInput.value = minuteExtra;
                            if (cardNotesInput) cardNotesInput.value = notes;

                            const players = getMatchPlayersByTeamWithSubs(teamSide);
                            createSearchablePlayerInput(players, 'card-player', 'card-player-select-wrapper', true);

                            // Set selected player
                            if (matchPlayerId && matchPlayerId !== '0') {
                                   const hiddenInput = cardForm.querySelector('input[id="card-player-value"]');
                                   if (hiddenInput) hiddenInput.value = matchPlayerId;
                                   const player = players.find(p => p.id == matchPlayerId);
                                   if (player) {
                                          const searchInput = cardForm.querySelector('input[id="card-player-search"]');
                                          if (searchInput) searchInput.value = player.full_name || player.player_name || 'Unknown';
                                   }
                            }

                            cardModal.style.display = 'block';
                     });
              }

              const deleteBtn = cardEl.querySelector('[data-delete-event]');
              if (deleteBtn) {
                     deleteBtn.addEventListener('click', async function () {
                            if (!confirm('Delete this event?')) return;

                            const eventId = parseInt(this.getAttribute('data-delete-event'));

                            try {
                                   const headers = { 'Content-Type': 'application/json' };
                                   if (config.csrfToken) headers['X-CSRF-Token'] = config.csrfToken;

                                   const response = await fetch(`${config.basePath}/api/matches/${config.matchId}/events/delete`, {
                                          method: 'POST',
                                          headers: headers,
                                          body: JSON.stringify({ event_id: eventId, match_id: config.matchId }),
                                   });

                                   const result = await response.json();
                                   if (!response.ok || !result.ok) {
                                          throw new Error(result.error || 'Failed to delete event');
                                   }

                                   window.location.reload();
                            } catch (error) {
                                   alert('Error: ' + error.message);
                            }
                     });
              }
       }

       function appendCardToList(cardEvent) {
              const teamSide = cardEvent.team_side || 'home';
              const container = getCardListContainer(teamSide);
              if (!container) return;

              const empty = container.querySelector('.card-empty');
              if (empty) empty.remove();

              const cardPlayer = (cardEvent.match_player_name && cardEvent.match_player_name.trim())
                     || (cardEvent.display_name && cardEvent.display_name.trim())
                     || 'Unknown';
              const minute = parseInt(cardEvent.minute || 0);
              const minuteExtra = parseInt(cardEvent.minute_extra || 0);
              const minuteDisplay = `${minute}${minuteExtra > 0 ? `+${minuteExtra}` : ''}`;
              const isYellow = (cardEvent.event_type_key || '') === 'yellow_card' || cardEvent.event_type_id === EVENT_TYPE_IDS.yellow || currentCardType === 'yellow';

              const cardWrapper = document.createElement('div');
              cardWrapper.className = 'rounded-lg bg-slate-800/40 border border-slate-700 p-3';

              const inner = document.createElement('div');
              inner.className = 'flex items-start gap-3';
              cardWrapper.appendChild(inner);

              const content = document.createElement('div');
              content.className = 'flex-1 min-w-0';
              inner.appendChild(content);

              const nameEl = document.createElement('div');
              nameEl.className = 'text-sm font-semibold text-white truncate';
              nameEl.textContent = cardPlayer;
              content.appendChild(nameEl);

              const minuteEl = document.createElement('div');
              minuteEl.className = 'text-xs text-slate-400';
              minuteEl.textContent = `${minuteDisplay}'`;
              content.appendChild(minuteEl);

              const badge = document.createElement('div');
              badge.className = `mt-1 inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 rounded ${isYellow ? 'bg-amber-500/15 text-amber-300' : 'bg-rose-500/15 text-rose-200'}`;
              badge.textContent = isYellow ? 'Yellow Card' : 'Red Card';
              content.appendChild(badge);

              if (cardEvent.notes) {
                     const notesEl = document.createElement('div');
                     notesEl.className = 'text-xs text-slate-500 mt-2';
                     notesEl.textContent = cardEvent.notes;
                     content.appendChild(notesEl);
              }

              const actions = document.createElement('div');
              actions.className = 'flex items-center gap-2 ml-auto';
              inner.appendChild(actions);

              const editBtn = document.createElement('button');
              editBtn.type = 'button';
              editBtn.className = 'text-xs font-semibold px-3 py-1 rounded border border-slate-600 text-slate-200 hover:border-slate-400';
              editBtn.textContent = 'Edit';
              editBtn.dataset.editCard = '1';
              editBtn.dataset.eventId = cardEvent.id;
              editBtn.dataset.eventTypeId = cardEvent.event_type_id;
              editBtn.dataset.cardType = isYellow ? 'yellow' : 'red';
              editBtn.dataset.teamSide = teamSide;
              editBtn.dataset.minute = minute;
              editBtn.dataset.minuteExtra = minuteExtra;
              editBtn.dataset.matchPlayerId = cardEvent.match_player_id || '';
              editBtn.dataset.notes = cardEvent.notes || '';
              actions.appendChild(editBtn);

              const deleteBtn = document.createElement('button');
              deleteBtn.type = 'button';
              deleteBtn.className = 'text-rose-400 hover:text-rose-300 text-sm';
              deleteBtn.dataset.deleteEvent = cardEvent.id;
              deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
              actions.appendChild(deleteBtn);

              attachCardRowHandlers(cardWrapper);
              container.appendChild(cardWrapper);
       }

       cardForm.addEventListener('submit', async (e) => {
              e.preventDefault();
              const errorDiv = document.getElementById('card-form-error');
              if (errorDiv) errorDiv.classList.add('hidden');
              if (cardSuccessDiv) cardSuccessDiv.classList.add('hidden');

              try {
                     await submitCard(true);
              } catch (error) {
                     if (errorDiv) {
                            errorDiv.textContent = error.message;
                            errorDiv.classList.remove('hidden');
                     }
              }
       });

       if (cardAddAnotherBtn) {
              cardAddAnotherBtn.addEventListener('click', async (e) => {
                     e.preventDefault();

                     // If editing, fallback to normal submit/update
                     if (cardEventIdInput?.value) {
                            cardForm.requestSubmit();
                            return;
                     }

                     const errorDiv = document.getElementById('card-form-error');
                     if (errorDiv) errorDiv.classList.add('hidden');
                     if (cardSuccessDiv) cardSuccessDiv.classList.add('hidden');

                     try {
                            const { teamSide, result } = await submitCard(false);

                            if (result && result.event) {
                                   appendCardToList(result.event);
                            }

                            resetCardFormForNext(teamSide || 'home');

                            if (cardSuccessDiv) {
                                   cardSuccessDiv.textContent = 'Card saved. Ready for the next card.';
                                   cardSuccessDiv.classList.remove('hidden');
                            }
                     } catch (error) {
                            if (errorDiv) {
                                   errorDiv.textContent = error.message;
                                   errorDiv.classList.remove('hidden');
                            }
                     }
              });
       }

       // ====================================
       // Add Substitution
       // ====================================
       document.querySelectorAll('[data-add-substitution]').forEach(btn => {
              btn.addEventListener('click', function () {
                     subForm.reset();
                     document.getElementById('sub-form-error').classList.add('hidden');

                     const defaultTeam = 'home';
                     setSubTeamToggle(defaultTeam);
                     populateSubPlayers(defaultTeam);

                     // Clear reason selection
                     setSubReason(null);

                     // Focus first player search (Player ON)
                     setTimeout(() => {
                            const onSearch = document.getElementById('sub-player-on-search');
                            if (onSearch) onSearch.focus();
                     }, 50);

                     subModal.style.display = 'block';
              });
       });

       // Team toggle buttons for subs
       function setSubTeamToggle(teamSide) {
              const buttons = subForm.querySelectorAll('[name="sub_team_side_btn"]');
              buttons.forEach(b => {
                     b.classList.remove('active');
                     if (b.dataset.teamValue === teamSide) b.classList.add('active');
              });
              const hidden = document.getElementById('sub-team-side');
              if (hidden) hidden.value = teamSide;
       }

       function populateSubPlayers(teamSide) {
              const starters = getMatchPlayersByTeam(teamSide, true);
              const subs = getMatchPlayersByTeam(teamSide, false);

              createSearchablePlayerInput(starters, 'sub-player-off', 'sub-player-off-select-wrapper', false);
              createSearchablePlayerInput(subs, 'sub-player-on', 'sub-player-on-select-wrapper', false);

              // Ensure hidden inputs map to expected names
              const offHidden = document.querySelector('#sub-player-off-select-wrapper input[type="hidden"]');
              if (offHidden) offHidden.name = 'player_off_match_player_id';
              const onHidden = document.querySelector('#sub-player-on-select-wrapper input[type="hidden"]');
              if (onHidden) onHidden.name = 'player_on_match_player_id';
       }

       subForm.querySelectorAll('[name="sub_team_side_btn"]').forEach(btn => {
              btn.addEventListener('click', () => {
                     const teamSide = btn.dataset.teamValue;
                     setSubTeamToggle(teamSide);
                     populateSubPlayers(teamSide);
              });
       });

       // Reason buttons (optional)
       function setSubReason(reason) {
              const hidden = document.getElementById('sub-reason');
              if (hidden) hidden.value = reason || '';
              const buttons = document.querySelectorAll('#sub-reason-buttons .reason-toggle-btn');
              buttons.forEach(b => {
                     b.classList.remove('active');
                     if (reason && b.dataset.reason === reason) b.classList.add('active');
              });
       }

       document.querySelectorAll('#sub-reason-buttons .reason-toggle-btn').forEach(btn => {
              btn.addEventListener('click', () => {
                     const current = document.getElementById('sub-reason')?.value;
                     const next = current === btn.dataset.reason ? '' : btn.dataset.reason;
                     setSubReason(next || null);
              });
       });

       function buildSubPayload() {
              const formData = new FormData(subForm);
              const playerOffValue = formData.get('player_off_match_player_id');
              const playerOnValue = formData.get('player_on_match_player_id');

              const payload = {
                     match_id: config.matchId,
                     team_side: formData.get('team_side'),
                     minute: parseInt(formData.get('minute')),
                     player_off_match_player_id: playerOffValue ? parseInt(playerOffValue) : null,
                     player_on_match_player_id: playerOnValue ? parseInt(playerOnValue) : null,
                     reason: formData.get('reason') || null,
              };

              return payload;
       }

       async function submitSubstitution(reloadOnSuccess = true) {
              const payload = buildSubPayload();
              const headers = { 'Content-Type': 'application/json' };
              if (config.csrfToken) headers['X-CSRF-Token'] = config.csrfToken;

              const response = await fetch(config.basePath + '/api/match-substitutions/create', {
                     method: 'POST',
                     headers: headers,
                     body: JSON.stringify(payload),
              });

              const result = await response.json();
              const okFlag = result.ok !== undefined ? result.ok : result.success;
              if (!response.ok || !okFlag) {
                     throw new Error(result.error || 'Failed to add substitution');
              }

              if (reloadOnSuccess) {
                     window.location.reload();
              }

              return { result, payload };
       }

       function getSubstitutionListContainer(teamSide) {
              return document.getElementById(teamSide === 'away' ? 'away-subs-list' : 'home-subs-list');
       }

       function appendSubstitutionToList(sub, teamSide) {
              const container = getSubstitutionListContainer(teamSide);
              if (!container) return;

              const empty = container.querySelector('.sub-empty');
              if (empty) empty.remove();

              const minute = parseInt(sub.minute || 0);
              const playerOffName = sub.player_off_name || 'Unknown';
              const playerOnName = sub.player_on_name || 'Unknown';
              const shirtOff = sub.player_off_shirt || '?';
              const shirtOn = sub.player_on_shirt || '?';
              const reason = sub.reason || '';

              const subWrapper = document.createElement('div');
              subWrapper.className = 'rounded-lg bg-slate-800/40 border border-slate-700 p-3';

              const inner = document.createElement('div');
              inner.className = 'flex items-center gap-2';
              subWrapper.appendChild(inner);

              // Create a vertical container for emoji and time
              const emojiTimeContainer = document.createElement('div');
              emojiTimeContainer.className = 'flex flex-col items-center gap-1';
              inner.appendChild(emojiTimeContainer);

              const emoji = document.createElement('span');
              emoji.className = 'text-lg';
              emoji.textContent = '🔄';
              emojiTimeContainer.appendChild(emoji);

              const minuteEl = document.createElement('div');
              minuteEl.className = 'text-xs text-slate-400 font-semibold leading-none';
              minuteEl.textContent = `${minute}'`;
              emojiTimeContainer.appendChild(minuteEl);

              const content = document.createElement('div');
              content.className = 'flex-1 min-w-0';
              inner.appendChild(content);

              const details = document.createElement('div');
              details.className = 'text-xs space-y-0.5';
              content.appendChild(details);

              const offEl = document.createElement('div');
              offEl.className = 'text-slate-400';
              offEl.innerHTML = `<i class="fa-solid fa-arrow-down mr-1"></i>OFF: #${shirtOff} ${playerOffName}`;
              details.appendChild(offEl);

              const onEl = document.createElement('div');
              onEl.className = 'text-emerald-400';
              onEl.innerHTML = `<i class="fa-solid fa-arrow-up mr-1"></i>ON: #${shirtOn} ${playerOnName}`;
              details.appendChild(onEl);

              if (reason) {
                     const reasonEl = document.createElement('div');
                     reasonEl.className = 'text-xs text-slate-500 mt-1';
                     reasonEl.textContent = reason.charAt(0).toUpperCase() + reason.slice(1);
                     details.appendChild(reasonEl);
              }

              const deleteBtn = document.createElement('button');
              deleteBtn.type = 'button';
              deleteBtn.className = 'text-rose-400 hover:text-rose-300 text-sm';
              deleteBtn.dataset.deleteSubstitution = sub.id;
              deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
              inner.appendChild(deleteBtn);


              // Attach delete handler
              deleteBtn.addEventListener('click', async function () {
                     if (!confirm('Delete this substitution?')) return;

                     const subId = parseInt(this.getAttribute('data-delete-substitution'));

                     try {
                            const response = await fetch(config.basePath + '/api/match-substitutions/delete', {
                                   method: 'POST',
                                   headers: { 'Content-Type': 'application/json' },
                                   body: JSON.stringify({
                                          match_id: config.matchId,
                                          id: subId
                                   }),
                            });

                            const result = await response.json();
                            if (!response.ok || !result.ok) {
                                   throw new Error(result.error || 'Failed to delete substitution');
                            }

                            window.location.reload();
                     } catch (error) {
                            alert('Error: ' + error.message);
                     }
              });

              container.appendChild(subWrapper);
       }

       subForm.addEventListener('submit', async (e) => {
              e.preventDefault();
              const errorDiv = document.getElementById('sub-form-error');
              errorDiv.classList.add('hidden');

              const formData = new FormData(subForm);
              const playerOffValue = formData.get('player_off_match_player_id');
              const playerOnValue = formData.get('player_on_match_player_id');

              if (!playerOffValue || !playerOnValue) {
                     errorDiv.textContent = 'Please select both Player OFF and Player ON.';
                     errorDiv.classList.remove('hidden');
                     return;
              }

              try {
                     await submitSubstitution(true);
              } catch (error) {
                     errorDiv.textContent = error.message;
                     errorDiv.classList.remove('hidden');
              }
       });

       if (subAddAnotherBtn) {
              subAddAnotherBtn.addEventListener('click', async (e) => {
                     e.preventDefault();
                     const errorDiv = document.getElementById('sub-form-error');
                     if (errorDiv) errorDiv.classList.add('hidden');
                     if (subSuccessDiv) subSuccessDiv.classList.add('hidden');

                     const formData = new FormData(subForm);
                     const playerOffValue = formData.get('player_off_match_player_id');
                     const playerOnValue = formData.get('player_on_match_player_id');

                     if (!playerOffValue || !playerOnValue) {
                            if (errorDiv) {
                                   errorDiv.textContent = 'Please select both Player OFF and Player ON.';
                                   errorDiv.classList.remove('hidden');
                            }
                            return;
                     }

                     try {
                            const { result, payload } = await submitSubstitution(false);

                            // Append the substitution to the list
                            if (result && result.substitution) {
                                   appendSubstitutionToList(result.substitution, payload.team_side);
                            }

                            // Reset form for next entry but keep the selected team
                            const teamSide = document.getElementById('sub-team-side')?.value || 'home';
                            subForm.reset();
                            setSubTeamToggle(teamSide);
                            populateSubPlayers(teamSide);
                            setSubReason(null);
                            const minuteInput = document.getElementById('sub-minute');
                            if (minuteInput) minuteInput.value = '';

                            if (subSuccessDiv) {
                                   subSuccessDiv.textContent = 'Substitution saved. Ready for the next one.';
                                   subSuccessDiv.classList.remove('hidden');
                            }

                            setTimeout(() => {
                                   const onSearch = document.getElementById('sub-player-on-search');
                                   if (onSearch) onSearch.focus();
                            }, 50);
                     } catch (error) {
                            if (errorDiv) {
                                   errorDiv.textContent = error.message;
                                   errorDiv.classList.remove('hidden');
                            }
                     }
              });
       }

       // ====================================
       // Delete Event
       // ====================================
       document.querySelectorAll('[data-delete-event]').forEach(btn => {
              btn.addEventListener('click', async function () {
                     if (!confirm('Delete this event?')) return;

                     const eventId = parseInt(this.getAttribute('data-delete-event'));

                     try {
                            const headers = { 'Content-Type': 'application/json' };
                            if (config.csrfToken) headers['X-CSRF-Token'] = config.csrfToken;

                            const response = await fetch(`${config.basePath}/api/matches/${config.matchId}/events/delete`, {
                                   method: 'POST',
                                   headers: headers,
                                   body: JSON.stringify({ event_id: eventId, match_id: config.matchId }),
                            });

                            const result = await response.json();
                            if (!response.ok || !result.ok) {
                                   throw new Error(result.error || 'Failed to delete event');
                            }

                            window.location.reload();
                     } catch (error) {
                            alert('Error: ' + error.message);
                     }
              });
       });

       // ====================================
       // Delete Substitution
       // ====================================
       document.querySelectorAll('[data-delete-substitution]').forEach(btn => {
              btn.addEventListener('click', async function () {
                     if (!confirm('Delete this substitution?')) return;

                     const subId = parseInt(this.getAttribute('data-delete-substitution'));

                     try {
                            const response = await fetch(config.basePath + '/api/match-substitutions/delete', {
                                   method: 'POST',
                                   headers: { 'Content-Type': 'application/json' },
                                   body: JSON.stringify({
                                          match_id: config.matchId,
                                          id: subId
                                   }),
                            });

                            const result = await response.json();
                            if (!response.ok || !result.ok) {
                                   throw new Error(result.error || 'Failed to delete substitution');
                            }

                            window.location.reload();
                     } catch (error) {
                            alert('Error: ' + error.message);
                     }
              });
       });

       console.log('[match-edit] Match events management initialized');
})();
