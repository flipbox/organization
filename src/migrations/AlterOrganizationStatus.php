<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\meta\migrations;

use craft\db\Migration;
use flipbox\organization\records\Organization;

/**
 * @package flipbox\organization\migrations
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class AlterOrganizationStatus extends Migration
{

    /**
     * @var array
     */
    public $statuses;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->alterColumn(
            Organization::tableName(),
            'status',
            $this->enum('status', $this->statuses)
        );

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return false;
    }

}