<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\records;

use craft\models\Site;
use craft\records\User as UserRecord;
use flipbox\organization\validators\UserAssociation;
use flipbox\spark\records\Record;
use yii\db\ActiveQueryInterface;

/**
 * @package flipbox\organization\records
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property int $id
 * @property int $userId
 * @property int $organizationId
 * @property int $siteId
 * @property int $sortOrder
 * @property UserRecord $user
 * @property Organization $organization
 * @property Site $site
 */
class User extends Record
{

    const TABLE_ALIAS = Organization::TABLE_ALIAS . '_users';

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
                        'userId'
                    ],
                    'unique',
                    'targetAttribute' => [
                        'userId',
                        'organizationId'
                    ]
                ],
                [
                    [
                        'userId'
                    ],
                    UserAssociation::class
                ]
            ]
        );

    }

    /**
     * Returns the user element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(UserRecord::class, ['id' => 'userId']);
    }

    /**
     * Returns the organization element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOrganization(): ActiveQueryInterface
    {
        return $this->hasOne(Organization::class, ['id' => 'organizationId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

}
