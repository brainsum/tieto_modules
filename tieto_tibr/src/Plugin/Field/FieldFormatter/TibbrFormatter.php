<?php

namespace Drupal\tieto_tibr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'dice' formatter.
 *
 * @FieldFormatter (
 *   id = "tibbr_formatter",
 *   label = @Translation("Tibbr"),
 *   field_types = {
 *     "tibbr"
 *   }
 * )
 */
class TibbrFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->tibbr_subject_id) && !empty($item->tibbr_subject_name)) {
        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => '<script id="intra_subject_plugin" src="//intra.tieto.com/subject_plugin.js" subject_id="{{tibbr_id}}" subject_name="{{tibbr_name}}"></script>',
          '#context' => [
            'tibbr_id' => $item->tibbr_subject_id,
            'tibbr_name' => $item->tibbr_subject_name,
          ],
        ];
      }
    }

    return $elements;
  }

}
