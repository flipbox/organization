<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\services;

use Craft;
use flipbox\organization\models\Settings;
use flipbox\organization\Organization as OrganizationPlugin;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
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
        foreach ($settingsModel->getSites() as $siteSettings) {
            $fieldLayout = $siteSettings->getFieldLayout();

            // Save field layout
            if (!Craft::$app->getFields()->saveLayout($fieldLayout)) {
                throw new InvalidConfigException("Unable to save field layout");
            }

            $siteSettings->fieldLayoutId = $fieldLayout->id;
        }

        return Craft::$app->getPlugins()->savePluginSettings(
            OrganizationPlugin::getInstance(),
            $settingsModel->toArray()
        );
    }
}
