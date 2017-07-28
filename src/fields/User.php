<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User as UserElement;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\validators\ArrayValidator;
use flipbox\organization\elements\db\Organization as OrganizationQuery;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\helpers\Query as QueryHelper;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\User as OrganizationUserRecord;
use flipbox\organization\tasks\LocalizeRelations;
use flipbox\organization\validators\User as UserValidator;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends Field implements PreviewableFieldInterface, EagerLoadingFieldInterface
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'User Organizations');
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return OrganizationElement::class;
    }

    /**
     * Returns the default [[selectionLabel]] value.
     *
     * @return string The default selection label
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Select organization');
    }

    // Properties
    // =========================================================================

    /**
     * @var string|string[]|null The source keys that this field can
     * relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public $sources = '*';

    /**
     * @var string|null The source key that this field can
     * relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @var int|null The site that this field should relate elements from
     */
    public $targetSiteId;

    /**
     * @var string|null The view mode
     */
    public $viewMode;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
     */
    public $limit;

    /**
     * @var string|null The label that should be used on the selection input
     */
    public $selectionLabel;

    /**
     * @var int Whether each site should get its own unique set of relations
     */
    public $localizeRelations = false;

    /**
     * @var bool Whether to allow multiple source selection in the settings
     */
    public $allowMultipleSources = true;

    /**
     * @var bool Whether to allow the Limit setting
     */
    public $allowLimit = true;

    /**
     * @var bool Whether to allow the “Large Thumbnails” view mode
     */
    protected $allowLargeThumbsView = false;

    /**
     * @var string Temlpate to use for settings rendering
     */
    protected $settingsTemplate = '_components/fieldtypes/elementfieldsettings';

    /**
     * @var string Template to use for field rendering
     */
    protected $inputTemplate = '_includes/forms/elementSelect';

    /**
     * @var string|null The JS class that should be initialized for the input
     */
    protected $inputJsClass;

    /**
     * @var bool Whether the elements have a custom sort order
     */
    protected $sortable = true;

    /**
     * @var bool Whether existing relations should be made translatable after the field is saved
     */
    private $_makeExistingRelationsTranslatable = false;

    /**
     * @inheritdoc
     */
    public function init()
    {

        parent::init();

        // Not possible to have no sources selected
        if (!$this->sources) {
            $this->sources = '*';
        }

//        // Restrict limits
//        if(OrganizationPlugin::getInstance()->getSettings()->singleUser) {
//            $this->allowLimit = true;
//            $this->limit = 1;
//        }
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'sources';
        $attributes[] = 'source';
        $attributes[] = 'targetSiteId';
        $attributes[] = 'viewMode';
        $attributes[] = 'limit';
        $attributes[] = 'selectionLabel';
        $attributes[] = 'localizeRelations';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate($this->settingsTemplate, [
            'field' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                ArrayValidator::class,
                'min' => $this->required ? 1 : null,
                'max' => $this->allowLimit && $this->limit ? $this->limit : null,
                'tooFew' => Craft::t(
                    'app',
                    '{attribute} should contain at least {min, number} '.
                    '{min, plural, one{selection} other{selections}}.'
                ),
                'tooMany' => Craft::t(
                    'app',
                    '{attribute} should contain at most {max, number} '.
                    '{max, plural, one{selection} other{selections}}.'
                ),
            ],
            [
                UserValidator::class
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {

        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var Element $element */
        /** @var Element $class */
        $class = static::elementType();
        /** @var OrganizationQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } elseif ($value !== '' && !empty($element->id)) {
            $query->user($element);

            if ($this->sortable) {
                $query->orderBy(['sortOrder' => SORT_ASC]);
            }

            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }
        } else {
            $query->id(false);
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {

        if (null === $value) {
            return null;
        }

        // String = members
        if (is_string($value) || is_numeric($value)) {
            $value = ['member' => $value];
        }

        QueryHelper::applyOrganizationParam(
            $query,
            $value
        );

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(ElementInterface $element = null): bool
    {
        return $this->localizeRelations;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        /** @var ElementQuery $value */
        $titles = [];

        foreach ($value->all() as $relatedElement) {
            $titles[] = (string)$relatedElement;
        }

        return parent::getSearchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        /** @var ElementQuery $value */
        if (count($value)) {
            $html = '<div class="elementselect"><div class="elements">';

            foreach ($value as $relatedElement) {
                $html .= Craft::$app->getView()->renderTemplate(
                    '_elements/element',
                    [
                        'element' => $relatedElement
                    ]
                );
            }

            $html .= '</div></div>';

            return $html;
        }

        return '<p class="light">' . Craft::t('app', 'Nothing selected.') . '</p>';
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value instanceof ElementQueryInterface) {
            $element = $value->first();
        } else {
            $element = $value[0] ?? null;
        }

        if ($element) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $element
            ]);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
    {
        /** @var Element|null $firstElement */
        $firstElement = $sourceElements[0] ?? null;

        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['userId as source', 'organizationId as target'])
            ->from([OrganizationUserRecord::tableName()])
            ->where([
                'and',
                [
                    'userId' => $sourceElementIds,
                ],
                [
                    'or',
                    ['siteId' => $firstElement ? $firstElement->siteId : null],
                    ['siteId' => null]
                ]
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        // Figure out which target site to use
        $targetSite = $this->targetSiteId($firstElement);

        return [
            'elementType' => static::elementType(),
            'map' => $map,
            'criteria' => [
                'siteId' => $targetSite
            ],
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->_makeExistingRelationsTranslatable = false;

        if ($this->id && $this->localizeRelations) {
            /** @var Field $existingField */
            $existingField = Craft::$app->getFields()->getFieldById($this->id);

            if ($existingField && $existingField instanceof User && !$existingField->localizeRelations) {
                $this->_makeExistingRelationsTranslatable = true;
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($this->_makeExistingRelationsTranslatable) {
            Craft::$app->getTasks()->queueTask([
                'type' => LocalizeRelations::class,
                'fieldId' => $this->id,
            ]);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {

        /** @var Element $element */

        if (!$element instanceof UserElement) {
            $element->addError(
                $this->handle,
                Craft::t('organization', 'Field can only be used with the User element.')
            );

            return false;
        }

        // Save relations
        OrganizationPlugin::getInstance()->getField()->beforeSaveUserRelations(
            $this,
            $element,
            $element->getFieldValue($this->handle)
        );

        return parent::beforeElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {

        // Double check
        if (!$element instanceof UserElement) {
            throw new Exception("Invalid element type");
        }

        // Save relations
        OrganizationPlugin::getInstance()->getField()->afterSaveUserRelations(
            $this,
            $element,
            $element->getFieldValue($this->handle)
        );

        parent::afterElementSave($element, $isNew);
    }

    /**
     * Normalizes the available sources into select input options.
     *
     * @return array
     */
    public function getSourceOptions(): array
    {
        $options = [];
        $optionNames = [];

        foreach ($this->availableSources() as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key']
                ];
                $optionNames[] = $source['label'];
            }
        }

        // Sort alphabetically
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }

    /**
     * Returns the HTML for the Target Site setting.
     *
     * @return string|null
     */
    public function getTargetSiteFieldHtml()
    {
        /** @var Element $class */
        $class = static::elementType();

        if (Craft::$app->getIsMultiSite() && $class::isLocalized()) {
            $siteOptions = [
                ['label' => Craft::t('app', 'Same as source'), 'value' => null]
            ];

            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                $siteOptions[] = [
                    'label' => Craft::t('site', $site->name),
                    'value' => $site->id
                ];
            }

            return Craft::$app->getView()->renderTemplateMacro(
                '_includes/forms',
                'selectField',
                [
                    [
                        'label' => Craft::t('app', 'Target Site'),
                        'instructions' => Craft::t(
                            'app',
                            'Which site do you want to select {type} in?',
                                [
                                    'type' => StringHelper::toLowerCase(static::displayName())
                                ]
                        ),
                        'id' => 'targetSiteId',
                        'name' => 'targetSiteId',
                        'options' => $siteOptions,
                        'value' => $this->targetSiteId
                    ]
                ]
            );
        }

        return null;
    }

    /**
     * Returns the HTML for the View Mode setting.
     *
     * @return string|null
     */
    public function getViewModeFieldHtml()
    {
        $supportedViewModes = $this->supportedViewModes();

        if (count($supportedViewModes) === 1) {
            return null;
        }

        $viewModeOptions = [];

        foreach ($supportedViewModes as $key => $label) {
            $viewModeOptions[] = ['label' => $label, 'value' => $key];
        }

        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField', [
            [
                'label' => Craft::t('app', 'View Mode'),
                'instructions' => Craft::t('app', 'Choose how the field should look for authors.'),
                'id' => 'viewMode',
                'name' => 'viewMode',
                'options' => $viewModeOptions,
                'value' => $this->viewMode
            ]
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param ElementQueryInterface|array|null $value
     * @param ElementInterface|null $element
     *
     * @return array
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        if ($value instanceof ElementQueryInterface) {
            $value
                ->status(null)
                ->enabledForSite(false);
        } elseif (!is_array($value)) {
            /** @var Element $class */
            $class = static::elementType();
            $value = $class::find()
                ->id(false);
        }

        $selectionCriteria = $this->inputSelectionCriteria();
        $selectionCriteria['enabledForSite'] = null;
        $selectionCriteria['siteId'] = $this->targetSiteId($element);

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => static::elementType(),
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'elements' => $value,
            'sources' => $this->inputSources(),
            'criteria' => $selectionCriteria,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'limit' => $this->allowLimit ? $this->limit : null,
            'viewMode' => $this->viewMode(),
            'selectionLabel' => $this->selectionLabel ?
                Craft::t('site', $this->selectionLabel) :
                static::defaultSelectionLabel()
        ];
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     *
     * @return array|string
     */
    protected function inputSources()
    {
        if ($this->allowMultipleSources) {
            $sources = $this->sources;
        } else {
            $sources = [$this->source];
        }

        return $sources;
    }

    /**
     * Returns any additional criteria parameters limiting which elements the field should be able to select.
     *
     * @return array
     */
    protected function inputSelectionCriteria(): array
    {
        return [];
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     *
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        /** @var Element|null $element */
        if (Craft::$app->getIsMultiSite()) {
            if ($this->targetSiteId) {
                return $this->targetSiteId;
            }

            if ($element !== null) {
                return $element->siteId;
            }
        }

        return Craft::$app->getSites()->currentSite->id;
    }

    /**
     * Returns the field’s supported view modes.
     *
     * @return array
     */
    protected function supportedViewModes(): array
    {
        $viewModes = [
            'list' => Craft::t('app', 'List'),
        ];

        if ($this->allowLargeThumbsView) {
            $viewModes['large'] = Craft::t('app', 'Large Thumbnails');
        }

        return $viewModes;
    }

    /**
     * Returns the field’s current view mode.
     *
     * @return string
     */
    protected function viewMode(): string
    {
        $supportedViewModes = $this->supportedViewModes();
        $viewMode = $this->viewMode;

        if ($viewMode && isset($supportedViewModes[$viewMode])) {
            return $viewMode;
        }

        return 'list';
    }

    /**
     * Returns the sources that should be available to choose from within the field's settings
     *
     * @return array
     */
    protected function availableSources(): array
    {
        return Craft::$app->getElementIndexes()->getSources(static::elementType(), 'modal');
    }
}
