<?php

namespace Drupal\tieto_wysiwyg\Component;

use Drupal\tieto_wysiwyg\Model\ImageDimensions;
use Drupal\tieto_wysiwyg\Model\PopupImage;

/**
 * Class ImageDimensionsCalculator.
 *
 * @package Drupal\tieto_wysiwyg\Service
 */
final class ImageDimensionsCalculator {

  /**
   * The first image.
   *
   * @var \Drupal\tieto_wysiwyg\Model\PopupImage
   */
  private $firstImage;

  /**
   * The second image.
   *
   * @var \Drupal\tieto_wysiwyg\Model\PopupImage
   */
  private $secondImage;

  /**
   * Equalized dimensions for the first image.
   *
   * @var \Drupal\tieto_wysiwyg\Model\ImageDimensions
   */
  private $equalizedFirstDimensions;

  /**
   * Equalized dimensions for the second image.
   *
   * @var \Drupal\tieto_wysiwyg\Model\ImageDimensions
   */
  private $equalizedSecondDimensions;

  /**
   * ImageDimensionsCalculator constructor.
   *
   * @param \Drupal\tieto_wysiwyg\Model\PopupImage $firstImage
   *   The first image.
   * @param \Drupal\tieto_wysiwyg\Model\PopupImage $secondImage
   *   The second image.
   */
  public function __construct(
    PopupImage $firstImage,
    PopupImage $secondImage
  ) {
    $this->firstImage = $firstImage;
    $this->secondImage = $secondImage;
  }

  /**
   * Setter for firstImage.
   *
   * @param \Drupal\tieto_wysiwyg\Model\PopupImage $image
   *   The image.
   *
   * @return \Drupal\tieto_wysiwyg\Component\ImageDimensionsCalculator
   *   Returns the class for chaining.
   */
  public function setFirstImage(PopupImage $image): ImageDimensionsCalculator {
    $this->firstImage = $image;
    return $this;
  }

  /**
   * Setter for secondImage.
   *
   * @param \Drupal\tieto_wysiwyg\Model\PopupImage $image
   *   The image.
   *
   * @return \Drupal\tieto_wysiwyg\Component\ImageDimensionsCalculator
   *   Returns the class for chaining.
   */
  public function setSecondImage(PopupImage $image): ImageDimensionsCalculator {
    $this->secondImage = $image;
    return $this;
  }

  /**
   * Calculates side by side image's joint, same height.
   *
   * Sets the equalizedFirstDimensions and equalizedSecondDimensions fields
   * of this object.
   *
   * @param int $maxWidth
   *   Container max width.
   *
   * @throws \RuntimeException
   */
  public function calculateEqualDimensions(int $maxWidth): void {
    if ($maxWidth < 1) {
      throw new \RuntimeException('Max width has to be greater than 0!');
    }

    // Determinate which image is the smaller and which is the bigger.
    $originalSmallerAW = $this->secondImage->whRatio();
    $originalBiggerH = $this->firstImage->height();
    $originalBiggerW = $this->firstImage->width();
    $originalBiggerAW = $this->firstImage->whRatio();
    if ($this->firstImage->height() <= $this->secondImage->height()) {
      $originalSmallerAW = $this->firstImage->whRatio();
      $originalBiggerH = $this->secondImage->height();
      $originalBiggerW = $this->secondImage->width();
      $originalBiggerAW = $this->secondImage->whRatio();
    }

    // Scale up the smaller to the bigger's height.
    $originalSmallerW = $originalBiggerH * $originalSmallerAW;
    $originalSmallerH = $originalBiggerH;

    // Calculate out the difference, if its less than 0 it will scale down, if
    // it's bigger than 0 if will scale up.
    $diff = $maxWidth - ($originalBiggerW + $originalSmallerW);
    // Determinate the 'removable' or 'addable' height from the images.
    $averageHeight = $diff * ((($originalSmallerH + $originalBiggerH) / ($originalSmallerW + $originalBiggerW)) / 2);
    // Add or remove the height. If $averageHeight is less than 0 it will scale
    // down, if it's greater than it's scale up.
    $originalSmallerW += $averageHeight * $originalSmallerAW;
    $originalBiggerW += $averageHeight * $originalBiggerAW;
    $originalSmallerH += $averageHeight;
    $originalBiggerH += $averageHeight;

    // Return the result in the same order as the image was set.
    if ($this->firstImage->height() <= $this->secondImage->height()) {
      $this->equalizedFirstDimensions = new ImageDimensions($originalSmallerW, $originalSmallerH);
      $this->equalizedSecondDimensions = new ImageDimensions($originalBiggerW, $originalBiggerH);
    }
    else {
      $this->equalizedFirstDimensions = new ImageDimensions($originalBiggerW, $originalBiggerH);
      $this->equalizedSecondDimensions = new ImageDimensions($originalSmallerW, $originalSmallerH);
    }
  }

  /**
   * Returns the equalized dimensions for the first image.
   *
   * @return \Drupal\tieto_wysiwyg\Model\ImageDimensions
   *   The equalized dimensions.
   */
  public function equalizedFirstDimensions(): ImageDimensions {
    return $this->equalizedFirstDimensions;
  }

  /**
   * Returns the equalized dimensions for the second image.
   *
   * @return \Drupal\tieto_wysiwyg\Model\ImageDimensions
   *   The equalized dimensions.
   */
  public function equalizedSecondDimensions(): ImageDimensions {
    return $this->equalizedSecondDimensions;
  }

}
