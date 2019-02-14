[![Build Status](https://travis-ci.org/brainsum/tieto_tibr.svg?branch=master)](https://travis-ci.org/brainsum/tieto_tibr)

# Tieto Tibr integration
Provides integration for Tibr on the Tieto intranet.
Only supports nodes.

# Installation
- Add this as a repository in your composer.json file
```
{
    "type": "package",
    "package": {
        "name": "brainsum/tieto_tibr",
        "version": "0.1.0",
        "type": "drupal-module",
        "source": {
            "url": "https://github.com/brainsum/tieto_tibr",
            "type": "git",
            "reference": "master"
        }
    }
}
```

- Require as a dependency
```composer require brainsum/tieto_tibr```
- Then enable the module

# Usage
## Setup
- Add this to your settings.php:
```
$config['tieto_tibr.settings'] = [
  // Required.
  'default_host' => 'tibr-host.com',
  // Optional.
  'alternative_hosts' => [
    // Condition => tibr host.
    'demo.example.com' => 'demo.tibr-host.com',
  ],
  'mappings' => [
    // Required. Has to be a file field and must not be empty.
    'tibr_og_image' => 'field_my_image',
    // Optional. Has to be a text field and must not be empty.
    'tibr_og_description' => 'field_description',
  ],
];
```

- Keys:
    - The ```default_host``` key is needed, that is going to be used by default.
    - The ```alternative_hosts``` key is optional. You can use that for alternative hosts.
        - E.g if you have en.example.com and de.example.com and want to use some other tibr host for the different URLs.
        - The keys in the ```alternative_hosts``` are the conditions, the values are the tibr hosts. When the condition is met (meaning that it equals the http host) the appropriate tibr host is going to be used.

- After it's set up properly, you'll need to include the ```tibr.twig.html``` in your own appropriate templates, e.g in your ```node.html.twig``` like this:
    - ```{% include "@tieto_tibr/tibr.twig.html" ignore missing %}```

## tibr.twig.html
### Variables
- tibr_og_description
    - Value from the field from the setting key ```mappings.tibr_og_description```.
- tibr_og_image_absolute_url
    - Value from the field from the setting key ```mappings.tibr_og_image```.
- tibr_init_host
    - Automatic value according to the settings.
- tibr_tunnel_html_path
    - Automatic value according to the module path.
- tibr_og_title
    - Automatic value, the title of the node.
