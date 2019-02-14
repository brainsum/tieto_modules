<?php

namespace Drupal\tieto_wysiwyg\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;

/**
 * Class ImagePopup.
 *
 * @package Drupal\tieto_wysiwyg\Controller
 */
class ImagePopup extends ControllerBase {

  /**
   * Render.
   *
   * @param int $fid
   *   File id.
   * @param null|string $image_style
   *   Image style to render an image.
   *
   * @return array
   *   Return Hello string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function render($fid, $image_style = NULL) {
    /* @var \Drupal\file\Entity\File $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);

    if (!empty($image_style)) {
      $image_style = ImageStyle::load($image_style);
    }
    $image_uri = $file->getFileUri();

    if (!empty($image_style)) {
      $absolute_path = ImageStyle::load($image_style->getName())->buildUrl($image_uri);
    }
    else {
      // Get absolute path for original image.
      $absolute_path = Url::fromUri(file_create_url($image_uri))->getUri();
    }
    return [
      '#theme' => 'image_popup_details',
      '#url_popup' => $absolute_path,
    ];
  }

}
