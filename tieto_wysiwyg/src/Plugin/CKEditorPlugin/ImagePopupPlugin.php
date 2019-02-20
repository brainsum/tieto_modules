<?php

namespace Drupal\tieto_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "tieto_image" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tieto_image",
 *   label = @Translation("Tieto Popup for images"),
 *   module = "tieto_wysiwyg"
 * )
 */
class ImagePopupPlugin extends CKEditorPluginBase implements CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor): array {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return \drupal_get_path('module', 'tieto_wysiwyg') . '/js/plugins/tieto_image/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      \drupal_get_path('module', 'ckeditor') . '/css/plugins/drupalimagecaption/ckeditor.drupalimagecaption.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if the text format associated with this
    // text editor uses the filter_align or filter_caption filter and the
    // DrupalImage button is enabled.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = $editor->getFilterFormat();
    if (
      $format !== NULL
      && ($format->filters('filter_align')->status || $format->filters('filter_caption')->status)
    ) {
      $settings = $editor->getSettings();
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          foreach ($group['items'] as $button) {
            if ($button === 'DrupalImage') {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'DoubleImage' => [
        'label' => \t('Double Image Popup'),
        'image' => \drupal_get_path('module', 'tieto_wysiwyg') . '/js/plugins/tieto_image/icons/DoubleImage.png',
      ],
    ];
  }

}
