<?php

namespace Drupal\tieto_wysiwyg\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Form\EditorImageDialog;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\tieto_wysiwyg\Model\PopupImage;
use Drupal\tieto_wysiwyg\Component\ImageDimensionsCalculator;
use Drupal\tieto_wysiwyg\Service\ImagePopupRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an image dialog for text editors.
 */
class EditorDoubleImagePopupDialog extends EditorImageDialog {

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Renderer for image popups.
   *
   * @var \Drupal\tieto_wysiwyg\Service\ImagePopupRenderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('image.factory'),
      $container->get('tieto_wysiwyg.image_popup_renderer')
    );
  }

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $fileStorage
   *   The file storage service.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory.
   * @param \Drupal\tieto_wysiwyg\Service\ImagePopupRenderer $renderer
   *   Renderer for image popups.
   */
  public function __construct(
    EntityStorageInterface $fileStorage,
    ImageFactory $imageFactory,
    ImagePopupRenderer $renderer
  ) {
    parent::__construct($fileStorage);

    $this->imageFactory = $imageFactory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'editor_double_image_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    Editor $editor = NULL
  ): array {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the image dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $imageElement = $form_state->getUserInput()['editor_object'];
      $form_state->set('image_element', $imageElement);
      $form_state->setCached();
    }
    else {
      // Retrieve the image element's attributes from form state.
      $imageElement = $form_state->get('image_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-image-dialog-form">';
    $form['#suffix'] = '</div>';

    // Construct strings to use in the upload validators.
    $imageUpload = $editor->getImageUploadSettings();
    if (!empty($imageUpload['max_dimensions']['width']) || !empty($imageUpload['max_dimensions']['height'])) {
      $maxDimensions = $imageUpload['max_dimensions']['width'] . 'x' . $imageUpload['max_dimensions']['height'];
    }
    else {
      $maxDimensions = 0;
    }
    $maxFileSize = \min(Bytes::toInt($imageUpload['max_size']), \file_upload_max_size());

    $existingFile = NULL;
    if (
      isset($imageElement['data-entity-uuid'])
      && ($existingFiles = $this->fileStorage->loadByProperties(['uuid' => $imageElement['data-entity-uuid']]))
      && !empty($existingFiles)
    ) {
      $existingFile = \reset($existingFiles);
    }

    $fid = $existingFile ? $existingFile->id() : NULL;

    $form['fid_left'] = [
      '#title' => $this->t('Left Side Image'),
      '#type' => 'managed_file',
      '#upload_location' => $imageUpload['scheme'] . '://' . $imageUpload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$maxFileSize],
        'file_validate_image_resolution' => [$maxDimensions],
      ],
      '#required' => TRUE,
    ];

    $form['attributes_left']['src'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => $imageElement['src'] ?? '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($imageUpload['status']) {
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
    $alt = $imageElement['alt'] ?? '';
    if ($alt === '' && !empty($imageElement['src'])) {
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
    if (isset($imageElement['hasCaption']) && $editor->getFilterFormat()->filters('filter_caption')->status) {
      $form['attributes_left']['caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $imageElement['hasCaption'] === 'true',
        '#parents' => ['attributes', 'hasCaption'],
      ];
    }

    $form['fid_right'] = [
      '#title' => $this->t('Right Side Image'),
      '#type' => 'managed_file',
      '#upload_location' => $imageUpload['scheme'] . '://' . $imageUpload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [$maxFileSize],
        'file_validate_image_resolution' => [$maxDimensions],
      ],
      '#required' => TRUE,
    ];

    $form['attributes_right']['src'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => $imageElement['src'] ?? '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    // If the editor has image uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($imageUpload['status']) {
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
    $alt = $imageElement['alt'] ?? '';
    if ($alt === '' && !empty($imageElement['src'])) {
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
    if (isset($imageElement['hasCaption']) && $editor->getFilterFormat()->filters('filter_caption')->status) {
      $form['attributes_right']['caption'] = [
        '#title' => $this->t('Caption'),
        '#type' => 'checkbox',
        '#default_value' => $imageElement['hasCaption'] === 'true',
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
  public function submitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fidLeft = $form_state->getValue(['fid_left', 0]);
    $fidRight = $form_state->getValue(['fid_right', 0]);
    if (!empty($fidLeft) && !empty($fidRight)) {
      // LEFT IMAGE.
      /** @var \Drupal\file\FileInterface $fileLeft */
      $fileLeft = $this->fileStorage->load($fidLeft);
      $fileUrlLeft = \file_create_url($fileLeft->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $fileUrlLeft = \file_url_transform_relative($fileUrlLeft);
      $form_state->setValue(['attributes_left', 'src'], $fileUrlLeft);
      $form_state->setValue([
        'attributes_left',
        'data-entity-uuid',
      ], $fileLeft->uuid());
      $form_state->setValue(['attributes_left', 'data-entity-type'], 'file');

      // When the alt attribute is set to two double quotes, transform it to the
      // empty string: two double quotes signify "empty alt attribute". See
      // above.
      if (\trim($form_state->getValue(['attributes_left', 'alt'])) === '""') {
        $form_state->setValue(['attributes_left', 'alt'], '');
      }

      $displayImageLeft = $this->renderer->render($fidLeft);

      // RIGHT IMAGE.
      /** @var \Drupal\file\FileInterface $fileRight */
      $fileRight = $this->fileStorage->load($fidRight);
      $fileUrlRight = \file_create_url($fileRight->getFileUri());
      // Transform absolute image URLs to relative image URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $fileUrlRight = \file_url_transform_relative($fileUrlRight);
      $form_state->setValue(['attributes_right', 'src'], $fileUrlRight);
      $form_state->setValue([
        'attributes_right',
        'data-entity-uuid',
      ], $fileRight->uuid());
      $form_state->setValue(['attributes_right', 'data-entity-type'], 'file');

      // When the alt attribute is set to two double quotes, transform it to the
      // empty string: two double quotes signify "empty alt attribute". See
      // above.
      if (\trim($form_state->getValue(['attributes_right', 'alt'])) === '""') {
        $form_state->setValue(['attributes_right', 'alt'], '');
      }

      $imageLeft = $this->imageFactory->get($fileLeft->getFileUri());
      $imageRight = $this->imageFactory->get($fileRight->getFileUri());

      $displayImageRight = $this->renderer->render($fidRight);

      $imageDimensions = new ImageDimensionsCalculator(
        new PopupImage($imageLeft->getWidth(), $imageLeft->getHeight()),
        new PopupImage($imageRight->getWidth(), $imageRight->getHeight())
      );
      $imageDimensions->calculateEqualDimensions(620);

      $leftImageTag = static::generateImageTag(
        'left',
        $form_state->getValue(['attributes_left', 'alt']),
        $fileLeft->uuid(),
        $displayImageLeft['#url_popup'],
        $imageDimensions->equalizedFirstDimensions()->width(),
        $imageDimensions->equalizedFirstDimensions()->height()
      );
      $rightImageTag = static::generateImageTag(
        'right',
        $form_state->getValue(['attributes_right', 'alt']),
        $fileRight->uuid(),
        $displayImageRight['#url_popup'],
        $imageDimensions->equalizedSecondDimensions()->width(),
        $imageDimensions->equalizedSecondDimensions()->height()
      );
      // We need an outer container, or CKeditor will remove our div, and only
      // images will be inserted.
      $form_state->setValue('image_render', "<div><div class='sbs-full-image'>{$leftImageTag}{$rightImageTag}</div></div>");
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

  /**
   * Generate an image tag as string.
   *
   * @param string $alignment
   *   Alignment (left or right).
   * @param string $altTag
   *   Alt tag.
   * @param string $fileUuid
   *   The file UUID.
   * @param string $fileAbsolutePath
   *   Abs path to the file.
   * @param int $width
   *   File width.
   * @param int $height
   *   File height.
   *
   * @return string
   *   The image tag.
   */
  private static function generateImageTag(
    string $alignment,
    string $altTag,
    string $fileUuid,
    string $fileAbsolutePath,
    int $width,
    int $height
  ): string {
    $fileSrc = \parse_url($fileAbsolutePath, PHP_URL_PATH);

    return "<img data-align='$alignment' 
                 alt='$altTag' 
                 data-entity-type='file' 
                 data-entity-uuid='$fileUuid'
                 src='$fileSrc'
                 width='$width'
                 height='$height'
                 />";
  }

}
