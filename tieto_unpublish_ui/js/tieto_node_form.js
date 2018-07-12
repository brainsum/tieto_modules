/**
 * @file
 * Jquery UI Datepicker.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tieto_node_forms = {
    attach: function (context, settings) {
      $('.ief-form .field--widget-jquery-ui-datepicker-timestamp')
      .once('buttonUpdate')
      .each(function () {
        var dpWrapper = $(this);
        $(this)
        .find('input')
        .attr('autocomplete', 'off')
        .on('change', updateSubmitButton);

        function updateSubmitButton(e) {
          var good = true;
          dpWrapper.find('input').each(function () {
            if ($(this).val().length === 0) {
              good = false;
            }
          });

          if (good) {
            dpWrapper
            .closest('.ief-form')
            .find('.ief-entity-submit:not(.scheduled)')
            .addClass('scheduled');
          }
          else {
            dpWrapper
            .closest('.ief-form')
            .find('.ief-entity-submit.scheduled')
            .removeClass('scheduled');
          }
        }

        updateSubmitButton();
      });
    }
  };

  function actionExists(selector) {
    var notification = $(selector + ' .action-button--action-notification-wrapper');
    var button = $(selector + ' .action-button--action-wrapper');

    return notification.children().length > 0 && button.children().length > 0;
  }

  $.fn.updateButtonHints = function (scheduledDates) {
    var selectors = {
      'scheduled_publish_date': '.form-action-moderation-state-unpublished-button-wrapper',
      'scheduled_unpublish_date': '.form-action-moderation-state-unpublished-content-button-wrapper',
      'scheduled_trash_date': '.form-action-moderation-state-trash-button-wrapper',
    };

    var $notifElement, $buttonElement, pubSelector;
    $.each(scheduledDates, function (index, item) {
      pubSelector = false;

      $notifElement = $(selectors[index] + ' .action-button--action-notification-wrapper');
      $buttonElement = $(selectors[index] + ' .action-button--action-wrapper');

      $(selectors[index] + ' .action-description').html(item.text);

      if (index === 'scheduled_publish_date') {
        pubSelector = '.form-action-moderation-state-published-button-wrapper';
        $notifElement = $(pubSelector + ' .action-button--action-notification-wrapper');
        $buttonElement = $(pubSelector + ' .action-button--action-wrapper');
      }
      var actionDoesExist = actionExists(selectors[index]);
      if (pubSelector) {
        actionDoesExist = actionExists(pubSelector);
      }
      if (item.date === null) {
        $(selectors[index] + '.scheduled').removeClass('scheduled');

        if (pubSelector) {
          $(pubSelector + '.scheduled').removeClass('scheduled');
        }

        if (actionDoesExist) {
          $notifElement.not('.action-button--hidden-action-wrapper').addClass('action-button--hidden-action-wrapper');
          $buttonElement.removeClass('action-button--hidden-action-wrapper');
        }
      }
      else {
        $(selectors[index] + ':not(.scheduled)').addClass('scheduled');

        if (pubSelector) {
          $(pubSelector + ':not(.scheduled)').addClass('scheduled');
        }

        if (actionDoesExist) {
          $notifElement.removeClass('action-button--hidden-action-wrapper');
          $buttonElement.not('.action-button--hidden-action-wrapper').addClass('action-button--hidden-action-wrapper');
        }
      }
    });
  }
})(jQuery, Drupal);
