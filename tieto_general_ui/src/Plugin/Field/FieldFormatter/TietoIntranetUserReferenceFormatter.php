<?php

namespace Drupal\tieto_general_ui\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity reference' formatter.
 *
 * Display user name as link to Tieto intranet.
 *
 * @FieldFormatter(
 *   id = "tieto_intranet_user_reference",
 *   label = @Translation("Tieto intranet profile link"),
 *   description = @Translation("Link to http://intra.tieto.com/profile/%mail."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TietoIntranetUserReferenceFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    /** @var \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    /** @var \Drupal\user\UserInterface $entity */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $name = $entity->getDisplayName();
      $mail = $entity->getEmail();
      $full = $entity->get('field_user_fullname')->value;
      if (\strlen($name) === 8) {
        $fullName = $entity->get('field_user_fullname')->value;
        $uri = Url::fromUri('http://intra.tieto.com/profile/' . $mail);
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $fullName ?: $name,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];
      }
      else {
        $uri = Url::fromUri('mailto:' . $mail);
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $full ?: $name,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];
      }

      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $fieldDefinition): bool {
    // This formatter is only available for users.
    return $fieldDefinition
      ->getFieldStorageDefinition()
      ->getSetting('target_type') === 'user';
  }

}
