<?php

namespace Drupal\tieto_general_ui;

use Drupal\views\EntityViewsData;

/**
 * Provides views Clone field to node type.
 */
class TietoGeneralUiViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    $data['node']['tieto_general_ui_clone_link'] = [
      'field' => [
        'title' => $this->t('Link to clone Content'),
        'help' => $this->t('Provide a clone link to the Content.'),
        'id' => 'tieto_general_ui_clone_link',
      ],
    ];

    return $data;
  }

}
