<?php

namespace Drupal\tieto_wysiwyg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Form\EditorImageDialog;

/**
 * Provides an image dialog for text editors.
 */
class EditorImagePopupDialog extends EditorImageDialog {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL): array {
    $form = parent::buildForm($form, $form_state, $editor);

    // Retrieve the image element's attributes from form state.
    $image_element = $form_state->get('image_element') ?: [];

    // When Drupal core's filter_align is being used, the text editor may
    // offer the ability to change the alignment.
    /** @var \Drupal\editor\EditorInterface $editor */
    if (
      $editor !== NULL
      && isset($image_element['data-align'])
      && $editor->getFilterFormat()->filters('filter_align')->status
    ) {
      $form['align'] = [
        '#title' => $this->t('Align'),
        '#type' => 'radios',
        '#options' => [
          'left' => $this->t('Left'),
          'center' => $this->t('Full width'),
          'right' => $this->t('Right'),
        ],
        '#default_value' => ($image_element['data-align'] === '' || $image_element['data-align'] === 'none') ? 'center' : $image_element['data-align'],
        '#wrapper_attributes' => ['class' => ['container-inline']],
        '#attributes' => ['class' => ['container-inline']],
        '#parents' => ['attributes', 'data-align'],
      ];
    }

    return $form;
  }

}
