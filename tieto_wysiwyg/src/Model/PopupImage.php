<?php

namespace Drupal\tieto_wysiwyg\Model;

/**
 * Class PopupImage.
 *
 * @package Drupal\tieto_wysiwyg\Model
 */
final class PopupImage {

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
   * Width/Height ratio of the image.
   *
   * @var float
   */
  private $whRatio;

  /**
   * Height/Width ratio of the image.
   *
   * @var float
   */
  private $hwRatio;

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
      throw new \RuntimeException('Illegal dimension for the image!');
    }

    $this->width = $width;
    $this->height = $height;

    $this->whRatio = $width / $height;
    $this->hwRatio = $height / $width;
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

  /**
   * Returns the Width/Height ratio.
   *
   * @return float
   *   The ratio.
   */
  public function whRatio(): float {
    return $this->whRatio;
  }

  /**
   * Returns the Height/Width ratio.
   *
   * @return float
   *   The ratio.
   */
  public function hwRatio(): float {
    return $this->hwRatio;
  }

}
