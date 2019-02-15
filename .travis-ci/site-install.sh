#!/usr/bin/env bash
# Note: This should run inside docker.

# @todo: brainsum/tieto_modules:master should install the proper version (e.g for PR-s, etc).
# Or just copy it over from the local version. Maybe docker volume mounting would be the best.
# Install composer dependencies and the site.
composer config extra.patches-file "composer.patches.json" \
  && composer config discard-changes true \
  && composer require brainsum/tieto_modules:master \
  && composer install -n \
  && cd web \
  && drush site-install --site-name="Test" --account-pass=123 --db-url=mysql://drupal:drupal@mariadb/drupal standard -y \
  && drush en \
    jquery_ui_datepicker \
    tieto_admin_pages \
    tieto_general_ui \
    tieto_ldap \
    tieto_link_customization \
    tieto_linkit \
    tieto_tibr \
    tieto_unpublish_ui \
    tieto_wysiwyg -y \
  && drush cr
