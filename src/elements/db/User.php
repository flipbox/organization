<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\elements\db;

use craft\elements\db\UserQuery;
use flipbox\organization\helpers\Query as QueryHelper;
use flipbox\spark\helpers\ArrayHelper;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends UserQuery
{

    /**
     * @var array
     */
    private $organization;

    /**
     * @inheritdoc
     */
    public function afterPrepare(): bool
    {

        if (null !== $this->organization) {
            QueryHelper::applyOrganizationParam(
                $this,
                $this->organization
            );
        }

        return parent::afterPrepare();
    }

    /**
     * @param $organization
     * @return static
     */
    public function setOrganization($organization)
    {

        // Default
        $this->organization = [];

        if (null === $organization) {
            return $this;
        }

        // String = members
        if (is_string($organization) || is_numeric($organization)) {
            $this->organization['member'] = $organization;
            return $this;
        }

        $this->organization = ArrayHelper::merge(
            $this->organization,
            ArrayHelper::toArray($organization)
        );

        return $this;
    }

    /**
     * @param $organization
     * @return static
     */
    public function organization($organization)
    {
        return $this->setOrganization($organization);
    }
}
