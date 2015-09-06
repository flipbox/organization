---
layout: default
title: Twig
permalink: twig/
---

# Twig

Rating can be accessed via [Twig][] template language in the following ways:

{% include tier2nav.html url=page.url recursive=false removeFirst=true %}

--- 

{% assign variableUrl = page.url|append:'tags/' %}
### Tags

[Twig][] [Tags][] are the interface when interacting with Rating data.  

{% include tier3nav.html url=variableUrl recursive=false %}

---

{% assign filterUrl = page.url|append:'filters/' %}
### Filters

[Twig][] [Filters][] allow for extra manipulation of the data before it is rendered.

{% include tier3nav.html url=filterUrl recursive=false %}

[Twig]: http://twig.sensiolabs.org/ "Twig is a modern template engine for PHP"
[Tags]: http://twig.sensiolabs.org/doc/tags/index.html "Twig Tags"
[Filters]: http://twig.sensiolabs.org/doc/filters/index.html "Twig Filters"
