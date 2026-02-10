/**
 * Global Toast Notification System
 * 
 * Usage:
 *   Toast.show('Event saved successfully', 'success');
 *   Toast.show('An error occurred', 'error');
 *   Toast.show('Info message', 'info');
 *   Toast.show('Warning message', 'warning');
 *   Toast.show('Message', 'success', { duration: 5000 });
 *   Toast.show('Persistent message', 'info', { duration: 0 });
 *   Toast.dismiss(toastId);
 *   Toast.dismissAll();
 */

window.Toast = (() => {
          let toastContainer = null;
          let toastIdCounter = 0;
          const toasts = new Map();
          const autoDismissTimers = new Map();
          const DEFAULT_DURATION = 8000; // 8 seconds
          const MAX_DURATION = 10000; // 10 seconds

          const icons = {
                    success: '✓',
                    error: '✕',
                    info: 'ℹ',
                    warning: '⚠',
          };

          /**
           * Ensure the toast container exists in the DOM
           */
          function ensureContainer() {
                    if (!toastContainer) {
                              toastContainer = document.createElement('div');
                              toastContainer.className = 'toast-container';
                              toastContainer.setAttribute('aria-live', 'polite');
                              toastContainer.setAttribute('aria-atomic', 'true');
                              document.body.appendChild(toastContainer);
                    }
                    return toastContainer;
          }

          /**
           * Create a toast element
           */
          function createToastElement(type, message, options = {}) {
                    const toast = document.createElement('div');
                    toast.className = `toast ${type}`;
                    toast.setAttribute('role', 'status');

                    const icon = document.createElement('span');
                    icon.className = 'toast-icon';
                    icon.setAttribute('aria-hidden', 'true');
                    icon.textContent = icons[type] || '•';

                    const messageEl = document.createElement('span');
                    messageEl.className = 'toast-message';
                    messageEl.textContent = message;

                    toast.appendChild(icon);
                    toast.appendChild(messageEl);

                    // Add close button unless dismissed automatically
                    const closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.className = 'toast-close';
                    closeBtn.setAttribute('aria-label', 'Dismiss message');
                    closeBtn.innerHTML = '×';
                    toast.appendChild(closeBtn);

                    return toast;
          }

          /**
           * Dismiss a toast by ID
           */
          function dismissToast(toastId) {
                    const toast = toasts.get(toastId);
                    if (!toast) return;

                    // Clear auto-dismiss timer if it exists
                    if (autoDismissTimers.has(toastId)) {
                              clearTimeout(autoDismissTimers.get(toastId));
                              autoDismissTimers.delete(toastId);
                    }

                    // Animate out
                    toast.classList.add('exiting');
                    setTimeout(() => {
                              if (toast.parentNode) {
                                        toast.parentNode.removeChild(toast);
                              }
                              toasts.delete(toastId);
                    }, 300);
          }

          /**
           * Show a toast notification
           * @param {string} message - The toast message
           * @param {string} type - Type: 'success', 'error', 'info', 'warning'
           * @param {object} options - Options: { duration: ms or 0 for no auto-dismiss }
           * @returns {number} Toast ID
           */
          function show(message, type = 'info', options = {}) {
                    if (!message) return null;

                    const container = ensureContainer();
                    const toastId = ++toastIdCounter;
                    let duration = 'duration' in options ? options.duration : DEFAULT_DURATION;
                    if (duration && duration > MAX_DURATION) {
                              duration = MAX_DURATION;
                    }

                    const toastElement = createToastElement(type, message, options);
                    toastElement.setAttribute('data-toast-id', toastId);
                    container.appendChild(toastElement);

                    toasts.set(toastId, toastElement);

                    // Handle close button click
                    const closeBtn = toastElement.querySelector('.toast-close');
                    if (closeBtn) {
                              closeBtn.addEventListener('click', () => dismissToast(toastId));
                    }

                    // Set auto-dismiss if duration > 0
                    if (duration && duration > 0) {
                              const timer = setTimeout(() => dismissToast(toastId), duration);
                              autoDismissTimers.set(toastId, timer);
                    }

                    return toastId;
          }

          /**
           * Dismiss all toasts
           */
          function dismissAll() {
                    const ids = Array.from(toasts.keys());
                    ids.forEach(dismissToast);
          }

          /**
           * Dismiss a specific toast by ID
           */
          function dismiss(toastId) {
                    dismissToast(toastId);
          }

          // Public API
          return {
                    show,
                    dismiss,
                    dismissAll,
                    success: (msg, opts) => show(msg, 'success', opts),
                    error: (msg, opts) => show(msg, 'error', opts),
                    info: (msg, opts) => show(msg, 'info', opts),
                    warning: (msg, opts) => show(msg, 'warning', opts),
          };
})();
