<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\web\twig\variables;

use flipbox\organization\models\Settings as SettingsModel;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Settings
{

    /**
     * @return SettingsModel
     */
    public function getAll()
    {
        return OrganizationPlugin::getInstance()->getSettings();
    }

    /**
     * @return bool
     */
    public function getIsOwnerRequired()
    {
        return OrganizationPlugin::getInstance()->getSettings()->requireOwner;
    }

    /**
     * @return bool
     */
    public function getIsPublicRegistrationEnabled()
    {
        return OrganizationPlugin::getInstance()->getSettings()->publicRegistration;
    }
}
