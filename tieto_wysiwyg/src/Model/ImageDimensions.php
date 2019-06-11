<?php

namespace Drupal\tieto_wysiwyg\Model;

use RuntimeException;

/**
 * Class ImageDimensions.
 *
 * @package Drupal\tieto_wysiwyg\Model
 */
final class ImageDimensions {

  /**
   * Image width.
   *
   * @var int
   */
  private $width;

  /**
   * Image height.
   *
   * @var int
   */
  private $height;

  /**
   * PopupImage constructor.
   *
   * @param int $width
   *   Image width.
   * @param int $height
   *   Image height.
   */
  public function __construct(
    int $width,
    int $height
  ) {
    if ($width < 1 || $height < 1) {
      throw new RuntimeException('Illegal width or height!');
    }

    $this->width = $width;
    $this->height = $height;
  }

  /**
   * Returns the image width.
   *
   * @return int
   *   The width.
   */
  public function width(): int {
    return $this->width;
  }

  /**
   * Returns the image height.
   *
   * @return int
   *   The height.
   */
  public function height(): int {
    return $this->height;
  }

}
