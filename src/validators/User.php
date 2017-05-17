<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\validators;

use craft\elements\db\UserQuery;
use craft\elements\User as UserElement;
use yii\validators\Validator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends Validator
{

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {

        /** @var UserQuery $value */

        $errors = [];

        /** @var UserElement[] $users */
        if (null !== ($users = $value->getCachedResult())) {

            foreach ($users as $user) {

                if (!$user->id && !$user->validate()) {

                    $errors[] = $user->getFirstErrors();

                }

            }

        }

        return $errors;

    }

}