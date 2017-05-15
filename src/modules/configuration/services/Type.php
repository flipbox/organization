<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\services;

use Craft;
use craft\events\ModelEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use flipbox\organization\elements\db\Organization;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\models\TypeSettings as TypeSettingsModel;
use flipbox\organization\records\Type as TypeRecord;
use flipbox\organization\records\TypeSettings as TypeSettingsRecord;
use flipbox\spark\services\ModelByHandle;
use flipbox\spark\services\traits\ModelDelete;
use flipbox\spark\services\traits\ModelSave;
use yii\base\Exception;

/**
 * @package flipbox\organization\modules\configuration\services
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Type extends ModelByHandle
{

    use ModelSave, ModelDelete;

    /**
     * @inheritdoc
     */
    public static function modelClass(): string
    {
        return TypeModel::class;
    }

    /**
     * @inheritdoc
     */
    public static function recordClass(): string
    {
        return TypeRecord::class;
    }

    /**
     * @param ModelEvent $event
     * @return bool
     * @throws Exception
     */
    public function onBeforeSave(ModelEvent $event)
    {

        /** @var TypeModel $type */
        $type = $event->sender;

        // Get the site settings (indexed by siteId)
        $allSettings = $type->getSites();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSettings[$siteId])) {
                throw new Exception('Tried to save an organization type that is missing site settings');
            }
        }

        return $event->isValid;

    }

    /**
     * @param ModelEvent $event
     * @return bool
     */
    public function onAfterSave(ModelEvent $event)
    {

        /** @var TypeModel $type */
        $type = $event->sender;

        /** @var bool $isNew */
        $isNew = $event->isNew;

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
                        ])
                    ->execute();

            } else if (!empty($sitesWithNewUriFormats)) {

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

        return $event->isValid;
    }

