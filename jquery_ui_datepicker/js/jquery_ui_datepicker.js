/**
 * @file
 * Jquery UI Datepicker.
 */
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.jquery_ui_datepicker = {
    attach: function (context, settings) {
      $.each(drupalSettings.jquery_ui_datepicker, function (fieldName, settings){
        var $input = $('input[name="' + fieldName + '"]');
        var value = $input.val();
        if (typeof value != 'undefined') {
          $input.val($.trim(value.replace(/\s+/g, ' ')));
        }

        $input
          .once('datepicker')
          .datepicker(settings)
          .on('blur', function(){
            // Strip multiple spaced in between words, and trim the string
            var value = $(this).val();
            if (typeof value != 'undefined') {
              $(this).val($.trim(value.replace(/\s+/g, ' ')));
            }
          });
      });
      $('input[name^="scheduled_"][name$="[date]"]').once('datepicker')
          .datepicker({
            dateFormat: "d M yy",
            minDate: 0
          })
          .on('blur', function(){
            // Strip multiple spaced in between words, and trim the string
            var value = $(this).val();
            if (typeof value != 'undefined') {
              $(this).val($.trim(value.replace(/\s+/g, ' ')));
            }
          });
      $('input[name^="scheduled_"][name$="[time]"]').once('timepicker')
          .timepicker({
            timeFormat: "H:mm",
            interval: 60,
            change: function() {
              $(this).trigger('change');
            }
          });
      $.each(drupalSettings.jquery_timepicker, function (fieldName, settings){
        $('input[name="' + fieldName + '"]')
          .once('timepicker')
          .timepicker(settings);
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
