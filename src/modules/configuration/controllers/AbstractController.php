<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers;

use flipbox\organization\controllers\AbstractController as BaseAbstractController;
use flipbox\organization\modules\configuration\Module;

/**
 * @package flipbox\organization\modules\configuration\controllers
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class AbstractController extends BaseAbstractController
{

    /**
     * @var Module
     */
    public $module;

}
