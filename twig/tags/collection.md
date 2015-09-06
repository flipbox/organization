---
layout: default
title: TWIG Tags - Collection
permalink: twig/tags/collection/
---

# Collection

The `craft.rating.collection` namespace variable enables interaction with [Rating Collections][Collection].  Commonly, these tags are used to display information about a particular [Collection] or [Collections][Collection].

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

[Collection]: /models/collection "Rating Collection Model"