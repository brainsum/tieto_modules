<?php

namespace Drupal\tieto_general_ui\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Provides a clone link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("tieto_general_ui_clone_link")
 */
class CloneLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return Url::fromRoute('node_clone.prepopulate_node', [
      'node' => $this->getEntity($row)->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('clone');
  }

}
