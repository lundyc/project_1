// Instant hide/show for the top nav based on scroll direction.
(function () {
          var nav = document.querySelector('.top-nav');
          if (!nav) {
                    return;
          }

          var knownScrollContainers = [
                    '.desk-side-scroll',
                    '.timeline-scroll',
          ];

          var isScrollable = function (element) {
                    if (!element) {
                              return false;
                    }

                    var computed = window.getComputedStyle(element);
                    var overflowY = computed && computed.overflowY;
                    return overflowY === 'auto' || overflowY === 'scroll' || overflowY === 'overlay';
          };

          var findScrollSource = function () {
                    for (var i = 0; i < knownScrollContainers.length; i++) {
                              var match = document.querySelector(knownScrollContainers[i]);
                              if (isScrollable(match)) {
                                        return {
                                                  element: match,
                                                  target: match,
                                                  isWindow: false,
                                        };
                              }
                    }

                    var scrollingElement = document.scrollingElement || document.documentElement || document.body;
                    return {
                              element: scrollingElement,
                              target: window,
                              isWindow: true,
                    };
          };

          var scrollSource = findScrollSource();
          var getScrollPosition = function () {
                    if (scrollSource.isWindow) {
                              return window.scrollY || window.pageYOffset || (scrollSource.element && scrollSource.element.scrollTop) || 0;
                    }
                    return scrollSource.element ? scrollSource.element.scrollTop : 0;
          };

          var lastScrollPos = getScrollPosition();
          var isTicking = false;

          var updateNavVisibility = function () {
                    var currentScrollPos = getScrollPosition();

                    if (currentScrollPos <= 0 || currentScrollPos < lastScrollPos) {
                              nav.classList.remove('is-hidden');
                    } else if (currentScrollPos > lastScrollPos) {
                              nav.classList.add('is-hidden');
                    }

                    lastScrollPos = currentScrollPos;
                    isTicking = false;
          };

          var onScroll = function () {
                    if (!isTicking) {
                              isTicking = true;
                              window.requestAnimationFrame(updateNavVisibility);
                    }
          };

          (scrollSource.target || window).addEventListener('scroll', onScroll, { passive: true });
})();

// Dropdown menu functionality
(function () {
          document.addEventListener('click', function (e) {
                    var dropdownButton = e.target.closest('[data-bs-toggle="dropdown"]');

                    if (dropdownButton) {
                              e.preventDefault();
                              var dropdownMenu = dropdownButton.nextElementSibling;

                              if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                                        // Close all other dropdowns
                                        document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
                                                  if (menu !== dropdownMenu) {
                                                            menu.classList.remove('show');
                                                  }
                                        });

                                        // Toggle this dropdown
                                        dropdownMenu.classList.toggle('show');
                                        dropdownButton.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
                              }
                    } else {
                              // Close dropdowns when clicking outside
                              document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
                                        menu.classList.remove('show');
                                        var btn = menu.previousElementSibling;
                                        if (btn && btn.hasAttribute('data-bs-toggle')) {
                                                  btn.setAttribute('aria-expanded', 'false');
                                        }
                              });
                    }
          });
})();
