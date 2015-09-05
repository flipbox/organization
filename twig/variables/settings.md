---
layout: default
title: TWIG Variables - Settings
permalink: twig/variables/settings/
---

# Twig Variables - Settings

The `craft.rating.settings` namespace variable enables interaction with [Rating][] settings.  Commonly, these variables are used to display general plugin setting information.

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