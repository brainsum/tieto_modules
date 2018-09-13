<?php

namespace Drupal\tieto_unpublish_ui\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'intra:first_pub_date' meta tag.
 *
 * @MetatagTag(
 *   id = "last_pub_date",
 *   label = @Translation("Last publish date"),
 *   description = @Translation("The first publish date in 'intra:last_pub_date' tag."),
 *   name = "intra:last_pub_date",
 *   group = "tieto_intra",
 *   weight = 2,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class LastPubDate extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
