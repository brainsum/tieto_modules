# Tieto Modules

Umbrella repository for Tieto-related modules.

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
