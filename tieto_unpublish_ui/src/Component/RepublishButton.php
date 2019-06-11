<?php

namespace Drupal\tieto_unpublish_ui\Component;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class RepublishButton.
 *
 * @package Drupal\tieto_unpublish_ui\Component
 */
class RepublishButton {

  use StringTranslationTrait;

  public const BUTTON_NAME = 'node-republish-last-published-revision';

  /**
   * The button build.
   *
   * @return array
   *   The button build.
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => $this->t('Publish this version again'),
      '#name' => static::BUTTON_NAME,
      '#id' => static::BUTTON_NAME,
      '#submit' => [
        [$this, 'submit'],
      ],
    ];
  }

  /**
   * Validation callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function validate(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (static::BUTTON_NAME !== $trigger['#name']) {
      return;
    }

    /** @var \Drupal\tieto_unpublish_ui\Service\NodeRevisionManager $nodeRevisionManager */
    $nodeRevisionManager = Drupal::service('tieto_unpublish_ui.node_revision_manager');
    /** @var \Drupal\node\NodeForm $nodeForm */
    $nodeForm = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $nodeForm->getEntity();

    $revision = $nodeRevisionManager->loadLatestPublishedRevision($node);
    if (NULL === $revision) {
      $form_state->setError($trigger, t('The content could not be reverted, no published version was found.'));
      return;
    }
    $revisionId = $revision->getRevisionId();

    $form_state->clearErrors();
    $form_state->setError($trigger, t('The content should be reverted, not saved.'));
    // Since form redirect doesn't execute on validate fail,
    // we force it with the RedirectResponse.
    $redirectUrl = Url::fromRoute('tieto_unpublish_ui.revert_node_revision', [
      'revisionId' => $revisionId,
    ])->toString(TRUE)->getGeneratedUrl();
    $response = new RedirectResponse($redirectUrl);
    $response->send();
  }

}
