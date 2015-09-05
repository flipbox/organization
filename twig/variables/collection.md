---
layout: default
title: TWIG Variables - Collection
permalink: twig/variables/collection/
---

# Collection

The `craft.rating.collection` namespace variable enables interaction with [Rating Collections][Collection].  Commonly, these variables are used to display information about a particular [Collection] or [Collections][Collection].

## Available Methods

The following methods are available:

### `find( identifier )`

Returns a [Collection Model][Collection] by its ID or handle.

{% raw %}
~~~twig
{% set collection = craft.rating.collection.find(id) %}
{% set collection = craft.rating.collection.find(handle) %}
~~~
{% endraw %}

### `findById( id )`

Returns a [Collection Model][Collection] by its ID.

{% raw %}
~~~twig
{% set collection = craft.rating.collection.findById(id) %}
~~~
{% endraw %}

### `findByHandle( handle )`

Returns a [Collection Model][Collection] by its handle.

{% raw %}
~~~twig
{% set collection = craft.rating.collection.findByHandle(handle) %}
~~~
{% endraw %}

[Collection]: /models/standard/collection "Rating Collection Model"