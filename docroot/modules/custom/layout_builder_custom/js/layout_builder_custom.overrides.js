/**
 * @file
 */
(function ($, Drupal) {
    Drupal.behaviors.layoutBuilderCustomOverrides = {
        attach: function (context, settings) {
          $(window).once('off-canvas-overrides').on({
            'dialog:aftercreate': function (event, dialog, $element) {
              let justCreated = true;
              console.log('event', event);
              if (Drupal.offCanvas.isOffCanvas($element) && $element.find('.layout-selection').length === 0) {
                let offCanvasWidth;
                const offCanvasCookie = $.cookie('ui_off_canvas_width');
                if (offCanvasCookie === undefined) {
                  offCanvasWidth = 500;
                } else {
                  offCanvasWidth = offCanvasCookie;
                }
                $($element).parent().attr('style', `position: fixed; width: ${offCanvasWidth}px; right: 0; left: auto;`);

                let eventData = { settings: settings, $element: $element, offCanvasDialog: Drupal.offCanvas };
                const $container = Drupal.offCanvas.getContainer($element);
                $element.on('dialogContentResize.off-canvas', eventData, function() {
                  if (!justCreated) {
                    // Cookie that expires in 99 years.
                    const width = $container.outerWidth();
                    $.cookie('ui_off_canvas_width', width, { expires: 36135, path: '/' });
                  }
                  justCreated = false;
                });
              }
            }
          });
        }
    };
})(jQuery, Drupal);
