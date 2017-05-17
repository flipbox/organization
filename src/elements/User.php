<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\elements;

use craft\elements\db\ElementQueryInterface;
use craft\elements\User as BaseUserElement;
use flipbox\organization\elements\db\User as UserQuery;


/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends BaseUserElement
{

    /************************************************************
     * FIND / GET
     ************************************************************/

    /**
     * @inheritdoc
     *
     * @return UserQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new UserQuery(BaseUserElement::class);
    }

}
