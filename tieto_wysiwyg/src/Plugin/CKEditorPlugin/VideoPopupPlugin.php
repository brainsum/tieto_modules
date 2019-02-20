<?php

namespace Drupal\tieto_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\video_embed_wysiwyg\Plugin\CKEditorPlugin\VideoEmbedWysiwyg;

/**
 * Defines the "tieto_wysiwyg" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tieto_video",
 *   label = @Translation("Tieto Popup for video"),
 *   module = "tieto_wysiwyg"
 * )
 */
class VideoPopupPlugin extends VideoEmbedWysiwyg {

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
    return \drupal_get_path('module', 'tieto_wysiwyg') . '/js/plugins/tieto_video/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor): bool {
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if ($button === 'video_embed') {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

}
