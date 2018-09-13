<?php

namespace Drupal\tieto_unpublish_ui\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'intra:first_pub_date' meta tag.
 *
 * @MetatagTag(
 *   id = "first_pub_date",
 *   label = @Translation("First publish date"),
 *   description = @Translation("The first publish date in 'intra:first_pub_date' tag."),
 *   name = "intra:first_pub_date",
 *   group = "tieto_intra",
 *   weight = 1,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FirstPubDate extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
