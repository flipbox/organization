---
layout: default
title: Standard Models - Collection
permalink: /models/standard/collection/
---

# Models - Collection
The Collection model identifies the scope (or grouping) of a rating.

---

### Properties
The Collection model has the following properties:

* [`id`](#id-integer-unique)
* [`handle`](#handle-string-unique)
* [`name`](#name-string)
* [`elementType`](#elementtype-string)
* [`fieldLayoutId`](#fieldLayoutId-integer)
* [`dateUpdated`](#datecreated-datetime)
* [`dateCreated`](#dateCreated-datetime)

#### id *(integer) unique*
The Collection Id

#### handle *(string) unique*
The Collection handle

#### name *(string)*
The Collection name

#### elementType *(string)*
The Collection name

#### fieldLayoutId *(integer)*
The Collection name

#### dateCreated *(\DateTime)*
A [DateTime][] object of the date the Collection was created.

#### dateUpdated *(\DateTime)*
A [DateTime][] object of the date the Collection was last updated.

---

### Methods
In addition to the methods found within the [Base Model][], the Collection model has the following additional methods:

* [`getRatingFields`](#getratingfields-indexby--null-)
* [`setRatingFields`](#setratingfields-array-ratingfields-)

#### `getRatingFields( $indexBy = null )`
Returns an array of [FieldModel][] objects associated to this Collection.  Optionally, you can return the array indexed by a model key when passing the `indexBy` parameter.

#### `setRatingFields( array $ratingFields )`
Sets an array of [FieldModel][] objects that are to be associated to this Collection.

[Base Model]: base_model_link "Base Model"
[FieldModel]: (/models/standard/field/) "Rating Field Model"
[DateTime]: base_model_link "DateTime Class"