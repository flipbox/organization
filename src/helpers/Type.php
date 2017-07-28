<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\helpers;

use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\spark\helpers\ArrayHelper;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type
{

    /**
     * @param $type
     * @return TypeModel|null
     */
    public static function resolve($type)
    {

        // Extract from config
        if (is_array($type)) {
            $type = static::getIdentifierFromConfig($type);
        }

        if (null === $type) {
            return null;
        }

        return OrganizationPlugin::getInstance()->getType()->find($type);
    }

    /**
     * @param array $config
     * @return int|string
     */
    public static function getIdentifierFromConfig(array $config)
    {

        return ArrayHelper::getValue(
            $config,
            'id',
            ArrayHelper::getValue(
                $config,
                'handle'
            )
        );
    }
}
