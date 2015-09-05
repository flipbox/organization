---
layout: default
title: Models
permalink: models/
---
        
# Models

There are two types of data models:

{% include tier2nav.html url=page.url recursive=false %}

---

### Standard
Standard data models are raw [Yii Models][].  The following basic models exist:
{% assign standardUrl = page.url|append:'standard/' %}
{% include tier3nav.html url=standardUrl recursive=false %}

---

### Element
Elements are [Yii Components][] that have been extended to create a universal data model that are widely used with [Craft CMS][].  The following elements exist:
{% assign elementUrl = page.url|append:'element/' %}
{% include tier3nav.html url=elementUrl recursive=false %}

[Yii Models]: yii_model_url "Yii Models"
[Yii Components]: yii_component_url "Yii Components"
[Craft CMS]: http://buildwithcraft.com/ "Craft CMS"