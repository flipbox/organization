<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\web\twig\variables;

use flipbox\organization\elements\Organization;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User
{

    /**
     * @param array $criteria
     * @return \craft\elements\db\UserQuery
     */
    public function getQuery($criteria = [])
    {
        return OrganizationPlugin::getInstance()->getUser()->getQuery($criteria);
    }

    /**
     * @param array $criteria
     * @return \craft\elements\db\UserQuery
     */
    public function find($criteria = [])
    {
        return $this->getQuery($criteria);
    }

    /**
     * @param Organization $organization
     * @param array $criteria
     * @param bool $match
     * @return \craft\elements\db\UserQuery
     */
    public function getByOrganization(Organization $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getOrganization()->getMemberQuery(
            $organization,
            $criteria,
            $match
        );
    }

    /**
     * @param Organization $organization
     * @param array $criteria
     * @param bool $match
     * @return \craft\elements\db\UserQuery
     */
    public function getByOrganizationOwner(Organization $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getOrganization()->getOwnerQuery(
            $organization,
            $criteria,
            $match
        );
    }

    /**
     * @param Organization $organization
     * @param array $criteria
     * @param bool $match
     * @return \craft\elements\db\UserQuery
     */
    public function getByOrganizationUsers(Organization $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getOrganization()->getUserQuery(
            $organization,
            $criteria,
            $match
        );
    }
}
