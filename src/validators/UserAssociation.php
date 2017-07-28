<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\validators;

use Craft;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\Organization;
use yii\validators\Validator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class UserAssociation extends Validator
{

    /**
     * @var boolean whether this validation rule should be skipped if the attribute value
     * is null or an empty string.
     */
    public $skipOnEmpty = true;

    /**
     * @param Organization $object
     * @param $attribute
     *
     * @return void
     */
    public function validateAttribute($object, $attribute)
    {

        $value = $object->{$attribute};

        // Do we have a value?
        if (!empty($value)) {
            // If a user doesn't exist, than this means we have an invalid owner element
            if (!$userModel = Craft::$app->getUsers()->getUserById($value)) {
                // Invalid status message
                $message = Craft::t('organization', 'User is of invalid type.');

                // Add error
                $this->addError($object, $attribute, $message);
            }

            // Are they already a member of another organization?
            if (OrganizationPlugin::getInstance()->getSettings()->userAssociationRestriction()) {
                $query = OrganizationPlugin::getInstance()->getOrganization()->getQuery([
                    'id' => 'not ' . $object->id,
                    'status' => null,
                    'user' => $userModel->id
                ]);

                if ($query->count()) {
                    // Invalid status message
                    $message = Craft::t('organization', 'Owner is a member of another organization.');

                    // Add error
                    $this->addError($object, $attribute, $message);
                }
            }

            // Are they already a member of another organization?
            if (OrganizationPlugin::getInstance()->getSettings()->memberAssociationRestriction()) {
                $query = OrganizationPlugin::getInstance()->getOrganization()->getQuery([
                    'id' => 'not ' . $object->id,
                    'status' => null,
                    'member' => $userModel->id
                ]);

                if ($query->count()) {
                    // Invalid status message
                    $message = Craft::t('organization', 'Owner is a member of another organization.');

                    // Add error
                    $this->addError($object, $attribute, $message);
                }
            }
        }
    }
}
