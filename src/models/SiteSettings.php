<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\models;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;
use flipbox\organization\elements\Organization as OrganizationElement;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class SiteSettings extends Model
{

    /**
     * @var boolean Has URLs
     */
    public $siteId;

    /**
     * @var boolean Has URLs
     */
    public $hasUrls = true;

    /**
     * @var string URL format
     */
    public $uriFormat;

    /**
     * @var string Template
     */
    public $template;

    /**
     * @var integer Default Layout Id
     */
    public $fieldLayoutId;

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
