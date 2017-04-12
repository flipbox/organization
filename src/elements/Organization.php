<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Edit as EditAction;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as UrlHelper;
use craft\validators\DateTimeValidator;
use DateTime;
use flipbox\organization\elements\actions\ChangeOrganizationStatus as StatusAction;
use flipbox\organization\elements\actions\DeleteOrganization as DeleteAction;
use flipbox\organization\elements\db\Organization as OrganizationQuery;
use flipbox\organization\helpers\User as UserHelper;
use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\Plugin;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\organization\records\User as OrganizationUsersRecord;
use flipbox\organization\validators\Owner;
use flipbox\spark\helpers\QueryHelper;
use yii\base\ErrorException as Exception;

/**
 * @package flipbox\organization\elements
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Organization extends Element
{

    /**
     * @var string
     */
    private $_status;

    /**
     * @var DateTime|null
     */
    public $dateJoined;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var User
     */
    private $_owner;

    /**
     * @var TypeModel[]
     */
    private $_types;

    /**
     * @var TypeModel
     */
    private $_activeType;

    /**
     * @var TypeModel
     */
    private $_primaryType;

    /**
     * @var UserQuery
     */
    private $_users;

    /**
     * @var UserQuery
     */
    private $_members;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('organization', 'Organization');
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'types',
                'activeType',
                'primaryType',
                'users'
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'ownerId'
                    ],
                    'number',
                    'integerOnly' => true

                ],
                [
                    [
                        'ownerId'
                    ],
                    Owner::class
                ],
                [
                    [
                        'dateJoined'
                    ],
                    DateTimeValidator::class
                ]
            ]
        );

    }

    /**
     * Returns the names of any attributes that should be converted to DateTime objects from [[populate()]].
     *
     * @return string[]
     */
    public function datetimeAttributes(): array
    {

        return array_merge(
            parent::datetimeAttributes(),
            [
                'dateJoined'
            ]
        );

    }


    /************************************************************
     * FIELD LAYOUT
     ************************************************************/

    /**
     * @inheritdoc
     */
    public function getFieldLayout(int $siteId = null)
    {

        /** @var TypeModel $type */
        if (!$type = $this->getActiveType()) {
            return OrganizationPlugin::getInstance()->getOrganization()->getDefaultFieldLayout();
        }

        return $type->getFieldLayout($siteId);

    }


    /************************************************************
     * FIND / GET
     ************************************************************/

    /**
     * @inheritdoc
     *
     * @return OrganizationQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new OrganizationQuery(get_called_class());
    }


    /************************************************************
     * STATUS
     ************************************************************/

    /**
     * Returns whether this element type can have statuses.
     *
     * @return boolean
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return array_merge(
            [
                self::STATUS_ENABLED => Craft::t('organization', 'Active')
            ],
            OrganizationPlugin::getInstance()->getSettings()->getStatuses(),
            [
                self::STATUS_DISABLED => Craft::t('organization', 'Disabled')
            ]
        );
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {

        $this->_status = null;

        // A custom organization status
        if (OrganizationPlugin::getInstance()->getOrganization()->isCustomStatus($status)) {
            $this->archived = 0;
            $this->enabled = 1;
            $this->enabledForSite = 1;

            $this->_status = $status;

        } else {

            switch ($status) {

                case Element::STATUS_ENABLED:
                    $this->enabled = 1;
                    $this->enabledForSite = 1;
                    break;

                case Element::STATUS_DISABLED:
                    $this->enabled = 0;
                    break;

                case Element::STATUS_ARCHIVED:
                    $this->archived = 1;
                    break;

            }

        }

        return $this;

    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {

        if (null === $this->_status) {
            return parent::getStatus();
        }

        return $this->_status;

    }

    /************************************************************
     * ACTIVE TYPE
     ************************************************************/

    /**
     * @param TypeModel|null $type
     * @return $this
     */
    public function setActiveType(TypeModel $type = null)
    {

        if ($type) {
            $this->addType($type);
        }

        $this->_activeType = (null === $type) ? false : $type;
        return $this;
    }

    /**
     * @return TypeModel|null
     */
    public function getActiveType()
    {

        if (null === $this->_activeType) {

            // Default to the primary type
            if (!$activeType = $this->getPrimaryType()) {

                // Set false vs null to indicate population has taken place
                $activeType = false;

            }

            $this->_activeType = $activeType;

        }

        return (false === $this->_activeType) ? null : $this->_activeType;

    }


    /************************************************************
     * PRIMARY TYPE
     ************************************************************/

    /**
     * Populate the primary type
     */
    protected function ensurePrimaryType()
    {

        if (null === $this->_primaryType) {

            if (!$primaryType = OrganizationPlugin::getInstance()->getType()->findPrimaryByOrganization($this)) {

                // Set false vs null to indicate population has taken place
                $primaryType = false;

            }

            // Set cache
            $this->_primaryType = $primaryType;

        }

    }

    /**
     * Identify whether a primary type is set
     *
     * @return bool
     */
    public function hasPrimaryType()
    {

        $this->ensurePrimaryType();

        return $this->_primaryType instanceof TypeModel;

    }

    /**
     * Identify whether the type is primary
     *
     * @param $type
     * @return bool
     */
    public function isPrimaryType(TypeModel $type)
    {

        if ($primaryType = $this->getPrimaryType()) {
            return $primaryType->id === $type->id;
        }

        return false;

    }

    /**
     * @param TypeModel $type
     * @return $this
     */
    public function setPrimaryType(TypeModel $type)
    {

        $this->_primaryType = $type;

        // Remove active type cache
        if (false === $this->_activeType) {
            $this->_activeType = null;
        }

        return $this;

    }

    /**
     * Get the primary type
     *
     * @return TypeModel|null
     */
    public function getPrimaryType()
    {

        if (!$this->hasPrimaryType()) {
            return null;
        }

        return $this->_primaryType;

    }

    /************************************************************
     * TYPES
     ************************************************************/

    /**
     * Associate a type to the element
     *
     * @param TypeModel $type
     * @return $this
     */
    public function addType(TypeModel $type)
    {

        $this->ensureTypes();

        // Already set?
        if (!array_key_exists($type->id, $this->_types)) {
            $this->_types[$type->id] = $type;
        }

        return $this;

    }

    /**
     * Set the types associated to the element
     *
     * @param null $types
     * @return $this
     */
    public function setTypes($types = null)
    {

        $this->_types = [];

        // In case a type config is directly passed
        if (!is_array($types) || ArrayHelper::isAssociative($types)) {
            $types = [$types];
        }

        foreach ($types as $key => $type) {

            // Ensure we have a model
            if (!$type instanceof TypeModel) {
                $type = OrganizationPlugin::getInstance()->getType()->get($type);
            }

            $this->addType($type);

        }

        return $this;

    }

    /**
     * Get all associated types associated to the element
     *
     * @return TypeModel[]
     */
    public function getTypes(): array
    {

        $this->ensureTypes();

        return $this->_types;

    }

    /**
     * Ensure all types are associated to the element
     *
     * @return $this
     */
    private function ensureTypes()
    {

        if (null === $this->_types) {

            $this->_types = ArrayHelper::index(
                OrganizationPlugin::getInstance()->getType()->findAllByOrganization($this),
                'id'
            );

        }

        return $this;

    }

    /**
     * Get an associated type by identifier (id/handle)
     *
     * @param $identifier
     * @return null|TypeModel
     */
    public function getType($identifier)
    {

        // Determine index type
        $indexBy = (is_numeric($identifier)) ? 'id' : 'handle';

        // Find all types
        $allTypes = ArrayHelper::index(
            $this->getTypes(),
            $indexBy
        );

        return array_key_exists($identifier, $allTypes) ? $allTypes[$identifier] : null;

    }

    /**
     * Identify whether a type is associated to the element
     *
     * @param TypeModel|null $type
     * @return bool
     */
    public function hasType(TypeModel $type = null): bool
    {

        // Check if any type is set
        if (null === $type) {
            return !empty($this->getTypes());
        }

        return null !== $this->getType($type->id);

    }

    /**
     * @param TypeModel|null $type
     * @return bool
     * @deprecated
     */
    public function getHasType(TypeModel $type = null): bool
    {

        Craft::$app->getDeprecator()->log(
            __METHOD__,
            'Use "hasType()" method'
        );

        return $this->hasType($type);

    }


    /************************************************************
     * MEMBERS
     ************************************************************/
    /**
     * Get an array of users associated to an organization
     *
     * @param array $criteria
     * @return UserQuery
     */
    public function getMembers($criteria = [])
    {

        if (null === $this->_members) {
            $this->_members = OrganizationPlugin::getInstance()->getOrganization()->getMemberQuery($this);
        }

        if (!empty($criteria)) {

            QueryHelper::configure(
                $this->_members,
                $criteria
            );

        }

        return $this->_members;

    }

    /**
     * Associate users to an organization
     *
     * @param $members
     * @return $this
     */
    protected function setMembers($members)
    {

        // Reset the query
        $this->_members = OrganizationPlugin::getInstance()->getOrganization()->getMemberQuery($this);

        // Remove all users
        $this->_members->setCachedResult([]);

        $this->addMembers($members);

        return $this;

    }

    /**
     * Associate an array of users to an organization
     *
     * @param $members
     * @return $this
     */
    protected function addMembers(array $members)
    {

        // In case a type config is directly passed
        if (!is_array($members) || ArrayHelper::isAssociative($members)) {
            $members = [$members];
        }

        foreach ($members as $key => $user) {

            // Ensure we have a model
            if (!$user instanceof User) {
                $user = UserHelper::resolve($user);
            }

            $this->addMember($user);

        }

        return $this;

    }

    /**
     * Associate a user to an organization
     *
     * @param User $user
     * @param bool $addAsUser
     * @return $this
     */
    protected function addMember(User $user, bool $addAsUser = true)
    {

        $currentUsers = $this->getMembers()->all();

        $userElementsByEmail = ArrayHelper::index(
            $currentUsers,
            'email'
        );

        // Does the user already exist?
        if (!array_key_exists($user->email, $userElementsByEmail)) {

            $currentUsers[] = $user;
            $this->getMembers()->setCachedResult($currentUsers);

        }

        // Add as a 'user' as well?
        if ($addAsUser) {
            $this->addUser($user, false);
        }

        return $this;

    }


    /************************************************************
     * USERS
     ************************************************************/

    /**
     * Get an array of users associated to an organization
     *
     * @param array $criteria
     * @return UserQuery
     */
    public function getUsers($criteria = [])
    {

        if (null === $this->_users) {
            $this->_users = OrganizationPlugin::getInstance()->getOrganization()->getUserQuery($this);
        }

        if (!empty($criteria)) {

            QueryHelper::configure(
                $this->_users,
                $criteria
            );

        }

        return $this->_users;

    }

    /**
     * @param User $user
     * @param array $criteria
     * @return bool
     */
    public function isUser(User $user, $criteria = [])
    {

        Craft::$app->getDeprecator()->log(
            __METHOD__,
            'Moved into service.  Organization::isUser()'
        );

        return OrganizationPlugin::getInstance()->getOrganization()->isUser($user, $this, $criteria);

    }

    /**
     * Associate users to an organization
     *
     * @param $users
     * @return $this
     */
    public function setUsers($users)
    {

        // Reset the query
        $this->_users = OrganizationPlugin::getInstance()->getOrganization()->getUserQuery($this);

        // Remove all users
        $this->_users->setCachedResult([]);

        $this->addUsers($users);

        return $this;

    }

    /**
     * Associate an array of users to an organization
     *
     * @param $users
     * @return $this
     */
    public function addUsers(array $users)
    {

        // In case a type config is directly passed
        if (!is_array($users) || ArrayHelper::isAssociative($users)) {
            $users = [$users];
        }

        foreach ($users as $key => $user) {

            // Ensure we have a model
            if (!$user instanceof User) {
                $user = UserHelper::resolve($user);
            }

            $this->addUser($user);

        }

        return $this;

    }

    /**
     * Associate a user to an organization
     *
     * @param User $user
     * @param bool $addAsMember
     * @return $this
     */
    public function addUser(User $user, bool $addAsMember = true)
    {

        $currentUsers = $this->getUsers()->all();

        $userElementsByEmail = ArrayHelper::index(
            $currentUsers,
            'email'
        );

        // Does the user already exist?
        if (!array_key_exists($user->email, $userElementsByEmail)) {

            $currentUsers[] = $user;
            $this->getUsers()->setCachedResult($currentUsers);

        }

        if ($addAsMember) {
            $this->addMember($user, false);
        }

        return $this;

    }

    /**
     * Dissociate a user from an organization
     *
     * @param array $users
     * @return $this
     */
    public function removeUsers(array $users)
    {

        // In case a type config is directly passed
        if (!is_array($users) || ArrayHelper::isAssociative($users)) {
            $users = [$users];
        }

        foreach ($users as $key => $user) {

            // Ensure we have a model
            if (!$user instanceof User) {
                $user = UserHelper::resolve($user);
            }

            $this->removeUser($user);

        }

        return $this;

    }

    /**
     * Dissociate a user from an organization
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user)
    {

        $userElementsByEmail = ArrayHelper::index(
            $this->getUsers()->all(),
            'email'
        );

        // Does the user already exist?
        if (array_key_exists($user->email, $userElementsByEmail)) {

            unset($userElementsByEmail[$user->email]);

            $this->getUsers()->setCachedResult(
                array_values($userElementsByEmail)
            );

        }

        return $this;

    }

    /**
     * Associate an array of types from request input
     *
     * @param string $identifier
     * @return $this
     */
    public function setTypesFromRequest(string $identifier = 'types')
    {

        // Set users array
        $this->setTypes(
            Craft::$app->getRequest()->getBodyParam($identifier, [])
        );

        return $this;

    }

    /**
     * Associate an array of users from request input
     *
     * @param string $identifier
     * @return $this
     */
    public function setUsersFromRequest(string $identifier = 'users')
    {

        if ($users = Craft::$app->getRequest()->getBodyParam($identifier, [])) {

            // Set users array
            $this->setUsers($users);

        }

        return $this;

    }


    /************************************************************
     * ELEMENT ADMIN
     ************************************************************/

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('organization/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {

        switch ($context) {

            case 'user':
                return self::defineUserSources();

            case 'owner':
                return self::defineOwnerSources();

            default:
                return self::defineTypeSources();

        }

    }

    /**
     * @return array
     */
    private static function defineDefaultSources(): array
    {

        return [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All organizations'),
                'criteria' => ['status' => null],
                'hasThumbs' => true
            ]
        ];

    }

    /**
     * @return array
     */
    private static function defineTypeSources(): array
    {

        $sources = static::defineDefaultSources();

        // Array of all organization types
        $organizationTypes = OrganizationPlugin::getInstance()->getType()->findAll();

        $sources[] = ['heading' => Craft::t('organization', 'Types')];

        /** @var TypeModel $organizationType */
        foreach ($organizationTypes as $organizationType) {

            $sources[] = [
                'key' => 'type:' . $organizationType->id,
                'label' => $organizationType->name,
                'criteria' => ['status' => null, 'typeId' => $organizationType->id],
                'hasThumbs' => true
            ];

        }

        return $sources;

    }

    /**
     * @return array
     */
    private static function defineUserSources(): array
    {

        $sources = static::defineDefaultSources();

        // Array of all organization types
        $organizationUsers = OrganizationPlugin::getInstance()->getUser()->getQuery();

        $sources[] = ['heading' => Craft::t('organization', 'Users')];

        /** @var User $organizationUser */
        foreach ($organizationUsers as $organizationUser) {

            $sources[] = [
                'key' => 'user:' . $organizationUser->id,
                'label' => $organizationUser->getFullName(),
                'criteria' => [
                    'status' => null,
                    'users' => [$organizationUser->id]
                ],
                'hasThumbs' => true
            ];

        }

        return $sources;

    }

    /**
     * @return array
     */
    private static function defineOwnerSources(): array
    {

        $sources = static::defineDefaultSources();

        // Array of all organization types
        $organizationOwners = OrganizationPlugin::getInstance()->getUser()->getOwnerQuery();

        $sources[] = ['heading' => Craft::t('organization', 'Users')];

        /** @var User $organizationOwner */
        foreach ($organizationOwners as $organizationOwner) {

            $sources[] = [
                'key' => 'owner:' . $organizationOwner->id,
                'label' => $organizationOwner->getFullName(),
                'criteria' => [
                    'status' => null,
                    'ownerId' => $organizationOwner->id
                ],
                'hasThumbs' => true
            ];

        }

        return $sources;

    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {

        $actions = [];

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => EditAction::class,
            'label' => Craft::t('app', 'Edit organization'),
        ]);

        if (Craft::$app->getUser()->checkPermission('administrateOrganizations')) {
            // Change status
            $actions[] = StatusAction::class;
        }

        if (Craft::$app->getUser()->checkPermission('deleteOrganizations')) {
            // Delete
            $actions[] = DeleteAction::class;
        }

        return $actions;

    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return [
            'id',
            'status'
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {

        return [
            'title' => Craft::t('organization', 'Name'),
            'ownerId' => Craft::t('organization', 'Owner'),
            'userCount' => Craft::t('organization', 'User Count'),
            'dateJoined' => Craft::t('organization', 'Join Date'),
            'status' => Craft::t('organization', 'Status'),
            'type' => Craft::t('organization', 'Type')
        ];

    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {

        switch ($handle) {

            case 'owner':
                return self::eagerLoadingOwnerMap($sourceElements);

            case 'users':
                return self::eagerLoadingUsersMap($sourceElements);

            case 'members':
                return ArrayHelper::merge(
                    self::eagerLoadingUsersMap($sourceElements),
                    self::eagerLoadingOwnerMap($sourceElements)
                );

        }

        return parent::eagerLoadingMap($sourceElements, $handle);

    }

    /**
     * @param array $sourceElements
     * @return array
     */
    private static function eagerLoadingOwnerMap(array $sourceElements)
    {

        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select(['id as source', 'ownerId as target'])
            ->from(OrganizationRecord::tableName())
            ->where(['id' => $sourceElementIds])
            ->all();

        return [
            'elementType' => User::class,
            'map' => $map
        ];

    }

    /**
     * @param array $sourceElements
     * @return array
     */
    private static function eagerLoadingUsersMap(array $sourceElements)
    {

        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select(['organizationId as source', 'userId as target'])
            ->from(OrganizationUsersRecord::tableName())
            ->where(['organizationId' => $sourceElementIds])
            ->all();

        return [
            'elementType' => User::class,
            'map' => $map
        ];

    }


    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {

        switch ($handle) {

            case 'owner':
                $owner = $elements[0] ?? null;
                $this->setOwner($owner);
                break;

            case 'users':
                $users = $elements ?? [];
                $this->setUsers($users);
                break;

            case 'members':
                $users = $elements ?? [];
                $this->setMembers($users);
                break;

            default:
                parent::setEagerLoadedElements($handle, $elements);

        }

    }

    /**
     * @inheritdoc
     */
    public static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('app', 'ID')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'title' => ['label' => Craft::t('organization', 'Name')],
            'owner' => ['label' => Craft::t('organization', 'Owner')],
            'userCount' => ['label' => Craft::t('organization', 'User Count')],
            'status' => ['label' => Craft::t('organization', 'Status')],
            'types' => ['label' => Craft::t('organization', 'Type(s)')],
            'dateJoined' => ['label' => Craft::t('organization', 'Join Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];

    }



    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function tableAttributeHtml(string $attribute): string
    {

        switch ($attribute) {

            case 'status' :
                $value = $this->getStatus();
                $availableStatuses = self::statuses();
                if (array_key_exists($value, $availableStatuses)) {
                    return '<span class="status ' . $value . '"></span> ' . ucfirst($availableStatuses[$value]);
                }
                return $value . $availableStatuses;

            case 'owner' :
                if ($this->hasOwner()) {
                    return '<span class="status ' . $this->getOwner()->getStatus() . '"></span>' . $this->getOwner()->getFullName();
                }

                return '';

            case 'userCount' :
                return count($this->getUsers(['status' => 'not :anything:']));

            case 'types' :

                // Get all configured types
                $types = $this->getTypes();

                foreach ($types as $type) {
                    $typeHtmlParts[] = '<a href="' . UrlHelper::cpUrl('/organization/' . $this->id . '/' . $type->handle) . '">' . $type->name . '</a>';
                }

                return !empty($typeHtmlParts) ? StringHelper::toString($typeHtmlParts, ', ') : '';

        }

        return parent::tableAttributeHtml($attribute);

    }


    /**
     * @inheritdoc
     */
    protected function route()
    {

        // Make sure that the organization is actually live
        if (in_array($this->getStatus(), [Element::STATUS_DISABLED, Element::STATUS_ARCHIVED], true)) {
            return null;
        }

        // todo - match on other organization types or than primary?

        // Use primary type as the element route
        if (!$primaryType = $this->getPrimaryType()) {
            return null;
        }

        $primaryTypeSettings = $primaryType->getSite();

        if (!$primaryTypeSettings->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $primaryTypeSettings->template,
                'variables' => [
                    'organization' => $this,
                ]
            ]
        ];

    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function beforeSave(bool $isNew): bool
    {

        Plugin::getInstance()->getOrganization()->beforeSave($this, $isNew);

        return parent::beforeSave($isNew);

    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function afterSave(bool $isNew)
    {

        // Do parent
        parent::afterSave($isNew);

        Plugin::getInstance()->getOrganization()->afterSave($this, $isNew);

    }

    /**
     * Identify whether element has an owner
     *
     * @return bool
     */
    public function hasOwner()
    {
        // Get owner if it already isn't set
        if (is_null($this->_owner)) {
            $this->getOwner();
        }

        return $this->_owner instanceof User;

    }

    /**
     * @return bool|User|null
     */
    public function getOwner()
    {

        // Check cache
        if (is_null($this->_owner)) {

            // Check property
            if (!empty($this->ownerId)) {

                // Find element
                if ($ownerElement = Craft::$app->getUsers()->getUserById($this->ownerId)) {

                    // Set
                    $this->setOwner($ownerElement);

                } else {

                    // Clear property (it's invalid)
                    $this->ownerId = null;

                    // Prevent subsequent look-ups
                    $this->_owner = false;

                }

            } else {

                // Prevent subsequent look-ups
                $this->_owner = false;

            }

        } else {

            // Cache changed?
            if ($this->ownerId && (($this->_owner === false) || ($this->ownerId !== $this->_owner->getId()))) {

                // Clear cache
                $this->_owner = null;

                // Again
                return $this->getOwner();

            }

        }

        return $this->hasOwner() ? $this->_owner : null;

    }

    /**
     * Associate an owner to the element
     *
     * @param $owner
     * @return $this
     */
    public function setOwner($owner)
    {

        // Clear cache
        $this->_owner = null;

        // Find element
        if (!$owner = $this->findUserElement($owner)) {

            // Clear property / cache
            $this->ownerId = $this->_owner = null;

        } else {

            // Set property
            $this->ownerId = $owner->getId();

            // Set cache
            $this->_owner = $owner;

        }

        return $this;

    }

    /**
     * @param string $user
     * @return bool
     */
    public function getIsOwner($user = 'CURRENT_USER')
    {

        if ('CURRENT_USER' === $user) {

            // Current user
            $element = Craft::$app->getUser()->getIdentity();

        } else {

            // Find element
            $element = $this->findUserElement($user);

        }

        return ($element && $element->getId() == $this->ownerId);

    }

    /**
     * @param string|User $user
     * @return bool
     */
    public function isOwner($user = 'CURRENT_USER')
    {
        return $this->getIsOwner($user);
    }

    /**
     * @param $user
     * @return User|null
     */
    private function findUserElement($user)
    {

        // Element
        if ($user instanceof User) {

            return $user;

            // Id
        } elseif (is_numeric($user)) {

            return Craft::$app->getUsers()->getUserById($user);

            // Username / Email
        } elseif (!is_null($user)) {

            return Craft::$app->getUsers()->getUserByUsernameOrEmail($user);

        }

        return null;

    }

}
