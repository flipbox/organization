<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\models;

use Craft;
use craft\helpers\ArrayHelper;
use craft\validators\UniqueValidator;
use flipbox\organization\models\TypeSettings as TypeSettingsModel;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\Type as TypeRecord;
use flipbox\spark\helpers\SiteHelper;
use flipbox\spark\models\ModelWithIdAndHandle;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type extends ModelWithIdAndHandle
{

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string
     */
    public $uid;

    /**
     * @var TypeSettingsModel[]
     */
    private $_settings;

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
                        'name',
                        'handle'
                    ],
                    UniqueValidator::class,
                    'targetClass' => TypeRecord::class
                ],
                [
                    [
                        'sites'
                    ],
                    'required'
                ]
            ]
        );

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return Craft::t('organization', $this->name);
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $validates = parent::validate($attributeNames, $clearErrors);

        // Validate settings
        if ($attributeNames === null || in_array('sites', $attributeNames, true)) {
            foreach ($this->getSites() as $settings) {
                if (!$settings->validate(null, $clearErrors)) {
                    $validates = false;
                }
            }
        }

        return $validates;
    }

    /**
     * @param int|null $siteId
     * @return \craft\models\FieldLayout
     */
    public function getFieldLayout(int $siteId = null)
    {
        return $this->getSite($siteId)->getFieldLayout();
    }


    /**
     * @param int|null $siteId
     * @return TypeSettings
     */
    public function getSite(int $siteId = null)
    {

        $siteId = SiteHelper::resolveSiteId($siteId);

        if (!$settings = ArrayHelper::getValue($this->getSites(), $siteId)) {
            $settings = (new TypeSettingsModel([
                'siteId' => $siteId
            ]))
                ->setType($this);

            $this->_settings[$siteId] = $settings;
        }

        return $this->_settings[$siteId];
    }

    /**
     * @param TypeSettingsModel $settings
     * @return $this
     */
    public function addSite(TypeSettingsModel $settings)
    {
        $settings->setType($this);
        $this->_settings[$settings->siteId] = $settings;
        return $this;
    }


    /**
     * Returns all of the types's settings.
     *
     * @return TypeSettingsModel[]
     */
    public function getSites(): array
    {

        $this->ensureSites();

        return $this->_settings;
    }

    /**
     * Ensure all settings are loaded
     */
    private function ensureSites()
    {

        if (is_null($this->_settings)) {
            $this->setSites(
                OrganizationPlugin::getInstance()->getType()->findAllSettings($this)
            );
        }
    }

    /**
     * Sets the type's site-specific settings.
     *
     * @param TypeSettingsModel[] $siteSettings
     *
     * @return $this
     */
    public function setSites(array $siteSettings)
    {

        $this->_settings = [];

        foreach ($siteSettings as $settings) {
            if (!$settings instanceof TypeSettingsModel) {
                $settings = new TypeSettingsModel($settings);
            }

            $this->addSite($settings);
        }

        return $this;
    }
}
