<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\models;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;
use craft\validators\SiteIdValidator;
use craft\validators\UriFormatValidator;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\spark\models\ModelWithId;
use yii\base\InvalidConfigException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class TypeSettings extends ModelWithId
{

    // Properties
    // =========================================================================

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var bool Has URLs?
     */
    public $hasUrls = true;

    /**
     * @var string|null URI format
     */
    public $uriFormat;

    /**
     * @var string|null Entry template
     */
    public $template;

    /**
     * @var string Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var int|null
     */
    private $typeId;

    /**
     * @var Type|null
     */
    private $type;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => OrganizationElement::class
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {

        return array_merge(
            parent::attributes(),
            [
                'typeId'
            ]
        );
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setTypeId(int $id)
    {
        $this->typeId = $id;

        if ($this->type && $this->type->getId() != $id) {
            $this->type = null;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        if (null === $this->typeId && $this->type) {
            $this->typeId = $this->type->getId();
        }

        return $this->typeId;
    }

    /**
     * Returns the type.
     *
     * @return Type
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getType(): Type
    {

        if ($this->type === null) {
            if (!$this->typeId) {
                throw new InvalidConfigException('Type Id is missing');
            }

            $this->type = OrganizationPlugin::getInstance()->getType()->getById($this->typeId);
        }

        return $this->type;
    }

    /**
     * @param Type $type
     * @return $this
     */
    public function setType(Type $type)
    {
        $this->type = $type;

        if ($this->typeId != $type->id) {
            $this->typeId = $type->id;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'uriFormat' => Craft::t('app', 'URI Format'),
                'template' => Craft::t('app', 'Template')
            ]
        );
    }

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
                        'id',
                        'typeId',
                        'siteId',
                        'fieldLayoutId'
                    ],
                    'number',
                    'integerOnly' => true
                ],
                [
                    [
                        'siteId'
                    ],
                    SiteIdValidator::class
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
                ]
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
     * Returns the owner's field layout.
     *
     * @return FieldLayout
     */
    public function getFieldLayout(): FieldLayout
    {
        return $this->getFieldLayoutBehavior()->getFieldLayout();
    }

    /**
     * Sets the owner's field layout.
     *
     * @param FieldLayout $fieldLayout
     *
     * @return void
     */
    public function setFieldLayout(FieldLayout $fieldLayout)
    {
        $fieldLayout->type = OrganizationElement::class;
        $this->getFieldLayoutBehavior()->setFieldLayout($fieldLayout);
    }

    /**
     * @return null|\yii\base\Behavior|FieldLayoutBehavior
     */
    private function getFieldLayoutBehavior()
    {
        $this->ensureBehaviors();
        return $this->getBehavior('fieldLayout');
    }
}
