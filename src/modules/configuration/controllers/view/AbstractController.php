<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers\view;

use Craft;
use craft\helpers\UrlHelper;
use flipbox\organization\controllers\view\AbstractController as BaseViewController;
use flipbox\organization\modules\configuration\Module;
use flipbox\organization\modules\configuration\web\assets\Configuration as ConfigurationAssetBundle;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class AbstractController extends BaseViewController
{

    /** The template base path */
    const TEMPLATE_BASE = BaseViewController::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'configuration';

    /**
     * @var Module
     */
    public $module;

    /**
     * @inheritdoc
     */
    public function init()
    {

        // Do parent
        parent::init();

        // Register our configuration asset bundle
        Craft::$app->getView()->registerAssetBundle(ConfigurationAssetBundle::class);
    }

    /*******************************************
     * VARIABLES
     *******************************************/

    /**
     * @inheritdoc
     */
    protected function baseVariables(array &$variables = [])
    {

        parent::baseVariables($variables);

        // Page title
        $variables['title'] .= ' ' . Craft::t('organization', "Configuration");

        // Selected tab
        $variables['selectedTab'] = 'configuration';

        // Path to controller actions
        $variables['baseActionPath'] .= '/configuration';

        // Path to CP
        $variables['baseCpPath'] .= '/configuration';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpPath'];

        // Settings Breadcrumbs
        $variables['crumbs'][] = [
            'label' => Craft::t(
                'organization',
                "Configuration"
            ),
            'url' => UrlHelper::url(
                $this->module->getUniqueId()
            )
        ];
    }
}
