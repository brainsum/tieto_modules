<?php

namespace Drupal\tieto_general_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'TietoFooterLogo' Block.
 *
 * @Block(
 *   id = "TietoFooterLogo",
 *   admin_label = @Translation("Tieto footer logo"),
 * )
 */
class TietoFooterLogo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $imagePath = '/' . \drupal_get_path('module', 'tieto_general_ui') . '/images/logofooter.png';
    return [
      '#markup' => "<img src='$imagePath' alt='Tieto logo' />",
    ];
  }

}
