<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers\view;

use Craft;
use craft\helpers\UrlHelper as UrlHelper;
use flipbox\organization\models\Type as OrganizationType;
use flipbox\organization\Plugin as OrganizationPlugin;

/**
 * @package flipbox\organization\modules\configuration\controllers\view
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class TypeController extends AbstractViewController
{

    /**
     * The index view template path
     */
    const TEMPLATE_INDEX = AbstractViewController::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'type';

    /**
     * The insert/update view template path
     */
    const TEMPLATE_UPSERT = self::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'upsert';

    /**
     * Index
     *
     * @return string
     */
    public function actionIndex()
    {

        // Empty variables for template
        $variables = [];

        // apply base view variables
        $this->baseVariables($variables);

        // Find all organization types
        $variables['types'] = OrganizationPlugin::getInstance()->getType()->findAll();

        return $this->renderTemplate(static::TEMPLATE_INDEX, $variables);

    }

    /**
     * Insert/Update
     *
     * @param null $typeIdentifier
     * @param OrganizationType $organizationType
     * @return string
     */
    public function actionUpsert($typeIdentifier = null, OrganizationType $organizationType = null)
    {

        // Empty variables for template
        $variables = [];

        // Apply base view variables
        $this->baseVariables($variables);

        // Check if type is already set (failures, etc).
        if (is_null($organizationType)) {

            // Look for type id
            if (!empty($typeIdentifier)) {

                $organizationType = OrganizationPlugin::getInstance()->getType()->get($typeIdentifier);

            } else {

                $organizationType = OrganizationPlugin::getInstance()->getType()->create();

                $variables['brandNew'] = true;

            }

        }

        // Set type model
        $variables['type'] = $organizationType;

        // If new model
        if (!$organizationType->getId()) {

            // Append title
            $variables['title'] .= " - " . Craft::t('organization', 'New');

            // Append breadcrumb
            $variables['crumbs'][] = [
                'label' => Craft::t('organization', 'New'),
                'url' => UrlHelper::url($variables['baseCpPath'] . '/new')
            ];

            // Set the "Continue Editing" URL
            $variables['continueEditingUrl'] .= '/{id}';

        } else {

            // Append title
            $variables['title'] .= " - " . $organizationType->name;

            // Append breadcrumb
            $variables['crumbs'][] = [
                'label' => $variables['type']->name,
                'url' => UrlHelper::url($variables['baseCpPath'] . '/' . $variables['type']->id)
            ];

            // Set the "Continue Editing" URL
            $variables['continueEditingUrl'] .= '/' . $variables['type']->id;

        }

        // Full page form in the CP
        $variables['fullPageForm'] = true;

        return $this->renderTemplate(static::TEMPLATE_UPSERT, $variables);

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
        $variables['title'] .= ': Types';

        // Path to controller actions
        $variables['baseActionPath'] .= '/type';

        // Path to CP
        $variables['baseCpPath'] .= '/type';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpPath'];

        // Breadcrumbs
        $variables['crumbs'][] = [
            'label' => Craft::t('organization', 'Types'),
            'url' => UrlHelper::url($variables['baseCpPath'])
        ];

    }

}
