---
layout: default
title: TWIG Tags - Settings
permalink: twig/tags/settings/
---

# Twig Tags - Settings

The `craft.rating.settings` namespace variable enables interaction with [Rating][] settings.  Commonly, these tags are used to display general plugin setting information.

## Available Methods

The following methods are available:

### `getStatuses()`

Returns an array of statuses in value => label format.

{% raw %}
~~~twig
{% set ratingStatuses = craft.rating.settings.getStatuses() %}
{% for value, label in ratingStatuses %}
    <li>Status - Label: {{ label }}, Value: {{ value }}</li>
{% endfor %}
~~~
{% endraw %}

### `getDefaultStatus()`

Returns the default status

{% raw %}
~~~twig
{% set defaultStatus = craft.rating.settings.getDefaultStatus() %}
~~~
{% endraw %}

[Rating]: /models/element/rating "Rating Element"