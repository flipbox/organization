<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\web\twig\variables;

use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type
{

    /**
     * @param null $criteria
     * @return TypeModel|null
     */
    public function find($criteria = null)
    {
        return OrganizationPlugin::getInstance()->getType()->findByCriteria($criteria);
    }

    /**
     * @param array $criteria
     * @return TypeModel[]
     */
    public function findAll($criteria = [])
    {
        return OrganizationPlugin::getInstance()->getType()->findAllByCriteria($criteria);
    }
}
