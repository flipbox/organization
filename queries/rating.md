---
layout: default
title: Elements - Rating
permalink: queries/rating/
---

# Rating Query
The Rating element represents an individual rating entry.

| - | - |
| **Class** | `\craft\rating\elements\db\Rating` |
| **Usage** | ``` use craft\rating\elements\db\Rating as RatingQuery; ``` |

---

### Properties
In addition to the properties found within the [Element Query][], the Rating Query has the following additional parameters:

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
In addition to the methods found within the base [Element Query][], the Rating Query has the following additional methods:

* [`setElement`](#setelement-element-)
* [`element`](#element-element-)
* [`elementId`](#elementid-elementid-)
* [`setCollection`](#setcollection-collection-)
* [`collection`](#collection-collection-)
* [`collectionId`](#collectionid-collectionid-)
* [`setOwner`](#setowner-owner-)
* [`owner`](#owner-owner-)
* [`ownerId`](#ownerid-ownerid)

#### `setElement( $element )`
Set a conditional that is to be applied to the sub-query for the elementId property.  This method accepts a array or singular reference to: [ElementInterface][], Element ID, Element Uri and will attempt to resolve the element Id(s).

#### `element( $element )`
An alias to [`setElement`](#setelement-element-).  Primarily used to chain set properties within [Twig Templates][].

#### `elementId( $elementId )`
Set a conditional that is to be applied to the sub-query for the elementId property.  Primarily used to chain set properties.

#### `setCollection( $collection )`
Set a conditional that is to be applied to the sub-query for the collectionId property.  This method accepts a array or singular reference to: [Collection][] object, Collection ID, Collection Handle and will attempt to resolve the collection Id(s).

#### `collection( $collection )`
An alias to [`setCollection`](#setcollection-collection-).  Primarily used to chain set properties within [Twig Templates][].

#### `collectionId( $collectionId )`
Set a conditional that is to be applied to the sub-query for the collectionId property.  Primarily used to chain set properties.

#### `setOwner( $owner )`
Set a conditional that is to be applied to the sub-query for the ownerId property.  This method accepts a array or singular reference to: [User Element][], User ID, Username or Email address and will attempt to resolve the owner Id(s).

#### `owner( $owner )`
An alias to [`setOwner`](#setowner-owner-).  Primarily used to chain set properties within [Twig Templates][].

#### `ownerId( $ownerId )`
Set a conditional that is to be applied to the sub-query for the ownerId property.  Primarily used to chain set properties.

[Collection]: /models/collection "Rating Collection Model"
[ElementInterface]: http://buildwithcraft.com/3 "Craft Element Interface"
[Element]: http://buildwithcraft.com/3 "Craft Element"
[Element Query]: http://buildwithcraft.com/3 "Craft Element Query"
[User Element]: http://buildwithcraft.com/3 "Craft User Element"
[Twig Templates]: /twig "Twig Templates"