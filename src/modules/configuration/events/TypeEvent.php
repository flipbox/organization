<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\events;

use flipbox\organization\models\Type;
use yii\base\Event;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class TypeEvent extends Event
{
    /**
     * @var Type
     */
    public $type;

    /**
     * @var bool
     */
    public $isNew = false;
}
