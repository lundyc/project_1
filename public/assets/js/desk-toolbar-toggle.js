// JS for toggling the drawing toolbar with animation
$(function () {
          var $toolbar = $('[data-drawing-toolbar]');
          var $toggleBtn = $('#drawingToolbarToggleBtn');
          var $controls = $('.custom-video-controls');
          // ...init code removed...
          if ($toolbar.length && $toggleBtn.length) {
                    $toolbar.hide().addClass('toolbar-hidden');
                    $controls.removeClass('toolbar-open');
                    $toggleBtn.on('click', function () {
                              // ...toggle button clicked log removed...
                              if ($toolbar.is(':visible')) {
                                        // ...hiding toolbar log removed...
                                        $toolbar.slideUp(200, function () {
                                                  $toolbar.addClass('toolbar-hidden');
                                                  $controls.removeClass('toolbar-open');
                                                  // ...toolbar hidden log removed...
                                        });
                                        $toggleBtn.attr('aria-pressed', 'false');
                              } else {
                                        // ...showing toolbar log removed...
                                        $toolbar.slideDown(200, function () {
                                                  $toolbar.removeClass('toolbar-hidden');
                                                  $controls.addClass('toolbar-open');
                                                  // ...toolbar shown log removed...
                                        });
                                        $toggleBtn.attr('aria-pressed', 'true');
                              }
                    });
          } else {
                    // ...toolbar or toggle button not found log removed...
          }
});
