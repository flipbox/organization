<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers\view;

use Craft;
use flipbox\organization\controllers\AbstractController;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class AbstractViewController extends AbstractController
{

    /**
     * The index view template path
     */
    const TEMPLATE_BASE = 'organization' . DIRECTORY_SEPARATOR . '_cp';

    /*******************************************
     * VARIABLES
     *******************************************/

    /**
     * @return string
     */
    protected function getBaseActionPath(): string
    {
        return OrganizationPlugin::getInstance()->getUniqueId();
    }

    /**
     * @return string
     */
    protected function getBaseCpPath(): string
    {
        return OrganizationPlugin::getInstance()->getUniqueId();
    }

    /**
     * @inheritdoc
     */
    protected function baseVariables(array &$variables = [])
    {

        $module = OrganizationPlugin::getInstance();

        // Settings
        $variables['settings'] = $module->getSettings();

        // Page title
        $variables['title'] = Craft::t('organization', "Organizations");

        // Selected tab
        $variables['selectedTab'] = '';

        // Path to controller actions
        $variables['baseActionPath'] = $this->getBaseActionPath();

        // Path to CP
        $variables['baseCpPath'] = $this->getBaseCpPath();

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $this->getBaseCpPath();
    }
}
