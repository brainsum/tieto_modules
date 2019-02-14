<?php

namespace Drupal\tieto_wysiwyg\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Form\EditorImageDialog;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\tieto_wysiwyg\Controller\ImagePopup;
use Drupal\tieto_wysiwyg\Service\ImageHelper;

/**
 * Provides an image dialog for text editors.
 */
class EditorDoubleImagePopupDialog extends EditorImageDialog {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_double_image_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the image dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $image_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('image_element', $image_element);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the image element's attributes from form state.
      $image_element = $form_state->get('image_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    // Construct strings to use in the upload validators.
    $image_upload = $editor->getImageUploadSettings();
    if (!empty($image_upload['max_dimensions']['width']) || !empty($image_upload['max_dimensions']['height'])) {
      $max_dimensions = $image_upload['max_dimensions']['width'] . 'x' . $image_upload['max_dimensions']['height'];
    }
    else {
      $max_dimensions = 0;
    }
    $max_filesize = min(Bytes::toInt($image_upload['max_size']), file_upload_max_size());

    $existing_file = isset($image_element['data-entity-uuid']) ? \Drupal::entityManager()->loadEntityByUuid('file', $image_element['data-entity-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid_left'] = [
      '#title' => $this->t('Left Side Image'),
      '#type' => 'managed_file',
      '#upload_location' => $image_upload['scheme'] . '://' . $image_upload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$max_filesize],
        'file_validate_image_resolution' => [$max_dimensions],
      ],
      '#required' => TRUE,
    ];

