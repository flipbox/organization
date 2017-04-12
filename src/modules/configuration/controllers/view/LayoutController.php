<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers\view;

use Craft;
use craft\helpers\UrlHelper as UrlHelper;

/**
 * @package flipbox\organization\modules\configuration\controllers\view
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class LayoutController extends AbstractViewController
{

    /**
     * The index view template path
     */
    const TEMPLATE_INDEX = AbstractViewController::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'layout';

    /**
     * @return string
     */
    public function actionIndex()
    {

        // Empty variables for template
        $variables = [];

        // apply base view variables
        $this->baseVariables($variables);

        // Full page form in the CP
        $variables['fullPageForm'] = true;

        return $this->renderTemplate(static::TEMPLATE_INDEX, $variables);

    }


    /*******************************************
     * VARIABLES
     *******************************************/

    /**
     * @inheritdoc
     */
    protected function baseVariables(array &$variables = [])
    {

        // Get base variables
        parent::baseVariables($variables);

        // Page title
        $variables['title'] .= ': Default Layout';

        // Path to controller actions
        $variables['baseActionPath'] .= '/layout';

        // Path to CP
        $variables['baseCpPath'] .= '/layout';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpPath'];

        // Breadcrumbs
        $variables['crumbs'][] = [
            'label' => Craft::t('organization', 'Layout'),
            'url' => UrlHelper::url($variables['baseCpPath'])
        ];

    }

}
