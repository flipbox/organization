<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\web\twig\variables;

use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\spark\models\Model;

/**
 * @package flipbox\organization\web\twig\variables
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type
{

    /**
     * @param null $criteria
     * @return Model|TypeModel|null
     */
    public function find($criteria = null)
    {
        return OrganizationPlugin::getInstance()->getType()->findByCriteria($criteria);
    }

    /**
     * @param array $criteria
     * @return Model[]|TypeModel[]
     */
    public function findAll($criteria = [])
    {
        return OrganizationPlugin::getInstance()->getType()->findAllByCriteria($criteria);
    }

}
