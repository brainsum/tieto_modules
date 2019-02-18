/**
 * @file
 * JavaScript code for UI modes.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Sets the event listener for the UI switcher button.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.editMode = {
    attach: function (context, settings) {

      // Get the checkbox element.
      var checkbox, checkboxSelector = '.edit-mode input';
      if ('function' === typeof context.querySelector) {
        checkbox = context.querySelector(checkboxSelector);
      }
      else {
        checkbox = document.body.querySelector(checkboxSelector);
      }

      if (!checkbox) {
        return;
      }

      // Set specific cookie.
      function setCookie(cname, cvalue, exsecs) {
        var d = new Date();
        d.setTime(d.getTime() + (exsecs * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
      }

      // Get specific cookie.
      function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') {
            c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
          }
        }
        return "";
      }

      // Set checkbox and trigger event.
      function setEditCheckbox(editMode) {
        checkbox.checked = editMode;
        // Fire change event, than fire it.
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("change", true, false);
        checkbox.dispatchEvent(evt);
      }

      /**
       * Handles changin the class on the body when UI switcher is clicked.
       *
       * @param {Object} event
       *   Click event details.
       */
      var onEditModeChange = function (event) {
        var isEditMode = event.target.checked;

        document.body.setAttribute('data-edit-mode', Number(isEditMode));

        var label = isEditMode ? Drupal.t('Quit editing...') : Drupal.t('Edit this page');
        checkbox.parentNode.setAttribute('data-label', label);
        checkbox.parentNode.classList.toggle('active', isEditMode);

        // Update URL string.
        if (window.location.href.indexOf("#") == -1) {
          // Fix first click, when there's no # in the url (empty # and no #
          // eq).
          window.location.hash = '#viewmode';
        }
        window.location.hash = isEditMode ? '#' : '#viewmode';
        // Save last state for 8 hours.
        setCookie("editMode", Number(isEditMode), 8 * 60 * 60);
      };

      checkbox.addEventListener('change', onEditModeChange);

      // Get the location hash (URL fragment in Drupal world).
      var hash = window.location.hash;
      if (window.location.href.indexOf("#") === -1) {
        // Get saved cookie data.
        var cookieEditMode = getCookie("editMode");
        setEditCheckbox(cookieEditMode === "1");
      }
      else if (hash === "") {
        setEditCheckbox(true);
      }
      else if (hash === "#viewmode") {
        setEditCheckbox(false);
      }

    }
  };

})(Drupal, drupalSettings);
