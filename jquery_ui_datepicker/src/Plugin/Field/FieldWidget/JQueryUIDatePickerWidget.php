<?php

namespace Drupal\jquery_ui_datepicker\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'datetime_default' widget.
 *
 * @FieldWidget(
 *   id = "jquery_ui_datepicker",
 *   label = @Translation("jQuery UI datepicker"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class JQueryUIDatePickerWidget extends DateTimeDefaultWidget {

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
    $formats = \Drupal::entityTypeManager()->getStorage('date_format')->loadMultiple();

    $options = [];

    foreach ($formats as $format_name => $format) {
      $options[$format->getPattern()] = $format->label();
    }

    $element['date_format'] = [
      '#type' => 'select',
      '#title' => t('Date Format'),
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

    $date_name = $this->fieldDefinition->getName() . '[' . $delta . ']' . '[value][date]';
    $time_name = $this->fieldDefinition->getName() . '[' . $delta . ']' . '[value][time]';

    $settings = $this->getSettings();

    $date_format = _date_format_parse($settings['date_format']);

    $element['value']['#date_date_element'] = 'textfield';
    $element['value']['#date_time_element'] = 'textfield';
    $element['value']['#date_date_format'] = $date_format['date'];
    $element['value']['#date_time_format'] = $date_format['time'];

    $element['value']['#value_callback'] = 'jquery_ui_datepicker_value_callback';

    $form['#attached']['drupalSettings']['jquery_ui_datepicker'][$date_name] = [
      'dateFormat' => _date_format_to_jquery_format($date_format['date']),
    ];

    if ($element['value']['#default_value'] instanceof DrupalDateTime) {
      $date_time_object = $element['value']['#default_value'];
    }
    else {
      $date_time_object = new DrupalDateTime();
    }

    // TODO: Update this to use configured settings.
    $form['#attached']['drupalSettings']['jquery_timepicker'][$time_name] = [
      'timeFormat' => _date_format_to_jquery_format($date_format['time']),
      'interval' => 60,
      'minTime' => '00:00',
      'maxTime' => '23:59',
      'startTime' => $date_time_object->format('G'),
      'dynamic' => FALSE,
      'dropdown' => TRUE,
      'scrollbar' => FALSE,
    ];

    $current_user = \Drupal::currentUser();
    $profile_link = Link::createFromRoute($this->t('profile edit page'), 'entity.user.edit_form', ['user' => $current_user->id()]);
    $element['value']['#description'] = t('Change timezone preference on your @profile.', ['@profile' => $profile_link->toString()]);

    return $element;
  }

}
