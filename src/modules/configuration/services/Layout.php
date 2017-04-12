<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\services;

use Craft;
use craft\helpers\Json as JsonHelper;
use craft\records\Plugin as PluginRecord;
use flipbox\organization\models\Settings;
use flipbox\organization\Plugin;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * @package flipbox\organization\modules\configuration\services
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Layout extends Component
{

    /**
     * @param Settings $settingsModel
     * @return bool
     * @throws InvalidConfigException
     */
    public function save(Settings $settingsModel)
    {

        // Delete existing field layouts
        $this->deleteFieldLayouts();

        foreach ($settingsModel->getSites() as $siteSettings) {

            // Get the field layout it
            $fieldLayout = $siteSettings->getFieldLayout();

            // Save field layout
            if (!Craft::$app->getFields()->saveLayout($fieldLayout)) {
                throw new InvalidConfigException("Unable to save field layout");
            }

            $siteSettings->fieldLayoutId = $fieldLayout->id;

        }

        return Craft::$app->getPlugins()->savePluginSettings(
            Plugin::getInstance(),
            $settingsModel->toArray()
        );

    }

    /**
     *
     */
    private function deleteFieldLayouts()
    {

        $settings = PluginRecord::find()
            ->select('settings')
            ->andWhere(['handle' => 'organization'])
            ->scalar();

        $settings = new Settings(
            JsonHelper::decodeIfJson($settings)
        );

        foreach ($settings->getSites() as $siteSettings) {

            if ($siteSettings->fieldLayoutId) {

                Craft::$app->getFields()->deleteLayoutById($siteSettings->fieldLayoutId);

            }

        }

    }


}