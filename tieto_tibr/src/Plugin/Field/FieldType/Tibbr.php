<?php

namespace Drupal\tieto_tibr\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of tibbr.
 *
 * @FieldType(
 *   id = "tibbr",
 *   label = @Translation("Tibbr Subject ID and Name fields"),
 *   module = "tieto_tibr",
 *   default_formatter = "tibbr_formatter",
 *   default_widget = "tibbr_widget",
 * )
 */
class Tibbr extends FieldItemBase {

  /**
   * Tibbr Subject Name field maximum length.
   *
   * @var int
   */
  private static $tibbrNameMaxLength = 100;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['tibbr_subject_id'] = DataDefinition::create('integer')
      ->setLabel(t('Subject ID'))
      ->setDescription(t('Tibbr Subject ID, value needs to be numeric unsinged.'))
      ->addConstraint('greater_than_1_integer');

    $properties['tibbr_subject_name'] = DataDefinition::create('string')
      ->setLabel(t('Subject Name'))
      ->setDescription(t('Tibbr subject Name, max %long character long.', ['%long' => self::$tibbrNameMaxLength]))
      ->addConstraint('max_length', ['maxLength' => self::$tibbrNameMaxLength]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('tibbr_subject_id')->getValue();
    $value2 = $this->get('tibbr_subject_name')->getValue();
    return empty($value) && empty($value2);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'tibbr_subject_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'tibbr_subject_name' => [
          'type' => 'varchar',
          'length' => self::$tibbrNameMaxLength,
          'not null' => FALSE,
        ],
      ],
    ];
  }

}
