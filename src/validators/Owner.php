<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\validators;

use Craft;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\organization\records\Organization;
use yii\validators\Validator;

/**
 * @package flipbox\organization\validators
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Owner extends Validator
{

    public $skipOnEmpty = false;

    /**
     * @param Organization $object
     * @param $attribute
     *
     * @return void
     */
    public function validateAttribute($object, $attribute)
    {

        $value = $object->{$attribute};

        if ($this->isEmpty($value)) {

            // Invalid status message
            $message = Craft::t('organization', 'Owner is required.');

            // Add error
            $this->addError($object, $attribute, $message);

            return;

        }

        // If a user doesn't exist, than this means we have an invalid owner element
        if (!$userModel = Craft::$app->getUsers()->getUserById($value)) {

            // Invalid status message
            $message = Craft::t('organization', 'Owner is of invalid type.');

            // Add error
            $this->addError($object, $attribute, $message);

        }

        // Are they already a member of another organization?
        if (OrganizationPlugin::getInstance()->getSettings()->uniqueOwner) {

            $query = OrganizationPlugin::getInstance()->getOrganization()->getQuery([
                'id' => 'not ' . $object->id,
                'status' => null,
                'owner' => $userModel->id
            ]);

            if ($query->count()) {

                // Invalid status message
                $message = Craft::t('organization', 'Owner is already in use.');

                // Add error
                $this->addError($object, $attribute, $message);

            }

        }

    }

}
