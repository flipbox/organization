<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\elements\db\UserQuery;
use craft\events\CancelableEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout as FieldLayoutModel;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\UrlManager;
use flipbox\organization\controllers\OrganizationController;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\fields\Organization as OrganizationField;
use flipbox\organization\fields\User as UserField;
use flipbox\organization\models\Settings as OrganizationSettings;
use flipbox\organization\models\Type as OrganizationType;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\organization\records\User as OrganizationUsersRecord;
use flipbox\organization\web\twig\variables\Organization as OrganizationVariable;
use yii\base\Event;

/**
 * @package flipbox\organization
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Plugin extends BasePlugin
{

    /**
     * @var array
     */
    public $controllerMap = [
        'save' => [
            'class' => OrganizationController::class,
            'defaultAction' => 'save'
        ],
        'delete' => [
            'class' => OrganizationController::class,
            'defaultAction' => 'delete'
        ]
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {

        // Do parent
        parent::init();

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = OrganizationField::class;
                $event->types[] = UserField::class;
            }
        );

        // Register our elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = OrganizationElement::class;
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            [self::class, 'onRegisterCpUrlRules']
        );

    }


    /*******************************************
     * EVENTS
     *******************************************/

    /**
     * @param RegisterUrlRulesEvent $event
     */
    public static function onRegisterCpUrlRules(RegisterUrlRulesEvent $event)
    {

        $event->rules = array_merge(
            $event->rules,
            [
                // SETTINGS
                'organization/configuration' => 'organization/configuration/view/general/index',
                'organization/configuration/layout' => 'organization/configuration/view/layout/index',
                'organization/configuration/type' => 'organization/configuration/view/type/index',
                'organization/configuration/type/new' => 'organization/configuration/view/type/upsert',
                'organization/configuration/type/<typeIdentifier:\d+>' => 'organization/configuration/view/type/upsert',

                // ORGANIZATION
                'organization' => 'organization/view/organization/index',
                'organization/new/<typeIdentifier:{handle}>' => 'organization/view/organization/upsert',
                'organization/new' => 'organization/view/organization/upsert',
                'organization/<identifier:\d+>' => 'organization/view/organization/upsert'

            ]
        );

    }
    
    /**
     * Returns the component definition that should be registered on the
     * [[\craft\web\twig\variables\CraftVariable]] instance for this plugin’s handle.
     *
     * @return mixed|null The component definition to be registered.
     * It can be any of the formats supported by [[\yii\di\ServiceLocator::set()]].
     */
    public function defineTemplateComponent()
    {
        return OrganizationVariable::class;
    }

    /**
     * @inheritdoc
     *
     * @return OrganizationSettings
     */
    public function getSettings()
    {
        return parent::getSettings();
    }

    /**
     * @return OrganizationSettings
     */
    protected function createSettingsModel()
    {
        return new OrganizationSettings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {

        Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl('organization/configuration')
        );

        Craft::$app->end();

    }

    /**
     * Delete any existing field layouts, and create default settings
     */
    public function afterInstall()
    {

        // Create default field layout
        $fieldLayout = new FieldLayoutModel();
        $fieldLayout->type = self::class;

        // Delete existing layouts
        Craft::$app->getFields()->deleteLayoutsByType($fieldLayout->type);

        // Delete existing layouts
        Craft::$app->getFields()->deleteLayoutsByType(OrganizationType::class);

        // Save layout
        Craft::$app->getFields()->saveLayout($fieldLayout);

        // Set settings array
        $settings = [
            'fieldLayoutId' => $fieldLayout->id
        ];

        Craft::$app->getPlugins()->savePluginSettings(
            $this,
            $settings
        );

        // Do parent
        parent::afterInstall();

    }

    /**
     * Remove all field layouts
     */
    public function afterUninstall()
    {

        // Delete existing layouts
        Craft::$app->getFields()->deleteLayoutsByType(self::class);

        // Delete existing layouts
        Craft::$app->getFields()->deleteLayoutsByType(OrganizationType::class);

        // Do parent
        parent::afterUninstall();

    }

    /*******************************************
     * MODULES
     *******************************************/
    /**
     * @return modules\configuration\Module
     */
    public function getConfiguration()
    {
        return $this->getModule('configuration');
    }


    /*******************************************
     * SERVICES
     *******************************************/

    /**
     * @return services\Field
     */
    public function getField()
    {
        return $this->get('field');
    }

    /**
     * @return services\Organization
     */
    public function getOrganization()
    {
        return $this->get('organization');
    }

    /**
     * @return services\Permission
     */
    public function getPermission()
    {
        return $this->get('permission');
    }

    /**
     * @return services\Type
     */
    public function getType()
    {
        return $this->get('type');
    }

    /**
     * @return services\User
     */
    public function getUser()
    {
        return $this->get('user');
    }

}
