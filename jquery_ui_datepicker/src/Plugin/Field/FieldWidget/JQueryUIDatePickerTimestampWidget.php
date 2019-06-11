<?php

namespace Drupal\jquery_ui_datepicker\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'datetime_default' widget.
 *
 * @FieldWidget(
 *   id = "jquery_ui_datepicker_timestamp",
 *   label = @Translation("jQuery UI datepicker Timestamp"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *   }
 * )
 */
class JQueryUIDatePickerTimestampWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // TODO: Add timepicker settings.
    return [
      'date_format' => 'd M y',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $formats = Drupal::entityTypeManager()->getStorage('date_format')->loadMultiple();

    $options = [];

    foreach ($formats as $format) {
      $options[$format->getPattern()] = $format->label();
    }

    $element['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date Format'),
      '#options' => $options,
      '#default_value' => $this->settings['date_format'],
    ];

    // TODO: Add timepicker settings form.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $form['#attached']['library'][] = 'jquery_ui_datepicker/datepicker';
    $element['value']['#jqdp'] = TRUE;

    $name = $this->fieldDefinition->getName() . '[' . $delta . ']' . '[value][date]';
    $timeName = $this->fieldDefinition->getName() . '[' . $delta . ']' . '[value][time]';

    $settings = $this->getSettings();

    $dateFormat = _date_format_parse($settings['date_format']);

    $element['value']['#date_date_element'] = 'textfield';
    $element['value']['#date_time_element'] = 'textfield';
    $element['value']['#date_date_format'] = $dateFormat['date'];
    $element['value']['#date_time_format'] = $dateFormat['time'];

    $element['value']['#value_callback'] = 'jquery_ui_datepicker_value_callback';

    $form['#attached']['drupalSettings']['jquery_ui_datepicker'][$name] = [
      'dateFormat' => _date_format_to_jquery_format($dateFormat['date']),
    ];

    if ($element['value']['#default_value'] instanceof DrupalDateTime) {
      $date_time_object = $element['value']['#default_value'];
    }
    else {
      $date_time_object = new DrupalDateTime();
    }

    // TODO: Update this to use configured settings.
    $form['#attached']['drupalSettings']['jquery_timepicker'][$timeName] = [
      'timeFormat' => _date_format_to_jquery_format($dateFormat['time']),
      'interval' => 60,
      'minTime' => '00:00',
      'maxTime' => '23:59',
      'startTime' => $date_time_object->format('G'),
      'dynamic' => FALSE,
      'dropdown' => TRUE,
      'scrollbar' => FALSE,
    ];

    $current_user = Drupal::currentUser();
    $profile_link = Link::createFromRoute($this->t('profile edit page'), 'entity.user.edit_form', ['user' => $current_user->id()]);
    $element['value']['#description'] = $this->t('Change timezone preference on your @profile.', ['@profile' => $profile_link->toString()]);
    if ($element['#required'] === FALSE) {
      $element['value']['#description'] .= ' ' . $this->t('Leave blank to use the time of form submission.');
    }

    return $element;
  }

}
