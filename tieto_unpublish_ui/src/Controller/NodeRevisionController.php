<?php

namespace Drupal\tieto_unpublish_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class NodeRevisionController.
 *
 * @package Drupal\tieto_unpublish_ui\Controller
 */
class NodeRevisionController extends ControllerBase {

  /**
   * Revert a node to a given revision.
   *
   * @param string|int $revisionId
   *   The revision ID.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Redirect to the node view or HTTP error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\node\Form\NodeRevisionRevertForm::submitForm()
   */
  public function revert($revisionId) {
    // @todo: Only allow from the button?
    $revision = $this->loadNodeRevision($revisionId);
    if (NULL === $revision) {
      $this->messenger()->addError($this->t('The requested revision could not be loaded.'));
      return new BadRequestHttpException($this->t('The requested revision could not be loaded.'));
    }
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    if ($revision instanceof RevisionLogInterface) {
      $revision->setRevisionLogMessage($this->t('Reverting to a previously published version.'));
    }

    $this->messenger()->addStatus($this->t('The content has been reverted to the previously published version.'));
    $revision->save();

    $redirectUrl = Url::fromRoute('entity.node.canonical', [
      'node' => $revision->id(),
    ])->toString(TRUE)->getGeneratedUrl();
    return new TrustedRedirectResponse($redirectUrl);
  }

  /**
   * Access callback for the revert function.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result.
   */
  public function accessRevert(AccountInterface $account) {
    /*
     * @todo: better permission checks.
     * return AccessResult::allowedIf(
     *   $account->hasPermission('revert all revisions')
     * );
     */
    return AccessResult::allowed();
  }

  /**
   * Load the revision.
   *
   * @param string|int $revisionId
   *   The revision ID.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The revision.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadNodeRevision($revisionId) {
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $revision */
    return $nodeStorage->loadRevision($revisionId);
  }

}
