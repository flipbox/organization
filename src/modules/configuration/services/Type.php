<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\services;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use flipbox\organization\elements\db\Organization;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\models\TypeSettings as TypeSettingsModel;
use flipbox\organization\records\TypeSettings as TypeSettingsRecord;
use flipbox\organization\services\AbstractType;
use flipbox\spark\services\traits\ModelDelete;
use flipbox\spark\services\traits\ModelSave;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type extends AbstractType
{

    use ModelSave, ModelDelete {
        ModelSave::beforeSave as _beforeSave;
        ModelSave::afterSave as _afterSave;
    }

    /**
     * @param TypeModel $type
     * @param bool $isNew
     * @return bool
     * @throws Exception
     */
    public function beforeSave(TypeModel $type, bool $isNew): bool
    {
        // Get the site settings (indexed by siteId)
        $allSettings = $type->getSites();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSettings[$siteId])) {
                throw new Exception('Tried to save an organization type that is missing site settings');
            }
        }

        return $this->_beforeSave($type, $isNew);
    }

    /**
     * @param TypeModel $type
     * @param bool $isNew
     * @return bool
     */
    public function afterSave(TypeModel $type, bool $isNew)
    {
        // Update the site settings
        // -----------------------------------------------------------------

        $sitesNowWithoutUrls = [];
        $sitesWithNewUriFormats = [];

        /** @var TypeSettingsRecord[] $allOldSettingsRecords */
        $allOldSettingsRecords = [];

        if (!$isNew) {
            // Get the old category group site settings
            $allOldSettingsRecords = TypeSettingsRecord::find()
                ->where(['typeId' => $type->id])
                ->indexBy('siteId')
                ->all();
        }

        /** @var TypeSettingsModel $setting */
        foreach ($type->getSites() as $settings) {
            // Existing settings?
            if (!$settingsRecord = ArrayHelper::remove($allOldSettingsRecords, $settings->siteId)) {

                /** @var TypeSettingsRecord $settingsRecord */
                $settingsRecord = new TypeSettingsRecord();
                $settingsRecord->typeId = $settings->getTypeId();
                $settingsRecord->siteId = $settings->siteId;
            }

            // Handle the field layout
            if (!$settings->fieldLayoutId || $settingsRecord->fieldLayoutId != $settings->fieldLayoutId) {
                // Delete existing field layout
                Craft::$app->getFields()->deleteLayoutById($settingsRecord->fieldLayoutId);
            }

            // Get new field layout
            $fieldLayout = $settings->getFieldLayout();

            // Save field layout
            Craft::$app->getFields()->saveLayout($fieldLayout);

            // Update the category group record/model with the new layout ID
            $settings->fieldLayoutId = $fieldLayout->id;

            $settingsRecord->hasUrls = $settings->hasUrls;
            $settingsRecord->uriFormat = $settings->uriFormat;
            $settingsRecord->template = $settings->template;
            $settingsRecord->fieldLayoutId = $settings->fieldLayoutId;

            if (!$settingsRecord->getIsNewRecord()) {
                // Did it used to have URLs, but not anymore?
                if ($settingsRecord->isAttributeChanged('hasUrls', false) && !$settings->hasUrls) {
                    $sitesNowWithoutUrls[] = $settings->siteId;
                }

                // Does it have URLs, and has its URI format changed?
                if ($settings->hasUrls && $settingsRecord->isAttributeChanged('uriFormat', false)) {
                    $sitesWithNewUriFormats[] = $settings->siteId;
                }
            }

            $settingsRecord->save();

            // Set the ID on the model
            $settings->id = $settingsRecord->id;
        }

        // Delete old settings records
        foreach ($allOldSettingsRecords as $siteSettingsRecord) {
            $siteSettingsRecord->delete();
        }

        // Handle existing types...
        // -----------------------------------------------------------------

        if (!$isNew) {
            // Get all of the category IDs in this group
            $ids = OrganizationElement::find()
                ->andWhere(['type' => [$type->id]])
                ->status(null)
                ->limit(null)
                ->ids();

            // Drop the old URIs for any site settings that don't have URLs
            if (!empty($sitesNowWithoutUrls)) {
                Craft::$app->getDb()->createCommand()
                    ->update(
                        '{{%elements_i18n}}',
                        ['uri' => null],
                        [
                            'elementId' => $ids,
                            'siteId' => $sitesNowWithoutUrls,
                        ]
                    )
                    ->execute();
            } elseif (!empty($sitesWithNewUriFormats)) {
                foreach ($ids as $id) {
                    App::maxPowerCaptain();
                    foreach ($sitesWithNewUriFormats as $siteId) {

                        /** @var Organization $query */
                        $query = OrganizationElement::find();

                        if ($organization = $query->id($id)
                            ->siteId($siteId)
                            ->status(null)
                            ->one()
                        ) {
                            Craft::$app->getElements()->updateElementSlugAndUri($organization, false, false);
                        }
                    }
                }
            }
        }

        $this->_afterSave($type, $isNew);
    }
}