    $form['attributes_left']['src'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($image_element['src']) ? $image_element['src'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($image_upload['status']) {
      $form['attributes_left']['src']['#access'] = FALSE;
      $form['attributes_left']['src']['#required'] = FALSE;
    }
    else {
      $form['fid_left']['#access'] = FALSE;
      $form['fid_left']['#required'] = FALSE;
    }

    // The alt attribute is *required*, but we allow users to opt-in to empty
    // alt attributes_left for the very rare edge cases where that is valid by
    // specifying two double quotes as the alternative text in the dialog.
    // However, that *is* stored as an empty alt attribute, so if we're editing
    // an existing image (which means the src attribute is set) and its alt
    // attribute is empty, then we show that as two double quotes in the dialog.
    // @see https://www.drupal.org/node/2307647
    $alt = isset($image_element['alt']) ? $image_element['alt'] : '';
    if ($alt === '' && !empty($image_element['src'])) {
      $alt = '""';
    }
    $form['attributes_left']['alt'] = [
      '#title' => $this->t("Left Side Image's Alternative text"),
      '#placeholder' => $this->t('Short description for the visually impaired'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#required_error' => $this->t('Alternative text is required.<br />(Only in rare cases should this be left empty. To create empty alternative text, enter <code>""</code> — two double quotes without any content).'),
      '#default_value' => $alt,
      '#maxlength' => 2048,
    ];

    // When Drupal core's filter_caption is being used, the text editor may
    // offer the ability to in-place edit the image's caption: show a toggle.
    if (isset($image_element['hasCaption']) && $editor->getFilterFormat()->filters('filter_caption')->status) {
      $form['attributes_left']['caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $image_element['hasCaption'] === 'true',
        '#parents' => ['attributes', 'hasCaption'],
      ];
    }

    $form['fid_right'] = [
      '#title' => $this->t('Right Side Image'),
      '#type' => 'managed_file',
      '#upload_location' => $image_upload['scheme'] . '://' . $image_upload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$max_filesize],
        'file_validate_image_resolution' => [$max_dimensions],
      ],
      '#required' => TRUE,
    ];

    $form['attributes_right']['src'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($image_element['src']) ? $image_element['src'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($image_upload['status']) {
      $form['attributes_right']['src']['#access'] = FALSE;
      $form['attributes_right']['src']['#required'] = FALSE;
    }
    else {
      $form['fid_right']['#access'] = FALSE;
      $form['fid_right']['#required'] = FALSE;
    }

    // The alt attribute is *required*, but we allow users to opt-in to empty
    // alt attributes_right for the very rare edge cases where that is valid by
    // specifying two double quotes as the alternative text in the dialog.
    // However, that *is* stored as an empty alt attribute, so if we're editing
    // an existing image (which means the src attribute is set) and its alt
    // attribute is empty, then we show that as two double quotes in the dialog.
    // @see https://www.drupal.org/node/2307647
    $alt = isset($image_element['alt']) ? $image_element['alt'] : '';
    if ($alt === '' && !empty($image_element['src'])) {
      $alt = '""';
    }
    $form['attributes_right']['alt'] = [
      '#title' => $this->t("Right Side Image's Alternative text"),
      '#placeholder' => $this->t('Short description for the visually impaired'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#required_error' => $this->t('Alternative text is required.<br />(Only in rare cases should this be left empty. To create empty alternative text, enter <code>""</code> — two double quotes without any content).'),
      '#default_value' => $alt,
      '#maxlength' => 2048,
    ];

    // When Drupal core's filter_caption is being used, the text editor may
    // offer the ability to in-place edit the image's caption: show a toggle.
    if (isset($image_element['hasCaption']) && $editor->getFilterFormat()->filters('filter_caption')->status) {
      $form['attributes_right']['caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $image_element['hasCaption'] === 'true',
        '#parents' => ['attributes', 'hasCaption'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid_left = $form_state->getValue(['fid_left', 0]);
    $fid_right = $form_state->getValue(['fid_right', 0]);
    if (!empty($fid_left) && !empty($fid_right)) {
      // LEFT IMAGE.
      $file_left = $this->fileStorage->load($fid_left);
      $file_url_left = file_create_url($file_left->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url_left = file_url_transform_relative($file_url_left);
      $form_state->setValue(['attributes_left', 'src'], $file_url_left);
      $form_state->setValue([
        'attributes_left',
        'data-entity-uuid',
      ], $file_left->uuid());
      $form_state->setValue(['attributes_left', 'data-entity-type'], 'file');

      // When the alt attribute is set to two double quotes, transform it to the
      // empty string: two double quotes signify "empty alt attribute". See
      // above.
      if (trim($form_state->getValue(['attributes_left', 'alt'])) === '""') {
        $form_state->setValue(['attributes_left', 'alt'], '');
      }

      $display_image_left = ImagePopup::render($fid_left);
      $absolute_path_left = $display_image_left['#url_popup'];

      // RIGHT IMAGE.
      $file_right = $this->fileStorage->load($fid_right);
      $file_url_right = file_create_url($file_right->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url_right = file_url_transform_relative($file_url_right);
      $form_state->setValue(['attributes_right', 'src'], $file_url_right);
      $form_state->setValue([
        'attributes_right',
        'data-entity-uuid',
      ], $file_right->uuid());
      $form_state->setValue(['attributes_right', 'data-entity-type'], 'file');

      // When the alt attribute is set to two double quotes, transform it to the
      // empty string: two double quotes signify "empty alt attribute". See
      // above.
      if (trim($form_state->getValue(['attributes_right', 'alt'])) === '""') {
        $form_state->setValue(['attributes_right', 'alt'], '');
      }
      $image_left = \Drupal::service('image.factory')->get($file_left->getFileUri());
      $image_right = \Drupal::service('image.factory')->get($file_right->getFileUri());

      $display_image_right = ImagePopup::render($fid_right);
      $absolute_path_right = $display_image_right['#url_popup'];

      /* @var \Drupal\tieto_wysiwyg\Service\ImageHelper $image_helper */
      $image_helper = new ImageHelper($image_left->getWidth(), $image_left->getHeight(), $image_right->getWidth(), $image_right->getHeight());
      $res = $image_helper->calculateEqHeight(630 - 10);

      // We need an outer container, or CKeditor will remove our div, and only
      // images will be inserted.
      $image_render = "<div>
        <div class=\"sbs-full-image\">
          <img data-align='left' alt='" . $form_state->getValue(['attributes_left', 'alt']) . "' data-entity-type=\"file\" data-entity-uuid='" . $file_left->uuid() . "' src='" . parse_url($absolute_path_left, PHP_URL_PATH) . "' width='" . $res['image1']['width'] . "' height='" . $res['image1']['height'] . "' />
          <img data-align='right' alt='" . $form_state->getValue(['attributes_right', 'alt']) . "' data-entity-type=\"file\" data-entity-uuid='" . $file_right->uuid() . "' src='" . parse_url($absolute_path_right, PHP_URL_PATH) . "' width='" . $res['image2']['width'] . "' height='" . $res['image2']['height'] . "' />
        </div></div>";
      $form_state->setValue('image_render', $image_render);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-image-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

}
