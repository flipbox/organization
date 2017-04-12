<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User as UserElement;
use craft\helpers\DateTimeHelper;
use craft\models\FieldLayout as FieldLayoutModel;
use craft\records\Element as ElementRecord;
use flipbox\organization\elements\db\Organization as OrganizationQuery;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\events\ChangeOwner as ChangeOwnerEvent;
use flipbox\organization\events\ChangeStatus as ChangeStatusEvent;
use flipbox\organization\Plugin as OrganizationPlugin;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\spark\helpers\ArrayHelper;
use flipbox\spark\helpers\RecordHelper;
use flipbox\spark\helpers\SiteHelper;
use flipbox\spark\services\Element as ElementService;
use flipbox\spark\services\traits\ElementRecordBehavior;
use yii\base\Exception;

/**
 * @package flipbox\organization\services
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Organization extends ElementService
{

    use ElementRecordBehavior;

    /**
     * @inheritdoc
     */
    public static function elementClass(): string
    {
        return OrganizationElement::class;
    }

    /**
     * @inheritdoc
     */
    public static function recordClass(): string
    {
        return OrganizationRecord::class;
    }

    /**
     * @event ChangeStatusEvent The event that is triggered before a organization has a custom status change.
     *
     * You may set [[ChangeStatusEvent::isValid]] to `false` to prevent the organization changing the status.
     */
    const EVENT_BEFORE_STATUS_CHANGE = 'beforeStatusChange';

    /**
     * @event ChangeStatusEvent The event that is triggered after a organization has a custom status change.
     *
     * * You may set [[ChangeStatusEvent::isValid]] to `false` to prevent the organization changing the status.
     */
    const EVENT_AFTER_STATUS_CHANGE = 'afterStatusChange';

    /**
     * @event ChangeOwnerEvent The event that is triggered before an organization ownership is transferred.
     *
     * You may set [[ChangeOwnerEvent::isValid]] to `false` to prevent the organization ownership from getting transferred.
     */
    const EVENT_BEFORE_TRANSFER_OWNERSHIP = 'beforeOwnerChange';

    /**
     * @event ChangeOwnerEvent The event that is triggered after an organization ownership is transferred.
     *
     * * You may set [[ChangeOwnerEvent::isValid]] to `false` to prevent the organization ownership from getting transferred.
     */
    const EVENT_AFTER_TRANSFER_OWNERSHIP = 'afterOwnerChange';

    /**
     * @var array
     */
    private $_statusesByOrganization = [];


    /*******************************************
     * STATUS
     *******************************************/

    /**
     * @param $status
     * @return bool
     */
    public function isCustomStatus($status)
    {

        if (!is_string($status) || empty($status)) {
            return false;
        }

        return array_key_exists($status, OrganizationPlugin::getInstance()->getSettings()->getStatuses());

    }


    /*******************************************
     * SAVE
     *******************************************/

    /**
     * @param OrganizationElement $organization
     * @param bool $isNew
     * @throws Exception
     * @throws \Exception
     */
    public function beforeSave(OrganizationElement $organization, bool $isNew)
    {

        // Join Date
        if (empty($organization->dateJoined)) {
            $organization->dateJoined = DateTimeHelper::currentUTCDateTime();
        }

        if (!$isNew) {

            /** @var OrganizationRecord $recordClass */
            $recordClass = static::recordClass();

            $query = (new Query())
                ->select(['enabled', 'archived', 'status'])
                ->from([$recordClass::tableName() . ' ' . $recordClass::tableAlias()])
                ->innerJoin(
                    ElementRecord::tableName() . ' elements',
                    'elements.id = ' . $recordClass::tableAlias() . '.id'
                )
                ->andWhere(
                    [
                        'elements.id' => $organization->getId()
                    ]
                )->one();

            $currentStatus = $organization->getStatus();
            $existingStatus = $query['status'];

            // Quick logic to determine the status
            if (empty($existingStatus)) {

                $existingStatus = (!empty($query['archived']) ?
                    OrganizationElement::STATUS_ARCHIVED :
                    ($query['enabled'] ?
                        OrganizationElement::STATUS_ENABLED :
                        OrganizationElement::STATUS_DISABLED
                    )
                );

            }

            // If they don't match, store it and set the original.
            //  We'll handle changing the status on the after event.
            if ($currentStatus !== $existingStatus) {
                $this->_statusesByOrganization[$organization->getId()] = $currentStatus;
                $organization->setStatus($existingStatus);
            }

        }

    }

    /**
     * @param OrganizationElement $organization
     * @param bool $isNew
     * @throws Exception
     * @throws \Exception
     */
    public function afterSave(OrganizationElement $organization, bool $isNew)
    {

        // Get the category record
        if (!$isNew) {
            $record = OrganizationRecord::findOne($organization->id);

            if (!$record) {
                throw new Exception('Invalid organization Id: ' . $organization->id);
            }
        } else {
            $record = new OrganizationRecord();
            $record->id = $organization->id;
        }

        $record->dateJoined = $organization->dateJoined;

        if ($isNew) {

            // Transfer element attribute(s) to record
            $record->status = $organization->getStatus();

            if (!$this->isCustomStatus(
                $record->status
            )
            ) {
                $record->status = null;
            }

            $record->ownerId = $organization->ownerId;

        }

        // Save the record
        if (!$record->save()) {

            $organization->addErrors($record->getErrors());

            throw new Exception('Unable to save record');

        }

        // Transfer id to the new records
        if ($isNew) {
            $organization->id = $record->id;
            $organization->dateCreated = $record->dateCreated;
        }

        $organization->dateUpdated = $record->dateUpdated;

        if (!$isNew) {

            // Change status
            $status = $organization->getStatus();

            $toStatus = ArrayHelper::remove(
                $this->_statusesByOrganization,
                $organization->getId(),
                $status
            );

            if ($status !== $toStatus) {

                // Change status
                if (!$this->changeStatus($organization, $toStatus)) {

                    // Add error
                    $organization->addError('status',
                        Craft::t('organization', 'Unable to change status.'));

                    throw new Exception("Unable to change status.");

                }

            }

            // The owner we're changing to
            $toOwner = $organization->ownerId;
            if ($record->ownerId !== $toOwner) {

                // Revert element to old owner
                $organization->ownerId = $record->ownerId;

                // Change owner
                if (!$this->transferOwner($organization, $toOwner)) {

                    // Add error
                    $organization->addError('ownerId',
                        Craft::t('organization', 'Unable to change owner.'));

                    throw new Exception("Unable to change owner.");

                }

            }

        }

        // Save organization types
        if (!$this->associateTypes($organization)) {

            // Add error
            $organization->addError('types',
                Craft::t('organization', 'Unable to save types.'));

            throw new Exception("Unable to save types.");

        }

        // Save organization users
        if (!$this->associateUsers($organization)) {

            // Add error
            $organization->addError('users',
                Craft::t('organization', 'Unable to save users.'));

            throw new Exception("Unable to save users.");

        }

    }


    /*******************************************
     * USER QUERY
     *******************************************/

    /**
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return UserQuery
     */
    public function getMemberQuery(OrganizationElement $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getUser()->getMemberQuery(($match ? '' : 'not ') . $organization->id ?: 'x', $criteria);
    }

    /**
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return UserQuery
     */
    public function getUserQuery(OrganizationElement $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getUser()->getUserQuery(($match ? '' : 'not ') . $organization->id ?: 'x', $criteria);
    }

    /**
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return UserQuery
     */
    public function getOwnerQuery(OrganizationElement $organization, $criteria = [], bool $match = true)
    {
        return OrganizationPlugin::getInstance()->getUser()->getOwnerQuery(($match ? '' : 'not ') . $organization->id ?: 'x', $criteria);
    }


    /*******************************************
     * UTILITY
     *******************************************/

    /**
     * @param UserElement $user
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return bool
     */
    public function isUser(UserElement $user, OrganizationElement $organization, $criteria = [], bool $match = true)
    {

        // User query
        $emails = $this->getUserQuery($organization, $criteria, $match)
            ->select(['users.email'])
            ->column();

        return in_array($user->email, $emails);

    }

    /**
     * @param UserElement $user
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return bool
     */
    public function isOwner(UserElement $user, OrganizationElement $organization, $criteria = [], bool $match = true)
    {

        // User query
        $emails = $this->getOwnerQuery($organization, $criteria, $match)
            ->select(['users.email'])
            ->column();

        return in_array($user->email, $emails);

    }

    /**
     * @param UserElement $user
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param bool $match
     * @return bool
     */
    public function isMember(UserElement $user, OrganizationElement $organization, $criteria = [], bool $match = true)
    {

        // User query
        $emails = $this->getMemberQuery($organization, $criteria, $match)
            ->select(['users.email'])
            ->column();

        return in_array($user->email, $emails);

    }


    /*******************************************
     * QUERY
     *******************************************/

    /**
     * @inheritdoc
     * @return ElementQueryInterface|OrganizationQuery
     */
    public function getQuery($criteria = [])
    {
        return parent::getQuery($criteria);
    }


    /*******************************************
     * POPULATE (from Request)
     *******************************************/

    /**
     * @param OrganizationElement $organization
     */
    public function populateFromRequest(OrganizationElement $organization)
    {

        $request = Craft::$app->getRequest();

        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $organization->slug = $request->getBodyParam('slug', $organization->slug);
        $organization->dateJoined = (($dateJoined = $request->getBodyParam('dateJoined')) !== false ? (DateTimeHelper::toDateTime($dateJoined) ?: null) : $organization->dateJoined);
        $organization->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $organization->enabledForSite);
        $organization->title = $request->getBodyParam('title', $organization->title);

        // Status
        $organization->setStatus(
            $request->getBodyParam('status', $organization->getStatus())
        );

        // Active Type
        $type = null;
        if ($typeId = $request->getBodyParam('type', null)) {
            $type = OrganizationPlugin::getInstance()->getType()->get($typeId);
        }
        $organization->setActiveType($type);

        // Owner
        $ownerId = $request->getBodyParam('owner', ($organization->ownerId ?: Craft::$app->getUser()->getIdentity()->id));
        if (is_array($ownerId)) {
            $ownerId = $ownerId[0] ?? null;
        }
        $organization->ownerId = $ownerId;

        // Set types
        $organization->setTypesFromRequest(
            $request->getParam('typesLocation', 'types')
        );

        // Set users
        $organization->setUsersFromRequest(
            $request->getParam('usersLocation', 'users')
        );

        // Set content
        $organization->setFieldValuesFromRequest(
            $request->getParam('fieldsLocation', 'fields')
        );

    }


    /*******************************************
     * TYPES - ASSOCIATE and/or DISASSOCIATE
     *******************************************/

    /**
     * @param OrganizationElement $organizationElement
     * @return bool
     * @throws \Exception
     */
    protected function associateTypes(OrganizationElement $organizationElement)
    {

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {

            // Primary type (previously saved?)
            $primaryType = $organizationElement->getPrimaryType();

            // All types
            $currentTypes = $organizationElement->getTypes();

            // Existing types
            $existingTypes = ArrayHelper::index(
                OrganizationPlugin::getInstance()->getType()->findAllByOrganization($organizationElement),
                'id'
            );

            // Verify primary type is still valid
            if ($primaryType) {
                if (!array_key_exists($primaryType->id, $currentTypes)) {
                    $primaryType = null;
                }
            }

            $count = 0;
            foreach ($currentTypes as $currentType) {

                if (null !== ArrayHelper::remove($existingTypes, $currentType->id)) {
                    continue;
                }

                // If primary isn't already set, and it's the first one
                $isPrimary = (0 === $count++ && empty($primaryType));

                // Associate
                if (!OrganizationPlugin::getInstance()->getType()->associate(
                    $currentType,
                    $organizationElement,
                    $isPrimary
                )
                ) {

                    // Roll back on failures
                    $transaction->rollBack();

                    return false;

                }

            }

            foreach ($existingTypes as $currentType) {

                // Dissociate
                if (!OrganizationPlugin::getInstance()->getType()->dissociate(
                    $currentType,
                    $organizationElement)
                ) {

                    // Roll back on failures
                    $transaction->rollBack();

                    return false;

                }

            }

        } catch (\Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // commit db actions (success)
        $transaction->commit();

        return true;

    }

    /*******************************************
     * USERS - ASSOCIATE and/or DISASSOCIATE
     *******************************************/

    /**
     * @param OrganizationElement $organizationElement
     * @param int|null $siteId
     * @return bool
     * @throws \Exception
     */
    protected function associateUsers(OrganizationElement $organizationElement, int $siteId = null)
    {

        /** @var UserQuery $query */
        $query = $organizationElement->getUsers();

        // Only perform save if we have cached result (meaning it was may have changed)
        if ($query->getCachedResult() === null) {
            return true;
        }

        // Db transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            // Find all currently associated and index by userId
            $existingUsers = $this->getUserQuery(
                $organizationElement,
                [
                    'status' => null,
                    'indexBy' => 'id'
                ]
            )->all();

            // Get array of associated users (index by email -> so we don't have dupes)
            $currentUsers = ArrayHelper::index(
                $query->getCachedResult(),
                'email'
            );

            // Exclude owner
            if ($organizationElement->hasOwner()) {
                ArrayHelper::remove($currentUsers, $organizationElement->getOwner()->email);
            }

            /**
             * @var string $key
             * @var UserElement $currentUser
             */
            foreach ($currentUsers as $key => $currentUser) {

                if (!$currentUser->getId() &&
                    !Craft::$app->getElements()->saveElement($currentUser)
                ) {

                    // Roll back on failures
                    $transaction->rollBack();

                    return false;

                }

                // Only associate if they are new
                if (null !== ArrayHelper::remove($existingUsers, $currentUser->getId())) {
                    continue;
                }

                // Otherwise, associate
                if (!OrganizationPlugin::getInstance()->getUser()->associate(
                    $currentUser,
                    $organizationElement,
                    $siteId
                )
                ) {

                    // Roll back on failures
                    $transaction->rollBack();

                    return false;

                }

            }

            foreach ($existingUsers as $key => $existingUser) {

                // Dissociate
                if (!OrganizationPlugin::getInstance()->getUser()->dissociate(
                    $existingUser,
                    $organizationElement
                )
                ) {

                    // Roll back on failures
                    $transaction->rollBack();

                    return false;

                }

            }

        } catch (\Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // commit db actions (success)
        $transaction->commit();

        return true;

    }


    /*******************************************
     * STATUS
     *******************************************/

    /**
     * @param OrganizationElement $element
     * @param $status
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function changeStatus(
        OrganizationElement $element,
        $status
    )
    {

        // The before event
        $event = new ChangeStatusEvent([
            'organization' => $element,
            'fromStatus' => $element->getStatus(),
            'toStatus' => $status
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_STATUS_CHANGE,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {

            /** @var OrganizationRecord $record */
            $record = $this->getRecordByCondition([
                'id' => $element->id
            ]);

            // Set status
            $record->status = $this->isCustomStatus($status) ? $status : null;

            // Validate record (status only)
            if (!$record->validate('status')) {

                // Transfer errors
                $element->addErrors($record->getErrors());

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

            // Organization status
            Craft::$app->getDb()->createCommand()->update(
                $record::tableName(),
                ['status' => $record->status],
                ['id' => $element->id]
            )->execute();

            // Element status
            switch ($status) {

                case OrganizationElement::STATUS_ARCHIVED:
                    $condition = [
                        'enabled' => 0,
                        'archived' => 1,
                    ];
                    break;

                case OrganizationElement::STATUS_DISABLED:
                    $condition = [
                        'enabled' => 0,
                        'archived' => 0,
                    ];
                    break;

                default:
                    $condition = [
                        'enabled' => 1,
                        'archived' => 0,
                    ];
                    break;

            }

            Craft::$app->getDb()->createCommand()->update(
                ElementRecord::tableName(),
                $condition,
                ['id' => $element->id]
            )->execute();

            // Transfer record attribute(s) to element
            $element->setStatus($status);

            // Trigger event
            $this->trigger(
                static::EVENT_AFTER_STATUS_CHANGE,
                $event
            );

            // Green light?
            if (!$event->isValid) {

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

        } catch (\Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // Commit db transaction
        $transaction->commit();

        return true;

    }

    /*******************************************
     * OWNER
     *******************************************/

    /**
     * @param OrganizationElement $element
     * @param $newOwnerId
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function transferOwner(
        OrganizationElement $element,
        $newOwnerId
    )
    {

        // The event
        $event = new ChangeOwnerEvent([
            'organization' => $element,
            'fromOwner' => $element->getOwner(),
            'toOwner' => $newOwnerId
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_TRANSFER_OWNERSHIP,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Db transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            // Get record (or throw an Exception)
            /** @var OrganizationRecord $record */
            $record = $this->getRecordByCondition([
                'id' => $element->id
            ]);

            // Set owner
            $record->ownerId = $newOwnerId;

            // Validate record (status only)
            if (!$record->save(true, ['ownerId'])) {

                // Transfer errors
                $element->addErrors($record->getErrors());

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

            // Transfer record attribute(s) to element
            $element->ownerId = $record->ownerId;

            // Trigger event
            $this->trigger(
                static::EVENT_AFTER_TRANSFER_OWNERSHIP,
                $event
            );

            // Green light?
            if (!$event->isValid) {

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

        } catch (\Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // Commit db transaction
        $transaction->commit();

        return true;

    }

    /*******************************************
     * UTILITIES
     *******************************************/

    /**
     * @param int|null $siteId
     * @return FieldLayoutModel
     */
    public function getDefaultFieldLayout(int $siteId = null)
    {
        return OrganizationPlugin::getInstance()->getSettings()->getSite(
            SiteHelper::resolveSiteId($siteId)
        )->getFieldLayout();
    }

}
