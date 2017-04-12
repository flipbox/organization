<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration;

use flipbox\organization\Plugin;
use yii\base\Module as BaseModule;

/**
 * @package flipbox\organization\modules\configuration
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property Plugin $module
 */
class Module extends BaseModule
{

    /*******************************************
     * SERVICES
     *******************************************/

    /**
     * @return null|object|services\General
     */
    public function getGeneral()
    {
        return $this->get('general');
    }

    /**
     * @return null|object|services\Layout
     */
    public function getLayout()
    {
        return $this->get('layout');
    }

    /**
     * @return null|object|services\Type
     */
    public function getType()
    {
        return $this->get('type');
    }

}
