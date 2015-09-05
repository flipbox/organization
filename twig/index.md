---
layout: default
title: TWIG
permalink: twig/
---

# Twig Reference

Rating can be accessed via Twig template language in the following ways:

{% include tier2nav.html url=page.url recursive=false removeFirst=true %}

--- 

{% assign variableUrl = page.url|append:'variables/' %}
### Variables

Variables

{% include tier3nav.html url=variableUrl recursive=false %}

---

{% assign filterUrl = page.url|append:'filters/' %}
### Filters

{% include tier3nav.html url=filterUrl recursive=false %}
