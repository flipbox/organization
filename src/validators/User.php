<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\validators;

use Craft;
use craft\elements\db\UserQuery;
use craft\elements\User as UserElement;
use craft\helpers\Json;
use yii\validators\Validator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends Validator
{
    /**
     * @inheritdoc
     * @param UserQuery $value
     */
    protected function validateValue($value)
    {
        $hasError = false;

        /** @var UserElement[] $users */
        if (null !== ($users = $value->getCachedResult())) {
            foreach ($users as $user) {
                if (!$user->id && !$user->validate()) {
                    $hasError = true;

                    Craft::warning(sprintf(
                        "Invalid user: '%s'",
                        Json::encode($user->getFirstErrors())
                    ), __METHOD__);
                }
            }
        }

        if($hasError) {
            return [Craft::t('organization', 'Invalid users.'), []];
        }

        return null;
    }
}