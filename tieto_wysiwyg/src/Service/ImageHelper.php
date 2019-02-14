<?php

namespace Drupal\tieto_wysiwyg\Service;

/**
 * This helps calculate out the correct width for images.
 *
 * @package Drupal\tieto_wysiwyg\Service
 */
class ImageHelper {

  private $image1;

  private $image2;

  private $ratio1W;
  private $ratio1H;

  private $ratio2W;
  private $ratio2H;

  /**
   * ImageHelper constructor.
   *
   * @param int|null $width1
   *   Image1 width.
   * @param int|null $height1
   *   Image1 height.
   * @param int|null $width2
   *   Image2 width.
   * @param int|null $height2
   *   Image2 height.
   */
  public function __construct($width1 = NULL, $height1 = NULL, $width2 = NULL, $height2 = NULL) {
    $this->setImage1($width1, $height1);
    $this->setImage2($width2, $height2);
  }

  /**
   * Set image1 width and height.
   *
   * @param int|null $width
   *   Image width.
   * @param int|null $height
   *   Image height.
   */
  public function setImage1($width, $height) {
    $this->image1 = ["width" => $width, "height" => $height];
    $this->ratio1W = $width / $height;
    $this->ratio1H = $height / $width;
  }

  /**
   * Set image2 width and height.
   *
   * @param int|null $width
   *   Image width.
   * @param int|null $height
   *   Image height.
   */
  public function setImage2($width, $height) {
    $this->image2 = ["width" => $width, "height" => $height];
    $this->ratio2W = $width / $height;
    $this->ratio2H = $height / $width;
  }

  /**
   * Calculates side by side image's joint, same height.
   *
   * @param int $maxwidth
   *   Container max width.
   *
   * @return array
   *   Returns images width and height.
   *   [
   *     'image1' => ['width' => 10, 'height' => 15],
   *     'image2' => ['width' => 10, 'height' => 15]
   *   ]
   *
   * @throws \Exception
   */
  public function calculateEqHeight($maxwidth) {
    // Throw errors for useless data.
    if (
      isset($this->image1) &&
      isset($this->image1['width']) && !empty($this->image1['width']) && $this->image1['width'] < 1 &&
      isset($this->image1['height']) && !empty($this->image1['height']) && $this->image1['height'] < 1
    ) {
      throw new \Exception("Image1 width or height has illegal value!");
    }
    if (
      isset($this->image2) &&
      isset($this->image2['width']) && !empty($this->image2['width']) && $this->image2['width'] < 1 &&
      isset($this->image2['height']) && !empty($this->image2['height']) && $this->image2['height'] < 1
    ) {
      throw new \Exception("Image2 width or height has illegal value!");
    }
    if ($maxwidth < 1) {
      throw new \Exception("Maxwidth parameter needs to be greater than or equival 1");
    }

    // Determinate which image is the smaller and which is the bigger.
    $originalSmallerAW = $this->ratio2W;
    $originalBiggerH = $this->image1['height'];
    $originalBiggerW = $this->image1['width'];
    $originalBiggerAW = $this->ratio1W;
    if ($this->image1['height'] <= $this->image2['height']) {
      $originalSmallerAW = $this->ratio1W;
      $originalBiggerH = $this->image2['height'];
      $originalBiggerW = $this->image2['width'];
      $originalBiggerAW = $this->ratio2W;
    }

    // Scale up the smaller to the bigger's height.
    $originalSmallerW = $originalBiggerH * $originalSmallerAW;
    $originalSmallerH = $originalBiggerH;

    // Calculate out the difference, if its less than 0 it will scale down, if
    // it's bigger than 0 if will scale up.
    $diff = $maxwidth - ($originalBiggerW + $originalSmallerW);
    // Determinate the 'removeable' or 'addable' height from the images.
    $averageHeight = $diff * ((($originalSmallerH + $originalBiggerH) / ($originalSmallerW + $originalBiggerW)) / 2);
    // Add or remove the height. If $averageHeight is less than 0 it will scale
    // down, if it's greater than it's scale up.
    $originalSmallerW += $averageHeight * $originalSmallerAW;
    $originalBiggerW += $averageHeight * $originalBiggerAW;
    $originalSmallerH += $averageHeight;
    $originalBiggerH += $averageHeight;

    // Return the result in the same order as the image was set.
    if ($this->image1['height'] <= $this->image2['height']) {
      return [
        "image1" => [
          "width" => $originalSmallerW,
          "height" => $originalSmallerH,
        ],
        "image2" => [
          "width" => $originalBiggerW,
          "height" => $originalBiggerH,
        ],
      ];
    }
    return [
      "image1" => [
        "width" => $originalBiggerW,
        "height" => $originalBiggerH,
      ],
      "image2" => [
        "width" => $originalSmallerW,
        "height" => $originalSmallerH,
      ],
    ];
  }

}
