---
layout: default
title: Elements - Rating
permalink: models/element/rating/
---

# Elements - Rating
The Rating element represents an individual rating entry.

---

### Properties
In addition to the properties found within the [ElementQuery][], the Rating element has the following additional parameters:

* [`elementId`](#elementid-integer)
* [`ownerId`](#ownerid-integer)
* [`collectionId`](#collectionid-integer)
* [`name`](#name-string)
* [`email`](#email-string)
* [`status`](#status-string)

#### elementId *(integer)*
The Id of the associated element.

#### ownerId *(integer)*
The Id of the associated owner/author.

#### collectionId *(integer)*
The Id of the associated [collection](/models/collection)

#### name *(string)*
The name of a guest owner/author.

#### email *(string)*
The email of a guest owner/author.

#### status *(string)*
The status of the rating.

---

### Methods
In addition to the methods found within the base [Element][], the Rating element has the following additional methods:

* [`hasElement`](#haselement)
* [`getElement`](#getelement-strict--true-)
* [`setElement`](#setelement-element-)
* [`hasCollection`](#hascollection)
* [`getCollection`](#getcollection-strict--true-)
* [`setCollection`](#setcollection-collection-)
* [`hasOwner`](#hasowner)
* [`getOwner`](#getowner-strict--true-)
* [`setOwner`](#setowner-owner-)
* [`setRatingFieldValuesFromPost`](#setratingfieldvaluesfrompost-values--ratings-)
* [`setRawPostValueForRatingField`](#setrawpostvalueforratingfield-handle-value-)
* [`getRatingContentFromPost`](#getratingcontentfrompost)

#### `hasElement()`
Indicates whether an associated [Element][] is set.

#### `getElement( $strict = true )`
Returns an associated [ElementInterface][].  Optionally, you can use the `strict` argument to indicate whether exceptions should be thrown if an element does not exist.

#### `setElement( $element )`
Associates an element.  Accepts a singular reference to: [ElementInterface][], Element ID, Element Uri.

#### `hasCollection()`
Indicates whether an associated [Collection][] is set.

#### `getCollection( $strict = true )`
Returns an associated [Collection][] model.  Optionally, you can use the `strict` argument to indicate whether exceptions should be thrown if a collection does not exist.

#### `setCollection( $collection )`
Associates a collection model.  Accepts a singular reference to: [Collection][] object, Collection ID, Collection Handle.

#### `hasOwner()`
Indicates whether an associated [Owner][User Element] is set.

#### `getOwner( $strict = true )`
Returns an associated [Owner][User Element] element.  Optionally, you can use the `strict` argument to indicate whether exceptions should be thrown if a owner does not exist.

#### `setOwner( $owner )`
Associates a owner element.  Accepts a singular reference to: [User Element][], User ID, Username or Email address.

#### `setRatingFieldValuesFromPost( $values = 'ratings' )`
Set rating field values from an HTTP post request.

#### `setRawPostValueForRatingField( $handle, $value )`
Set a single rating field post value.

#### `getRatingContentFromPost()`
Get an array of rating fields post values.

[Collection]: /models/standard/collection "Rating Collection Model"
[ElementInterface]: element_interface_url "Craft Element Interface"
[Element]: element_url "Craft Element"
[ElementQuery]: element_query_url "Craft Element Query"
[User Element]: user_element_url "Craft User Element"