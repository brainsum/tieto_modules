<?php

namespace Drupal\tieto_wysiwyg\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Class ImagePopupRenderer.
 *
 * @package Drupal\tieto_wysiwyg\Service
 */
final class ImagePopupRenderer {

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  private $fileStorage;

  /**
   * The image style storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  private $imageStyleStorage;

  /**
   * ImagePopupRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
  }

  /**
   * Render the image.
   *
   * @param string|int $fid
   *   File ID.
   * @param string|null $imageStyleId
   *   Image style ID.
   *
   * @return array
   *   Image popup details render array.
   */
  public function render($fid, $imageStyleId = NULL): array {
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->fileStorage->load($fid);
    $imageUri = $file->getFileUri();
    $imageStyle = NULL;

    if ($imageStyleId !== NULL) {
      /** @var \Drupal\image\ImageStyleInterface $imageStyle */
      $imageStyle = $this->imageStyleStorage->load($imageStyleId);
      $absolutePath = $imageStyle->buildUrl($imageUri);
    }
    else {
      $absolutePath = Url::fromUri(\file_create_url($imageUri))->getUri();
    }

    return [
      '#theme' => 'image_popup_details',
      '#url_popup' => $absolutePath,
    ];
  }

}
