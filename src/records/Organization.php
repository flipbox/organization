<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\records;

use craft\base\ElementInterface;
use craft\helpers\Db;
use craft\records\Element as ElementRecord;
use craft\records\User as UserRecord;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\organization\records\OrganizationType as OrganizationTypeRecord;
use flipbox\organization\records\User as OrganizationUserRecord;
use flipbox\organization\validators\Owner;
use flipbox\organization\validators\UserAssociation;
use flipbox\spark\records\Record;
use yii\db\ActiveQueryInterface;

/**
 * @package flipbox\organization\records
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property integer $id
 * @property integer $ownerId
 * @property string $status
 * @property string $dateJoined
 * @property ElementInterface $owner
 * @property ElementInterface $element
 * @property Type[] $types
 * @property UserRecord[] $users
 */
class Organization extends Record
{

    /**
     * The table name
     */
    const TABLE_ALIAS = 'organizations';

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {

        if ($this->getIsNewRecord()) {

            if (!$this->dateJoined) {
                $this->dateJoined = Db::prepareDateForDb(new \DateTime());
            }

        }

        return parent::beforeSave($insert);

    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'ownerId'
                    ],
                    Owner::class
                ],
                [
                    [
                        'ownerId'
                    ],
                    UserAssociation::class
                ],
                [
                    [
                        'status'
                    ],
                    'in',
                    'range' => array_keys(OrganizationPlugin::getInstance()->getSettings()->getStatuses())
                ]
            ]
        );

    }

    /**
     * Returns the organizations's element.
     *
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(ElementRecord::class, ['id' => 'id']);
    }

    /**
     * Returns the organizations's element.
     *
     * @return ActiveQueryInterface
     */
    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(ElementRecord::class, ['id' => 'ownerId']);
    }

    /**
     * Returns the organization's types.
     *
     * @return ActiveQueryInterface
     */
    public function getTypes(): ActiveQueryInterface
    {
        return $this->hasMany(Type::class, ['id' => 'typeId'])
            ->viaTable(OrganizationTypeRecord::tableName(), ['organizationId' => 'id']);
    }

    /**
     * Returns the organizations's users.
     *
     * @return ActiveQueryInterface
     */
    public function getUsers(): ActiveQueryInterface
    {
        return $this->hasMany(UserRecord::class, ['id' => 'userId'])
            ->viaTable(OrganizationUserRecord::tableName(), ['organizationId' => 'id']);
    }

}
