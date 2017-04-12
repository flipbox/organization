<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\records;

use craft\models\FieldLayout as FieldLayoutModel;
use craft\records\FieldLayout;
use craft\records\Site;
use craft\validators\UriFormatValidator;
use flipbox\spark\records\Record;
use yii\db\ActiveQueryInterface;

/**
 * @package flipbox\organization\records
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property int $id
 * @property int $typeId
 * @property int $siteId
 * @property bool $hasUrls
 * @property string $uriFormat
 * @property string $template
 * @property string $fieldLayoutId
 * @property FieldLayoutModel $fieldLayout
 * @property Type $type
 * @property Site $site
 */
class TypeSettings extends Record
{

    const TABLE_ALIAS = Type::TABLE_ALIAS . '_i18n';

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
                        'fieldLayoutId'
                    ],
                    'required'
                ],
                [
                    [
                        'fieldLayoutId'
                    ],
                    'number',
                    'integerOnly' => true
                ],
                [
                    [
                        'template'
                    ],
                    'string',
                    'max' => 500
                ],
                [
                    [
                        'uriFormat'
                    ],
                    UriFormatValidator::class
                ],
            ]
        );

        if ($this->hasUrls) {
            $rules[] = [
                [
                    'uriFormat'
                ],
                'required'
            ];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {

        // assume empty = false
        if (empty($this->hasUrls)) {
            $this->hasUrls = false;
        }

        return parent::beforeSave($insert);

    }

    /**
     * Returns the associated type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(Type::class, ['id' => 'typeId']);
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

    /**
     * Returns the associated field layout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

}
