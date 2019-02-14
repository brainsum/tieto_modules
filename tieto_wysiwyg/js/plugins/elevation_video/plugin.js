/**
 * @file
 * Tieto_wysiwyg plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to:
 * - modify UI for Drupal Image plugin and behaviors.
 *
 * @ignore
 */

(function (CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('tieto_video', {
    requires: 'video_embed',

    /**
     * Init the plugin.
     */
    init: function (editor) {
      var self = this;
      setTimeout(function () {
        self.addCommand(editor);
        // This needs, because somewhy the CKeditor disables the button if the
        // command is overwritten...
        jQuery('.cke_button.cke_button__video_embed').removeClass('cke_button_disabled').addClass('cke_button_off');
      }, 30);
    },

    /**
     * Add the command to the editor.
     */
    addCommand: function (editor) {
      var self = this;
      var modalSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };

      editor.addCommand('video_embed', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the video_embed widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};
          if (editor.widgets.focused && editor.widgets.focused.name == 'video_embed') {
            existingValues = editor.widgets.focused.data.json;
          }
          Drupal.ckeditor.openDialog(editor, Drupal.url('tieto_wysiwyg/dialog/single_video/' + editor.config.drupal.format), existingValues, modalSaveWrapper, {
            title: Drupal.t('Video Embed'),
            dialogClass: 'video-embed-dialog'
          });
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      // Insert a video widget that understands how to manage a JSON encoded
      // object, provided the video_embed property is set.
      var widget = editor.document.createElement('p');
      widget.setHtml(JSON.stringify(values));
      editor.insertHtml(widget.getOuterHtml());
    }
  });

})(CKEDITOR);
