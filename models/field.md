---
layout: default
title: Standard Models - Field
permalink: models/field/
---

# Models - Field
The Field model identifies the scope of an individual rating.

---

### Properties
The Field model has the following properties:

* [`id`](#id-integer-unique)
* [`handle`](#handle-string-unique)
* [`name`](#name-string)
* [`min`](#min-integer)
* [`max`](#max-integer)
* [`increment`](#increment-integer)
* [`precision`](#precision-integer)
* [`dateCreated`](#datecreated-datetime)
* [`dateUpdated`](#dateupdated-datetime)

#### id *(integer) unique*
The field Id.

#### handle *(string) unique*
The field handle.

#### name *(string)*
The field name.

#### min *(integer)*
The minimum field value.

#### max *(integer)*
The maximum field value.

#### increment *(integer)*
The number to increment the minimum field value with.

#### precision *(integer)*
The number of decimals a field value should have.

#### dateCreated *(\DateTime)*
A [DateTime][] object of the date the Collection was created.

#### dateUpdated *(\DateTime)*
A [DateTime][] object of the date the Collection was last updated.

---

### Methods
In addition to the methods found within the [Base Model][], the Field model has the following additional methods:

* [`getOptions`](#getoptions)

#### `getOptions()`
Returns an array of all available field values that this field accepts.  Field value are pragmatically created by taking the [minimum value](#min-integer) and [incrementing](#increment-integer) it until the [maximum value](#max-integer) is met.

[Base Model]: base_model_link "Base Model"
[DateTime]: base_model_link "DateTime Class"