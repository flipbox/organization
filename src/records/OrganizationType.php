<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\records;

use flipbox\spark\records\RecordWithId;
use yii\db\ActiveQueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property integer $typeId
 * @property integer $organizationId
 * @property boolean $primary
 * @property Type $type
 * @property Organization $organization
 */
class OrganizationType extends RecordWithId
{

    const TABLE_ALIAS = Organization::TABLE_ALIAS . '_types_organizations';

    /**
     * @inheritdoc
     */
    public function rules()
    {

        $rules = array_merge(
            parent::rules(),
            [
                [
                    [
                        'typeId'
                    ],
                    'unique',
                    'targetAttribute' => [
                        'typeId',
                        'organizationId'
                    ]
                ],

            ]
        );

        // if primary, add unique rule
        if ($this->primary) {

            $rules[] = [
                [
                    'primary'
                ],
                'unique',
                'targetAttribute' => [
                    'primary',
                    'organizationId'
                ]
            ];

        }

        return $rules;

    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {

        // assume empty == false
        if (empty($this->primary)) {
            $this->primary = false;
        }

        return parent::beforeSave($insert);

    }


    /**
     * Returns the organization type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(Type::class, ['id' => 'typeId']);
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

}
