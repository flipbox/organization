<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use Craft;
use craft\elements\User as UserElement;
use flipbox\organization\elements\Organization as OrganizationElement;
use yii\base\Component;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Permission extends Component
{

    /**
     * @param UserElement $userElement
     * @return bool
     */
    public function canCreateOrganization(UserElement $userElement)
    {

        return $userElement->admin || Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            '_createOrganization'
        );
    }

    /**
     * @param OrganizationElement $organizationElement
     * @param UserElement|null $userElement
     * @return bool
     */
    public function canUpdateOrganization(UserElement $userElement, OrganizationElement $organizationElement)
    {

        return $userElement->admin || $organizationElement->isOwner($userElement) || ($organizationElement->isUser($userElement) && Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            'updateMyOrganization'
        )) || Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            'updateAnyOrganization'
        ) || Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            'updateOrganization:' . $organizationElement->id
        );
    }

    /**
     * Determine whether a user can manage organization types
     *
     * @param UserElement $userElement
     * @param OrganizationElement $organizationElement
     * @return bool
     */
    public function canManageOrganizationTypes(UserElement $userElement, OrganizationElement $organizationElement)
    {

        // Admin -or- Owner -or- [User and Permission]
        return $userElement->admin || $organizationElement->isOwner($userElement) || ($organizationElement->isUser($userElement) && Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            'manageTypeAssociations'
        ));
    }

    /**
     * Determine whether a user can manage organization types
     *
     * @param UserElement $userElement
     * @param OrganizationElement $organizationElement
     * @return bool
     */
    public function canManageOrganizationUsers(UserElement $userElement, OrganizationElement $organizationElement)
    {

        // Admin -or- Owner -or- [User and Permission]
        return $userElement->admin || $organizationElement->isOwner($userElement) || ($organizationElement->isUser($userElement) && Craft::$app->getUserPermissions()->doesUserHavePermission(
            $userElement->id,
            'manageUserAssociations'
        ));
    }
}
