# Life-cycle management / Behat tests

## Requirements
### Packages
The `brainsum/drupal-behat-testing` is used for most of the boilerplate code.
  
### Behat config

The `Behat\MinkExtension` and `Drupal\DrupalExtension` are required.
Optionally, if you'd like to use DI for the project-specific context,
take a look at `Zalas\Behat\NoExtension` or `FriendsOfBehat\ContextServiceExtension`.

## Setup
- Initialize a new or use an existing behat suite for your project, e.g in `{project-root}/tests/behat`.
- Add a new suite, e.g `life-cycle-management`
- Add `'%paths.base%/../../web/modules/contrib/tieto_modules/tieto_lifecycle_management/tests/src/Behat/features'` to `paths`
    - Note: This assumes a standard drupal-composer install.
- Create a new context
    - Extend `Drupal\Tests\tieto_lifecycle_management\Behat\Context\BaseContext`
    - Implement the abstract functions that are needed
    - Suggestion: You should set up autoloading
        - E.g: `{project-root}/tests/behat/src` folder as `MyProject\Tests\Behat`
        - Then you can add your new context to `MyProject\Tests\Behat\Context` and use that in the `behat.yml` file
