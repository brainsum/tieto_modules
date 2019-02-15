# Tieto Modules

[![Build Status](https://travis-ci.org/brainsum/tieto_modules.svg?branch=master)](https://travis-ci.org/brainsum/tieto_modules)

## About

Umbrella repository for Tieto-related modules.

## Deployment
- `composer require brainsum/tieto_modules`
- Add this to the `extra` key of your composer.json
    - Note: Only add this after `require`
    - ```json
        "enable-patching": true,
        "merge-plugin": {
            "include": [
                "web/modules/contrib/tieto_modules/*/composer.json"
            ],
            "recurse": true,
            "replace": false,
            "ignore-duplicates": false,
            "merge-dev": true,
            "merge-extra": true,
            "merge-extra-deep": false,
            "merge-scripts": false
        }
        ```
- Add this to the `patches` key of your composer.json
    - ```json
        "drupal/ldap": {
            "Enable changing LDAP server on user provisioning": "web/modules/contrib/tieto_modules/patches/ldap_user-provisioning-multiple-server.patch",
            "LDAP server search filter": "web/modules/contrib/tieto_modules/patches/ldap_server_search_filter-v2.patch"
        }
        ```
- `composer update --lock`
    - Note: only use this after adding the previous keys

## Development
### Adding new modules
Make sure, that each module has:
- their own composer.json file with every dependency defined.
- every dependency defined in their *.info.yml file
- their own README.md file with relevant information (how to use, configure, build frontend assets, etc.)

Make sure, that
- the root composer.json picks up the requirements and changes from it.

### Versioning
- There is no need to add versions to any of the composer.json files (except for maybe documentation purposes)
- When you change anything, add and push a new tag
  - Try to follow [SemVer](https://semver.org/)
  - This is a Drupal 8-only package, don't prefix 8.x or similar

### Release
- When you make a change, don't forget to release it on EVERY project where used
