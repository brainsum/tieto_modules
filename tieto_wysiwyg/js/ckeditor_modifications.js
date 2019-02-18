/**
 * @file
 * Modify CKEditor classes for Inline Styles.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.ckeditor_modifications = {
    attach: function (context, settings) {
      $(context).on('click', '.cke_combo__styles .cke_combo_button', function () {
        var combopanel_iframe = $('.cke_combopanel iframe.cke_panel_frame');
        combopanel_iframe.load(function () {
          combopanel_iframe.contents().find('ul.cke_panel_list li:has(span)').each(function () {
            $(this).parent().addClass('inline-styles');
            var class_name = $(this).find('span').attr('class');
            $(this).addClass(class_name + '-wrapper');
          });
        });
      });
    }
  };

})(jQuery, Drupal);
