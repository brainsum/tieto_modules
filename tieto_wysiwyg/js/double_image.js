/**
 * @file
 * Fix double image's sidebar bug.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * This helps calculate out the correct width for images.
   *
   * @package Drupal\tieto_wysiwyg\Service
   */
  function ImageHelper($width1, $height1, $width2, $height2) {
    $width1 = typeof $width1 === 'undefined' ? NULL : $width1;
    $height1 = typeof $height1 === 'undefined' ? NULL : $height1;
    $width2 = typeof $width2 === 'undefined' ? NULL : $width2;
    $height2 = typeof $height2 === 'undefined' ? NULL : $height2;

    this.setImage1($width1, $height1);
    this.setImage2($width2, $height2);
  }

  /**
   * Set image1 width and height.
   *
   * @param int|null $width
   *   Image width.
   * @param int|null $height
   *   Image height.
   */
  ImageHelper.prototype.setImage1 = function($width, $height) {
    this.image1 = {"width" : $width, "height" : $height};
    this.ratio1W = $width / $height;
    this.ratio1H = $height / $width;
  };

  /**
   * Set image2 width and height.
   *
   * @param int|null $width
   *   Image width.
   * @param int|null $height
   *   Image height.
   */
  ImageHelper.prototype.setImage2 = function($width, $height) {
    this.image2 = {"width" : $width, "height" : $height};
    this.ratio2W = $width / $height;
    this.ratio2H = $height / $width;
  };

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
   * @throws String exception.
   */
  ImageHelper.prototype.calculateEqHeight = function($maxwidth) {
    var $originalSmallerH, $originalSmallerW, $originalSmallerAW, $originalSmallerAH, $originalBiggerH, $originalBiggerW, $originalBiggerAW, $originalBiggerAH, $diff, $averageHeight;

    // Throw errors for useless data.
    if (
      typeof this.image1 !== "undefined" &&
      typeof this.image1['width'] !== "undefined" && !(this.image1['width']) && this.image1['width'] < 1 &&
      typeof this.image1['height'] !== "undefined" && !(this.image1['height']) && this.image1['height'] < 1
    ) {
      throw "Image1 width or height has illegal value!";
    }
    if (
      typeof this.image2 !== "undefined" &&
      typeof this.image2['width'] !== "undefined" && !(this.image2['width']) && this.image2['width'] < 1 &&
      typeof this.image2['height'] !== "undefined" && !(this.image2['height']) && this.image2['height'] < 1
    ) {
      throw "Image2 width or height has illegal value!";
    }
    if ($maxwidth < 1) {
      throw "Maxwidth parameter needs to be greater than or equival 1";
    }

    // Determinate which image is the smaller and which is the bigger.
    $originalSmallerH = this.image2['height'];
    $originalSmallerW = this.image2['width'];
    $originalSmallerAW = this.ratio2W;
    $originalSmallerAH = this.ratio2H;
    $originalBiggerH = this.image1['height'];
    $originalBiggerW = this.image1['width'];
    $originalBiggerAW = this.ratio1W;
    $originalBiggerAH = this.ratio1H;
    if (this.image1['height'] <= this.image2['height']) {
      $originalSmallerH = this.image1['height'];
      $originalSmallerW = this.image1['width'];
      $originalSmallerAW = this.ratio1W;
      $originalSmallerAH = this.ratio1H;
      $originalBiggerH = this.image2['height'];
      $originalBiggerW = this.image2['width'];
      $originalBiggerAW = this.ratio2W;
      $originalBiggerAH = this.ratio2H;
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
    if (this.image1['height'] <= this.image2['height']) {
      return {
        "image1" : {
          "width" : $originalSmallerW,
          "height" : $originalSmallerH
        },
        "image2" : {
          "width" : $originalBiggerW,
          "height" : $originalBiggerH
        }
      };
    }
    return {
      "image1" : {
        "width" : $originalBiggerW,
        "height" : $originalBiggerH
      },
      "image2" : {
        "width" : $originalSmallerW,
        "height" : $originalSmallerH
      }
    };
  };

  Drupal.behaviors.tieto_wysiwyg = {
    attach: function (context, settings) {
      var mainItem = $('.node__main');

      if (mainItem.length > 0) {
        // Max size of the two images.
        var imagesMaxSize = mainItem[0].getBoundingClientRect().width - 10 -
          parseFloat(mainItem.css('paddingLeft').replace('px', '')) -
          parseFloat(mainItem.css('paddingRight').replace('px', ''));

        $('.sbs-full-image').each(function (index, current) {
          var images = $(current).find("> img");
          if (images.length === 2) {
            images.on('load', function () {
              // Cache elements.
              var image1 = $(current).find('> img:eq(0)');
              var image2 = $(current).find('> img:eq(1)');
              // Read out data.
              var width1 = image1[0].naturalWidth;
              var height1 = image1[0].naturalHeight;
              var width2 = image2[0].naturalWidth;
              var height2 = image2[0].naturalHeight;

              try {
                // Calculate the new values.
                var imageHelper = new ImageHelper(width1, height1, width2, height2);
                var res = imageHelper.calculateEqHeight(imagesMaxSize);

                // Write back the new values.
                image1.width(res['image1']['width']);
                image1.height(res['image1']['height']);
                image2.width(res['image2']['width']);
                image2.height(res['image2']['height']);
              } catch (error) {
                console.error(error);
              }
            });
          }
        });
      }
    }
  };

})(jQuery, Drupal);
