#!/usr/bin/env bash
# Note: This should run inside docker.

# Install composer dependencies and the site.
composer config extra.patches-file "composer.patches.json" \
  && composer config discard-changes true \
  && composer require drupal/tieto_modules:2.x-dev \
  && composer install -n \
  && cd web \
  && drush site-install --site-name="Test" --account-pass=123 --db-url=mysql://drupal:drupal@mariadb/drupal standard -y \
  && drush en \
    jquery_ui_datepicker \
    tieto_general_ui \
    tieto_link_customization \
    tieto_linkit \
    tieto_tibr \
    tieto_unpublish_ui -y \
  && drush cr
