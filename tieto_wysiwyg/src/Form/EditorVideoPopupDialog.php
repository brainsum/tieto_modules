<?php

namespace Drupal\tieto_wysiwyg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\video_embed_wysiwyg\Form\VideoEmbedDialog;

/**
 * Provides an image dialog for text editors.
 */
class EditorVideoPopupDialog extends VideoEmbedDialog {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    $form = parent::buildForm($form, $form_state, $filter_format);

    // Disable what we don't need.
    $form['settings']['responsive']['#access'] = FALSE;
    $form['settings']['height']['#access'] = FALSE;
    $form['settings']['width']['#access'] = FALSE;

    return $form;
  }

}
