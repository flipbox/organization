<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\records;

use flipbox\spark\records\RecordWithIdAndHandle;
use yii\db\ActiveQueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property string $name
 * @property TypeSettings[] $settings
 * @property Organization[] $organizations
 */
class Type extends RecordWithIdAndHandle
{

    const TABLE_ALIAS = Organization::TABLE_ALIAS . '_types';

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
                        'name'
                    ],
                    'required'
                ],
                [
                    [
                        'name',
                    ],
                    'string',
                    'max' => 255
                ]
            ]
        );

        return $rules;
    }

    /**
     * Returns the type’s site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSettings(): ActiveQueryInterface
    {
        return $this->hasMany(TypeSettings::class, ['typeId' => 'id']);
    }

    /**
     * Returns the organizations associated to this type.
     *
     * @return ActiveQueryInterface
     */
    public function getOrganizations(): ActiveQueryInterface
    {
        return $this->hasMany(Organization::class, ['id' => 'organizationId'])
            ->viaTable(OrganizationType::tableName(), ['typeId' => 'id']);
    }

}