//    /**
//     * @param TypeModel $type
//     * @param bool $runValidation
//     * @return bool
//     * @throws Exception
//     */
//    public function save(TypeModel $type, bool $runValidation = true): bool
//    {
//
//        if ($runValidation && !$type->validate()) {
//
//            Craft::info('Organization type not saved due to validation error.', __METHOD__);
//
//            return false;
//        }
//
//        $isNew = !$type->id;
//
//        // The event to trigger
//        $event = new TypeEvent([
//            'type' => $type,
//            'isNew' => $isNew,
//        ]);
//
//        // Fire a 'beforeSave' event
//        $this->trigger(
//            self::EVENT_BEFORE_SAVE,
//            $event
//        );
//
//        /** @var TypeRecord $record */
//        $record = $this->toRecord($type);
//
//        // Get the site settings (indexed by siteId)
//        $allSettings = $type->getSites();
//
//        // Make sure they're all there
//        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
//            if (!isset($allSettings[$siteId])) {
//                throw new Exception('Tried to save an organization type that is missing site settings');
//            }
//        }
//
//        $transaction = RecordHelper::beginTransaction();;
//
//        try {
//
//            // Save
//            if (!$record->save()) {
//                throw new Exception('Failed to save organization type.');
//            }
//
//            // Now that we have a category group ID, save it on the model
//            if ($isNew) {
//                $type->id = $record->id;
//                $type->setDateCreated($record->dateCreated);
//            }
//
//            $type->setDateUpdated($record->dateUpdated);
//
//            // Cache it
//            $this->addToCache($type);
//
//            // Update the site settings
//            // -----------------------------------------------------------------
//
//            $sitesNowWithoutUrls = [];
//            $sitesWithNewUriFormats = [];
//
//            /** @var TypeSettingsRecord[] $allOldSettingsRecords */
//            $allOldSettingsRecords = [];
//
//            if (!$isNew) {
//
//                // Get the old category group site settings
//                $allOldSettingsRecords = TypeSettingsRecord::find()
//                    ->where(['typeId' => $type->id])
//                    ->indexBy('siteId')
//                    ->all();
//
//            }
//
//            /** @var TypeSettingsModel $setting */
//            foreach ($allSettings as $settings) {
//
//                // Existing settings?
//                if (!$settingsRecord = ArrayHelper::remove($allOldSettingsRecords, $settings->siteId)) {
//
//                    /** @var TypeSettingsRecord $settingsRecord */
//                    $settingsRecord = new TypeSettingsRecord();
//                    $settingsRecord->typeId = $settings->getTypeId();
//                    $settingsRecord->siteId = $settings->siteId;
//
//                }
//
//                // Handle the field layout
//                if (!$settings->fieldLayoutId || $settingsRecord->fieldLayoutId != $settings->fieldLayoutId) {
//
//                    // Delete existing field layout
//                    Craft::$app->getFields()->deleteLayoutById($settingsRecord->fieldLayoutId);
//
//                }
//
//                // Get new field layout
//                $fieldLayout = $settings->getFieldLayout();
//
//                // Save field layout
//                Craft::$app->getFields()->saveLayout($fieldLayout);
//
//                // Update the category group record/model with the new layout ID
//                $settings->fieldLayoutId = $fieldLayout->id;
//
//                $settingsRecord->hasUrls = $settings->hasUrls;
//                $settingsRecord->uriFormat = $settings->uriFormat;
//                $settingsRecord->template = $settings->template;
//                $settingsRecord->fieldLayoutId = $settings->fieldLayoutId;
//
//                if (!$settingsRecord->getIsNewRecord()) {
//
//                    // Did it used to have URLs, but not anymore?
//                    if ($settingsRecord->isAttributeChanged('hasUrls', false) && !$settings->hasUrls) {
//                        $sitesNowWithoutUrls[] = $settings->siteId;
//                    }
//
//                    // Does it have URLs, and has its URI format changed?
//                    if ($settings->hasUrls && $settingsRecord->isAttributeChanged('uriFormat', false)) {
//                        $sitesWithNewUriFormats[] = $settings->siteId;
//                    }
//
//                }
//
//                $settingsRecord->save();
//
//                // Set the ID on the model
//                $settings->id = $settingsRecord->id;
//
//            }
//
//            // Delete old settings records
//            foreach ($allOldSettingsRecords as $siteSettingsRecord) {
//                $siteSettingsRecord->delete();
//            }
//
//            // Handle existing types...
//            // -----------------------------------------------------------------
//
//            if (!$isNew) {
//
//                // Get all of the category IDs in this group
//                $ids = OrganizationElement::find()
//                    ->andWhere(['type' => [$type->id]])
//                    ->status(null)
//                    ->limit(null)
//                    ->ids();
//
//                // Drop the old URIs for any site settings that don't have URLs
//                if (!empty($sitesNowWithoutUrls)) {
//
//                    Craft::$app->getDb()->createCommand()
//                        ->update(
//                            '{{%elements_i18n}}',
//                            ['uri' => null],
//                            [
//                                'elementId' => $ids,
//                                'siteId' => $sitesNowWithoutUrls,
//                            ])
//                        ->execute();
//
//                } else if (!empty($sitesWithNewUriFormats)) {
//
//                    foreach ($ids as $id) {
//
//                        Craft::$app->getConfig()->maxPowerCaptain();
//
//                        foreach ($sitesWithNewUriFormats as $siteId) {
//
//                            /** @var Organization $query */
//                            $query = OrganizationElement::find();
//
//                            if ($organization = $query->id($id)
//                                ->siteId($siteId)
//                                ->status(null)
//                                ->one()
//                            ) {
//
//                                Craft::$app->getElements()->updateElementSlugAndUri($organization, false, false);
//
//                            }
//
//                        }
//
//                    }
//
//                }
//
//            }
//
//            $transaction->commit();
//
//        } catch (Exception $e) {
//
//            $transaction->rollBack();
//
//            throw $e;
//
//        }
//
//        // Fire an 'afterSaveGroup' event
//        $this->trigger(
//            self::EVENT_AFTER_SAVE,
//            $event
//        );
//
//        return true;
//
//    }

//    /**
//     * @param TypeModel $type
//     * @return bool
//     * @throws Exception
//     */
//    public function delete(TypeModel $type): bool
//    {
//
//        // The event to trigger
//        $event = new TypeEvent([
//            'type' => $type
//        ]);
//
//        // Fire a 'beforeDelete' event
//        $this->trigger(
//            self::EVENT_BEFORE_DELETE,
//            $event
//        );
//
//        $transaction = RecordHelper::beginTransaction();
//
//        try {
//
//            $allSettings = $type->getSites();
//
//            // Delete all field layouts
//            foreach ($allSettings as $setting) {
//                if ($setting->fieldLayoutId) {
//                    Craft::$app->getFields()->deleteLayoutById($setting->fieldLayoutId);
//                }
//            }
//
//            /** @var Organization $query */
//            $query = OrganizationElement::find();
//
//            $elements = $query->status(null)
//                ->enabledForSite(false)
//                ->type($type->id)
//                ->all();
//
//            foreach ($elements as $element) {
//                Craft::$app->getElements()->deleteElement($element);
//            }
//
//            Craft::$app->getDb()->createCommand()
//                ->delete(
//                    TypeRecord::tableName(),
//                    ['id' => $type->id])
//                ->execute();
//
//            $transaction->commit();
//
//        } catch (Exception $e) {
//
//            $transaction->rollBack();
//
//            throw $e;
//
//        }
//
//        // Fire an 'afterDelete' event
//        $this->trigger(
//            self::EVENT_AFTER_DELETE,
//            $event
//        );
//
//        return true;
//
//    }

}