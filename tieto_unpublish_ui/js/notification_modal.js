(function ($, Drupal) {
  Drupal.behaviors.notificationModal = {
    attach: function (context, settings) {
      let modalForm = $('#tieto-action-notification-modal-form');
      let modalSubmitButton = modalForm.find('#modal-submit-button'); // $('#tieto-action-notification-modal-form > #modal-submit-button');
      let modalCancelButton = modalForm.find('#modal-cancel-button'); // $('#tieto-action-notification-modal-form > #modal-submit-button');
      // let modalCancelButton = $('#tieto-action-notification-modal-form > #modal-cancel-button');

      modalForm.submit(function (event) {
        event.preventDefault();
      });

      modalSubmitButton.once('one-click-listener-only').on('click', function (event) {
        event.preventDefault();
        // @todo: Get proper state.

        let stateDataItem = $('#tieto-action-notification-modal-form > #modal-moderation-state-information')

        let state = stateDataItem.val();
        let selector = "div.form-action-moderation-state-" + state + "-button-wrapper > div.action-button--action-wrapper.action-button--hidden-action-wrapper > input";

        $(selector).click();
        $('.ui-dialog-titlebar-close').click();
      });

      modalCancelButton.once('one-click-listener-only').on('click', function (event) {
        event.preventDefault();
        $('.ui-dialog-titlebar-close').click();
      });


      // $('input.myCustomBehavior', context).once('myCustomBehavior').each(function () {
      //   // Apply the myCustomBehaviour effect to the elements only once.
      // });
    }
  };
})(jQuery, Drupal);