<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\helpers;

use Craft;
use craft\elements\User as UserElement;
use yii\base\ErrorException as Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User
{

    /*******************************************
     * FIND / GET USER ELEMENT
     *******************************************/

    /**
     * Find a user element based on id, username or email
     *
     * @param $user
     * @return UserElement|null
     */
    public static function resolve($user = 'CURRENT_USER')
    {

        if ($user instanceof UserElement) {
            return $user;

            // Current
        } elseif ('CURRENT_USER' === $user) {
            return Craft::$app->getUser()->getIdentity();

            // Model
        } elseif (is_numeric($user)) {
            return Craft::$app->getUsers()->getUserById($user);
        }

        // String
        return Craft::$app->getUsers()->getUserByUsernameOrEmail($user);
    }

    /**
     * Get a user element based on id, username or email
     * @param $user
     * @return UserElement|null
     * @throws Exception
     */
    public static function get($user = 'CURRENT_USER')
    {
        $userElement = static::resolve($user);

        if (is_null($userElement)) {
            throw new Exception(Craft::t(
                'organization',
                'No user exists with the attribute "{attribute}".',
                ['attribute' => $user]
            ));
        }

        return $userElement;
    }
}
