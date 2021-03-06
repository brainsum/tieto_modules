<?php

namespace Drupal\tieto_unpublish_ui\Helper;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tieto_unpublish_ui\Form\NotificationsModalForm;
use function str_replace;

/**
 * Class NotificationsHelper.
 *
 * @package Drupal\tieto_unpublish_ui\Helper
 */
class NotificationsHelper {

  /**
   * Callback for the notification button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  public static function notificationCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $trigger = $form_state->getTriggeringElement();
    if (empty($trigger) || !isset($trigger['#attributes']['data-moderation-state'])) {
      $response->addCommand(new AlertCommand(t('Error! Triggering element cannot be found.')));
      return $response;
    }

    $displayNotifications = (isset($trigger['#displayNotification']) && $trigger['#displayNotification'] === TRUE);
    if (FALSE === $displayNotifications) {
      return $response;
    }

    $triggerState = $trigger['#attributes']['data-moderation-state'];
    $moderationState = str_replace('moderation_state_', '', $triggerState);
    $modalForm = NotificationsModalForm::buildFromParent($form, $form_state, $moderationState);

    $dialogOptions = [
      'width' => 800,
    ];
    $response->addCommand(new OpenModalDialogCommand(t('Are you sure?'), $modalForm, $dialogOptions));
    return $response;
  }

}
