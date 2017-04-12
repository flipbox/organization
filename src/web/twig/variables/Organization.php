<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\web\twig\variables;

use craft\elements\User;
use flipbox\organization\elements\db\Organization as OrganizationQuery;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\Plugin;
use yii\di\ServiceLocator;

/**
 * @package flipbox\organization\web\twig\variables
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Organization extends ServiceLocator
{

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {

        parent::__construct(array_merge(
            $config,
            [
                'components' => [
                    'organization' => __NAMESPACE__ . '\Organization',
                    'settings' => __NAMESPACE__ . '\Settings',
                    'type' => __NAMESPACE__ . '\Type',
                    'user' => __NAMESPACE__ . '\User'
                ]
            ]
        ));

    }

    /**
     * Configure a query.
     *
     * @param mixed $criteria
     * @return OrganizationQuery
     */
    public function getQuery($criteria = null)
    {
        return Plugin::getInstance()->getOrganization()->getQuery($criteria);
    }

    /**
     * Configure a query.
     *
     * @param mixed $criteria
     * @return OrganizationQuery
     */
    public function find($criteria = null)
    {
        return $this->getQuery($criteria);
    }

    /**
     * @param array $config
     * @return \craft\base\Element|OrganizationElement
     */
    public function create(array $config = [])
    {
        return Plugin::getInstance()->getOrganization()->create($config);
    }

    /**
     * Sub-Variables that are accessed 'craft.organization.settings'
     *
     * @return null|object|Settings
     */
    public function getSettings()
    {
        return $this->get('settings');
    }

    /**
     * Sub-Variables that are accessed 'craft.organization.type'
     *
     * @return null|object|Type
     */
    public function getType()
    {
        return $this->get('type');
    }

    /**
     * Sub-Variables that are accessed 'craft.organization.user'
     *
     * @return null|object|User
     */
    public function getUser()
    {
        return $this->get('user');
    }

}
