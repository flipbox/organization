<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\services;

use Craft;
use flipbox\meta\migrations\AlterOrganizationStatus;
use flipbox\organization\models\Settings;
use flipbox\organization\Plugin;
use yii\base\Component;

/**
 * @package flipbox\organization\modules\configuration\services
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class General extends Component
{

    /**
     * Save plugin settings
     *
     * @param Settings $settingsModel
     * @return bool|int
     */
    public function save(Settings $settingsModel)
    {

        // Save plugin settings
        if (Craft::$app->getPlugins()->savePluginSettings(
            Plugin::getInstance(),
            $settingsModel->toArray()
        )
        ) {

            // Alter table
            return $this->_alterStatusColumn();

        }

        return false;

    }

    /**
     * @return bool
     * @throws \yii\db\Exception
     */
    private function _alterStatusColumn()
    {

        $migration = new AlterOrganizationStatus([
            'statuses' => array_keys(Plugin::getInstance()->getSettings()->getStatuses())
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();

        return true;

    }

}