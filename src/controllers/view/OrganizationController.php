<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers\view;

use Craft;
use craft\base\Field;
use craft\helpers\UrlHelper;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\elements\User as UserElement;
use flipbox\organization\events\RegisterOrganizationActions;
use flipbox\organization\models\Type;
use flipbox\organization\Plugin;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\organization\web\assets\element\Element;
use flipbox\spark\helpers\SiteHelper;
use yii\web\Response;

/**
 * @package flipbox\organization\controllers\view
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class OrganizationController extends AbstractViewController
{

    /** The template base path */
    const TEMPLATE_BASE = AbstractViewController::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'organization';

    /**
     * @event RegisterOrganizationActionsEvent
     */
    const EVENT_REGISTER_ORGANIZATION_ACTIONS = 'registerOrganizationActions';

    /**
     * The index view template path
     */
    const TEMPLATE_INDEX = self::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'index';

    /**
     * The index view template path
     */
    const TEMPLATE_UPSERT = self::TEMPLATE_BASE . DIRECTORY_SEPARATOR . 'upsert';

    /**
     * @return string
     */
    public function actionIndex()
    {

        // Empty variables for template
        $variables = [];

        // apply base view variables
        $this->baseVariables($variables);

        // (adhere to element index)
        $variables['groupHandle'] = '';
        $variables['groups'] = $variables['types'];

        return $this->renderTemplate(
            static::TEMPLATE_INDEX,
            $variables
        );

    }

    /**
     * @param null $identifier
     * @param OrganizationElement|null $organization
     * @return string
     */
    public function actionUpsert($identifier = null, OrganizationElement $organization = null)
    {

        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(Element::class);

        // Empty variables for template
        $variables = [];

        // apply base view variables
        $this->baseVariables($variables);

        if (is_null($organization)) {

            if (is_null($identifier)) {

                $organization = OrganizationPlugin::getInstance()->getOrganization()->create();

            } else {

                $organization = OrganizationPlugin::getInstance()->getOrganization()->get($identifier);

            }

        }

        if ($variables['types']) {
            $this->getView()->registerJs('new Craft.OrganizationTypeSwitcher();');
        }

        // Template variables
        if ($organization->id) {

            // Set the "Continue Editing" URL
            $variables['continueEditingUrl'] = $variables['baseCpPath'] . '/' . $organization->id;

            // Append title
            $variables['title'] .= ': ' . Craft::t('organization', 'Edit');

            // Breadcrumbs
            $variables['crumbs'][] = [
                'label' => Craft::t(
                        'organization',
                        "Edit"
                    ) . ": " . $organization->title,
                'url' => UrlHelper::url(
                    $variables['baseCpPath'] . '/' . $organization->id
                )
            ];

        } else {

            // Set the "Continue Editing" URL
            $variables['continueEditingUrl'] = $variables['baseCpPath'] . '/{id}';

            // Append title
            $variables['title'] .= ': ' . Craft::t('organization', 'New');

            // Breadcrumbs
            $variables['crumbs'][] = [
                'label' => Craft::t('organization', 'New'),
                'url' => UrlHelper::url($variables['baseCpPath'] . '/new')
            ];

        }

        $variables['element'] = $organization;

        $destructiveActions = [];
        if (Craft::$app->getUser()->checkPermission('deleteOrganizations')) {
            $destructiveActions[] = [
                'id' => 'delete-btn',
                'label' => Craft::t('app', 'Delete')
            ];
        }

        foreach ($organization::statuses() as $statusKey => $statusLabel) {
            $variables['statusOptions'][] = [
                'label' => Craft::t('site', $statusLabel),
                'value' => $statusKey
            ];
        }

        // Give plugins a chance to modify these, or add new ones
        $event = new RegisterOrganizationActions([
            'organization' => $organization,
            'destructiveActions' => $destructiveActions,
            'miscActions' => [],
        ]);
        $this->trigger(self::EVENT_REGISTER_ORGANIZATION_ACTIONS, $event);

        $variables['actions'] = array_filter([
            $event->miscActions,
            $event->destructiveActions,
        ]);

        $variables['tabs'] = $this->getTabs($organization);

        // The owner select input criteria
        $variables['ownerInputConfiguration'] = $this->getOwnerInputConfiguration($organization);

        // The user select input criteria
        $variables['usersInputConfiguration'] = $this->getUsersInputConfiguration($organization);
        $variables['usersIndexConfiguration'] = $this->getUsersIndexConfiguration($organization);

        return $this->renderTemplate(
            static::TEMPLATE_UPSERT,
            $variables
        );

    }

    /**
     * Switches between two entry types.
     *
     * @return Response
     */
    public function actionSwitchType(): Response
    {

        $this->requirePostPutPatchRequest();
        $this->requireAcceptsJson();

        $organizationService = OrganizationPlugin::getInstance()->getOrganization();

        // Optional attributes
        $identifier = Craft::$app->getRequest()->getBodyParam('identifier');

        /** @var OrganizationElement $organizationElement */
        if ($identifier) {

            $organizationElement = $organizationService->get($identifier);

        } else {

            $organizationElement = $organizationService->create();

        }

        // Populate
        $organizationService->populateFromRequest($organizationElement);

        // Assemble html (tabs / tab content)
        $paneHtml = $this->getView()->renderTemplate(
                'organization/_cp/organization/_tabs',
                [
                    'tabs' => $this->getTabs($organizationElement, false)
                ]
            ) . $this->getView()->renderTemplate(
                'organization/_cp/organization/_fields',
                [
                    'element' => $organizationElement,
                    'fieldLayout' => $organizationElement->getFieldLayout()
                ]
            );

        $view = $this->getView();

        return $this->asJson([
            'paneHtml' => $paneHtml,
            'headHtml' => $view->getHeadHtml(),
            'footHtml' => $view->getBodyHtml(),
        ]);
    }


    /**
     * @param OrganizationElement $organization
     * @param bool $includeUsers
     * @return array
     */
    private function getTabs(OrganizationElement $organization, bool $includeUsers = true): array
    {

        $tabs = [];

        $fieldLayout = $organization->getFieldLayout();

        $count = 1;
        foreach ($fieldLayout->getTabs() as $tab) {
            $hasErrors = false;
            if ($organization->hasErrors()) {
                /** @var Field $field */
                foreach ($tab->getFields() as $field) {
                    $hasErrors = $organization->getErrors($field->handle) ? true : $hasErrors;
                }
            }
            $tabs[] = [
                'label' => $tab->name,
                'url' => '#tab' . $count++,
                'class' => $hasErrors ? 'error' : null
            ];
        }

        if ($organization->getId() && $includeUsers) {
            $tabs['users'] = [
                'label' => Craft::t('organization', 'Users'),
                'url' => '#tabusers'
            ];
        }

        return $tabs;

    }

    /**
     * @param OrganizationElement $organization
     * @return array
     */
    private function getUsersIndexConfiguration(OrganizationElement $organization)
    {

        $selectionCriteria = [
            'enabledForSite' => null,
            'siteId' => SiteHelper::resolveSiteId($organization->siteId),
            'organization' => [
                'user' => $organization
            ]
        ];

        return [
            'elementType' => UserElement::class,
            'id' => Craft::$app->getView()->formatInputId('users'),
            'source' => 'organization',
            'context' => 'index',
            'showStatusMenu' => true,
            'showSiteMenu' => true,
            'sourceElementId' => !empty($organization->id) ? $organization->id : null,
            'storageKey' => 'organizationusersindex',
            'criteria' => $selectionCriteria
        ];
    }

    /**
     * @param OrganizationElement $organization
     * @return array
     */
    private function getUsersInputConfiguration(OrganizationElement $organization)
    {

        $selectionCriteria = [
            'enabledForSite' => null,
            'siteId' => SiteHelper::resolveSiteId($organization->siteId)
        ];

        // Association restrictions
        if (Plugin::getInstance()->getSettings()->hasAssociationRestriction()) {

            $organizationCriteria = [
                ':empty:'
            ];

            // Ignore members from the current organization
            if ($organization->id) {
                $organizationCriteria = array_merge(
                    [
                        'or',
                        $organization->id
                    ],
                    $organizationCriteria
                );
            }

            if (Plugin::getInstance()->getSettings()->memberAssociationRestriction()) {

                $selectionCriteria['organization'] = [
                    'member' => $organizationCriteria
                ];

            } elseif (Plugin::getInstance()->getSettings()->userAssociationRestriction()) {

                $selectionCriteria['organization'] = [
                    'user' => $organizationCriteria
                ];

            }

        }

        // Disable everyone already associated
        $disabledIds = Plugin::getInstance()->getOrganization()->getMemberQuery($organization, ['status' => null])->ids();

        return [
            'elementType' => UserElement::class,
            'id' => Craft::$app->getView()->formatInputId('users'),
            'storageKey' => 'organization.users',
            'name' => 'users',
            'disabledElementIds' => $disabledIds,
            'sources' => '*',
            'criteria' => $selectionCriteria,
            'sourceElementId' => !empty($organization->id) ? $organization->id : null,
            'limit' => null,
            'viewMode' => 'list',
            'selectionLabel' => Craft::t('organization', "Add a user"),
            'addAction' => $this->getBaseActionPath() . '/user/associate'
        ];

    }

    /**
     * @param OrganizationElement $organization
     * @return array
     */
    private function getOwnerInputConfiguration(OrganizationElement $organization)
    {

        $selectionCriteria = [
            'enabledForSite' => null,
            'siteId' => SiteHelper::resolveSiteId($organization->siteId)
        ];

        // Association restrictions
        if (Plugin::getInstance()->getSettings()->hasAssociationRestriction() ||
            Plugin::getInstance()->getSettings()->uniqueOwner
        ) {

            $organizationCriteria = [
                ':empty:'
            ];

            // Ignore members from the current organization
            if ($organization->id) {
                $organizationCriteria = array_merge(
                    [
                        'or',
                        $organization->id
                    ],
                    $organizationCriteria
                );
            }

            // Association restrictions
            if (Plugin::getInstance()->getSettings()->hasAssociationRestriction()) {

                $selectionCriteria['organization'] = [
                    'member' => $organizationCriteria
                ];

            } else {

                $selectionCriteria['organization'] = [
                    'owner' => $organizationCriteria
                ];

            }

        }

        // Disable everyone already associated
        $disabledIds = Plugin::getInstance()->getOrganization()->getUserQuery($organization, ['status' => null])->ids();

        return [
            'label' => Craft::t('organization', "Owner"),
            'elementType' => UserElement::class,
            'id' => Craft::$app->getView()->formatInputId('owner'),
            'fieldId' => 'ownerId',
            'storageKey' => 'organization.owner',
            'name' => 'owner',
            'disabledElementIds' => $disabledIds,
            'elements' => [$organization->getOwner()],
            'sources' => '*',
            'criteria' => $selectionCriteria,
            'sourceElementId' => !empty($organization->id) ? $organization->id : null,
            'limit' => 1,
            'required' => OrganizationPlugin::getInstance()->getSettings()->requireOwner,
            'viewMode' => 'list',
            'selectionLabel' => Craft::t('organization', "Add an owner"),
            'errors' => $organization->getErrors('ownerId')
        ];

    }


    /**
     * Set base variables used to generate template views
     *
     * @param array $variables
     */
    protected function baseVariables(array &$variables = [])
    {

        // Get base variables
        parent::baseVariables($variables);

        // Find all organization types
        $variables['types'] = OrganizationPlugin::getInstance()->getType()->findAll();

        // Create organization types option array
        $variables['typeOptions'] = [];
        /** @var Type $type */
        foreach ($variables['types'] as $type) {
            $variables['typeOptions'][] = [
                'label' => Craft::t('site', $type->name),
                'value' => $type->id
            ];
        }

        // Breadcrumbs
        $variables['crumbs'][] = [
            'label' => $variables['title'],
            'url' => UrlHelper::url($variables['baseCpPath'])
        ];

    }

}
