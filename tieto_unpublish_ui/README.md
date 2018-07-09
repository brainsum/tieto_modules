# Tieto Unpublish UI
## Setup
1. Enable the module.
2. Update (or create) the ```node-edit-form.html.twig``` with the following:
```
<div class="layout-node-form clearfix">
  {{ form|without('actions', 'advanced', 'tieto_form_footer') }}

  {% include '@tieto_unpublish_ui/node-form--actions.html.twig' ignore missing  %}
</div>
```
