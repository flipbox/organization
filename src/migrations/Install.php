<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\migrations;

use craft\db\Migration;
use craft\records\Element as ElementRecord;
use craft\records\FieldLayout as FieldLayoutRecord;
use craft\records\Site as SiteRecord;
use craft\records\User as UserRecord;
use flipbox\organization\models\Settings;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\organization\records\OrganizationType as OrganizationTypeOrganizationRecord;
use flipbox\organization\records\Type as OrganizationTypeRecord;
use flipbox\organization\records\TypeSettings as OrganizationTypeSettingsRecord;
use flipbox\organization\records\User as OrganizationUserRecord;

/**
 * @package flipbox\organization\migrations
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Install extends Migration
{


    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        // Delete tables
        $this->dropTableIfExists(OrganizationUserRecord::tableName());
        $this->dropTableIfExists(OrganizationTypeOrganizationRecord::tableName());
        $this->dropTableIfExists(OrganizationTypeSettingsRecord::tableName());
        $this->dropTableIfExists(OrganizationTypeRecord::tableName());
        $this->dropTableIfExists(OrganizationRecord::tableName());

        return true;

    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {

        /** @var array of statuses $defaultStatuses */
        $defaultStatuses = Settings::defaultStatuses();

        $this->createTable(OrganizationRecord::tableName(), [
            'id' => $this->primaryKey(),
            'ownerId' => $this->integer(),
            'status' => $this->enum('status', array_keys($defaultStatuses)),
            'dateJoined' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable(OrganizationTypeRecord::tableName(), [
            'id' => $this->primaryKey(),
            'handle' => $this->string()->notNull(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable(OrganizationTypeSettingsRecord::tableName(), [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'hasUrls' => $this->boolean()->defaultValue(true)->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable(OrganizationTypeOrganizationRecord::tableName(), [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer()->notNull(),
            'organizationId' => $this->integer()->notNull(),
            'primary' => $this->boolean()->defaultValue(false)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable(OrganizationUserRecord::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'organizationId' => $this->integer()->notNull(),
            'siteId' => $this->integer(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {

        $this->createIndex(
            $this->db->getIndexName(OrganizationRecord::tableName(), 'ownerId', false, true),
            OrganizationRecord::tableName(), 'ownerId', false
        );

        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeRecord::tableName(), 'handle', true),
            OrganizationTypeRecord::tableName(), 'handle', true
        );

        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeSettingsRecord::tableName(), 'typeId', false, true),
            OrganizationTypeSettingsRecord::tableName(), 'typeId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeSettingsRecord::tableName(), 'fieldLayoutId', false, true),
            OrganizationTypeSettingsRecord::tableName(), 'fieldLayoutId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeSettingsRecord::tableName(), 'typeId,siteId', true),
            OrganizationTypeSettingsRecord::tableName(), 'typeId,siteId', true
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeSettingsRecord::tableName(), 'siteId', false, true),
            OrganizationTypeSettingsRecord::tableName(), 'siteId', false
        );

        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeOrganizationRecord::tableName(), 'typeId', false, true),
            OrganizationTypeOrganizationRecord::tableName(), 'typeId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeOrganizationRecord::tableName(), 'organizationId', false, true),
            OrganizationTypeOrganizationRecord::tableName(), 'organizationId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeOrganizationRecord::tableName(), 'typeId,organizationId', true),
            OrganizationTypeOrganizationRecord::tableName(), 'typeId,organizationId', true
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationTypeOrganizationRecord::tableName(), 'primary', false),
            OrganizationTypeOrganizationRecord::tableName(), 'primary', false
        );

        $this->createIndex(
            $this->db->getIndexName(OrganizationUserRecord::tableName(), 'userId', false, true),
            OrganizationUserRecord::tableName(), 'userId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationUserRecord::tableName(), 'siteId', false, true),
            OrganizationUserRecord::tableName(), 'siteId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationUserRecord::tableName(), 'organizationId', false, true),
            OrganizationUserRecord::tableName(), 'organizationId', false
        );
        $this->createIndex(
            $this->db->getIndexName(OrganizationUserRecord::tableName(), 'userId,organizationId,siteId', true),
            OrganizationUserRecord::tableName(), 'userId,organizationId,siteId', true
        );
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {

        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationRecord::tableName(), 'id'),
            OrganizationRecord::tableName(), 'id', ElementRecord::tableName(), 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationRecord::tableName(), 'ownerId'),
            OrganizationRecord::tableName(), 'ownerId', UserRecord::tableName(), 'id', 'CASCADE', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationTypeSettingsRecord::tableName(), 'typeId'),
            OrganizationTypeSettingsRecord::tableName(), 'typeId', OrganizationTypeRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationTypeSettingsRecord::tableName(), 'siteId'),
            OrganizationTypeSettingsRecord::tableName(), 'siteId', SiteRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationTypeSettingsRecord::tableName(), 'fieldLayoutId'),
            OrganizationTypeSettingsRecord::tableName(), 'fieldLayoutId', FieldLayoutRecord::tableName(), 'id', 'SET NULL', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationTypeOrganizationRecord::tableName(), 'typeId'),
            OrganizationTypeOrganizationRecord::tableName(), 'typeId', OrganizationTypeRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationTypeOrganizationRecord::tableName(), 'organizationId'),
            OrganizationTypeOrganizationRecord::tableName(), 'organizationId', OrganizationRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationUserRecord::tableName(), 'userId'),
            OrganizationUserRecord::tableName(), 'userId', UserRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationUserRecord::tableName(), 'siteId'),
            OrganizationUserRecord::tableName(), 'siteId', SiteRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(OrganizationUserRecord::tableName(), 'organizationId'),
            OrganizationUserRecord::tableName(), 'organizationId', OrganizationRecord::tableName(), 'id', 'CASCADE', 'CASCADE'
        );

    }

}
