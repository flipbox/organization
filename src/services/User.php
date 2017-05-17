<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use Craft;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\helpers\ArrayHelper;
use flipbox\organization\elements\db\User as UserQuery;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\events\ManageOrganizationUser;
use flipbox\organization\helpers\Query as QueryHelper;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\User as OrganizationUserRecord;
use flipbox\spark\helpers\RecordHelper;
use yii\base\Component;
use yii\base\ErrorException as Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class User extends Component
{

    /**
     * @event ManageOrganizationUserEvent The event that is triggered before a user is associated to an organization.
     *
     * You may set [[ManageOrganizationUserEvent::isValid]] to `false` to prevent the user from being associated to the organization.
     */
    const EVENT_BEFORE_ASSOCIATE = 'beforeAssociate';

    /**
     * @event ManageOrganizationUserEvent The event that is triggered after a user is associated to an organization.
     *
     * * You may set [[ManageOrganizationUserEvent::isValid]] to `false` to prevent the user from being associated to the organization.
     */
    const EVENT_AFTER_ASSOCIATE = 'afterAssociate';

    /**
     * @event ManageOrganizationUserEvent The event that is triggered before a user is remove from an organization.
     *
     * You may set [[ManageOrganizationUserEvent::isValid]] to `false` to prevent the user from being removed from the organization.
     */
    const EVENT_BEFORE_DISSOCIATE = 'beforeDissociate';

    /**
     * @event ManageOrganizationUserEvent The event that is triggered after a user is remove from an organization.
     *
     * * You may set [[ManageOrganizationUserEvent::isValid]] to `false` to prevent the user from being removed from the organization.
     */
    const EVENT_AFTER_DISSOCIATE = 'afterDissociate';


    /**
     * @inheritdoc
     */
    public static function elementClass(): string
    {
        return UserElement::class;
    }

    /*******************************************
     * QUERY
     *******************************************/

    /**
     * Get query
     *
     * @param $criteria
     * @return UserQuery
     */
    public function getQuery($criteria = [])
    {

        /** @var UserQuery $query */
        $query = new UserQuery(UserElement::class);

        // Force array
        if (!is_array($criteria)) {
            $criteria = ArrayHelper::toArray($criteria, [], false);
        }

        // Configure it
        QueryHelper::configure(
            $query,
            $criteria
        );

        return $query;

    }

    /**
     * @param array $ownerCriteria
     * @param array $criteria
     * @return UserQuery
     */
    public function getOwnerQuery($ownerCriteria = [], $criteria = [])
    {

        $query = $this->getQuery($criteria)
            ->organization(['owner' => $ownerCriteria]);

        return $query;

    }

    /**
     * @param array $userCriteria
     * @param array $criteria
     * @return UserQuery
     */
    public function getUserQuery($userCriteria = [], $criteria = [])
    {

        $query = $this->getQuery($criteria)
            ->organization(['user' => $userCriteria]);

        return $query;

    }

    /**
     * @param array $memberCriteria
     * @param array $criteria
     * @return UserQuery
     */
    public function getMemberQuery($memberCriteria = [], $criteria = [])
    {

        $query = $this->getQuery($criteria)
            ->organization(['member' => $memberCriteria]);

        return $query;

    }

    /*******************************************
     * UTILITY
     *******************************************/

    /**
     * @param UserElement $user
     * @param array $criteria
     * @return bool
     */
    public function isUser(UserElement $user, $criteria = [])
    {

        // Gotta have an Id to be a user
        if (!$user->id) {
            return false;
        }

        return $this->getUserQuery($criteria, ['id' => $user->id])
                ->count() > 0;

    }

    /**
     * @param UserElement $user
     * @param array $criteria
     * @return bool
     */
    public function isOwner(UserElement $user, $criteria = [])
    {

        // Gotta have an Id to be an owner
        if (!$user->id) {
            return false;
        }

        return $this->getOwnerQuery($criteria, ['id' => $user->id])
                ->count() > 0;

    }

    /**
     * @param UserElement $user
     * @param array $criteria
     * @return bool
     */
    public function isMember(UserElement $user, $criteria = [])
    {

        // Gotta have an Id to be a member
        if (!$user->id) {
            return false;
        }

        return $this->getMemberQuery($criteria, ['id' => $user->id])
                ->count() > 0;

    }

    /**
     * @param UserElement $userElement
     * @param OrganizationElement $organizationElement
     * @param int|null $siteId
     * @param int|null $sortOrder The order which the user should be positioned.  If the value is zero (0) we position them last.
     */
    protected function applySortOrder(UserElement $userElement, OrganizationElement $organizationElement, int $siteId = null, int $sortOrder = null)
    {

        // No order
        if (null === $sortOrder) {
            return;
        }

        /** @var array $currentOrder */
        $currentOrder = $this->_getCurrentSortOrder($organizationElement, $siteId);

        // The target record to position
        if (!$target = ArrayHelper::remove($currentOrder, $userElement->id)) {
            return;
        }

        $currentSortOrder = ArrayHelper::getValue($target, 'sortOrder');

        // Sort order already correct?
        if ($sortOrder === $currentSortOrder) {
            return;
        }

        /** @var int $items */
        $items = count($currentOrder);

        // Last
        if (0 === $sortOrder || $sortOrder > $items) {

            // Set to last
            Craft::$app->getDb()->createCommand()->update(
                OrganizationUserRecord::tableName(),
                [
                    'sortOrder' => ($items + 1)
                ],
                $target
            )->execute();

            return;

        }

        // First
        if (1 === $sortOrder) {

            $newOrder = [$userElement->id => $target] + $currentOrder;

        } else {

            $offset = $sortOrder - 1;

            // Split at sortOrder / offset
            $preOrder = array_slice($currentOrder, 0, $offset, true);
            $postOrder = array_slice($currentOrder, $offset, null, true);

            // Merge them all back together
            $newOrder = $preOrder + [$userElement->id => $target] + $postOrder;

        }

        $ct = 1;
        foreach ($newOrder as $userId => $condition) {

            // Update
            Craft::$app->getDb()->createCommand()->update(
                OrganizationUserRecord::tableName(),
                [
                    'sortOrder' => $ct++
                ],
                $condition
            )->execute();

        }

    }

    /************************************************************
     * ASSOCIATE
     ************************************************************/

    /**
     * Associate a user to an organization
     *
     * @param UserElement $userElement
     * @param OrganizationElement $organizationElement
     * @param int|null $siteId
     * @param int|null $sortOrder
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function associate(UserElement $userElement, OrganizationElement $organizationElement, int $siteId = null, int $sortOrder = null)
    {

        // Already associated
        if ($this->_associationExists($userElement, $organizationElement, $siteId)) {
            $this->applySortOrder($userElement, $organizationElement, $siteId, $sortOrder);
            return true;
        }

        // The event
        $event = new ManageOrganizationUser([
            'user' => $userElement,
            'organization' => $organizationElement
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_ASSOCIATE,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Restrictions
        if (OrganizationPlugin::getInstance()->getSettings()->hasAssociationRestriction()) {

            if (OrganizationPlugin::getInstance()->getSettings()->memberAssociationRestriction()) {
                $criteria = ['member' => $userElement->id];
            } else {
                $criteria = ['user' => $userElement->id];
            }

            // Ignore the current organization
            $query = OrganizationPlugin::getInstance()->getOrganization()->getQuery(
                array_merge([
                    'id' => 'not ' . $organizationElement->id,
                    'status' => null
                ],
                    $criteria
                )
            );

            if ($query->count()) {
                return false;
            }

        }

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {

            // New record
            $organizationUserRecord = new OrganizationUserRecord();

            // Transfer element attribute(s) to record
            $organizationUserRecord->userId = $userElement->id;
            $organizationUserRecord->organizationId = $organizationElement->id;
            $organizationUserRecord->siteId = $siteId;
            $organizationUserRecord->sortOrder = $sortOrder;

            // Save record
            if (!$organizationUserRecord->save()) {

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

            // Trigger event
            $this->trigger(
                static::EVENT_AFTER_ASSOCIATE,
                $event
            );

            // Green light?
            if (!$event->isValid) {

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

            // Apply the sort order
            $this->applySortOrder($userElement, $organizationElement, $siteId, $sortOrder);

        } catch (Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // Commit db transaction
        $transaction->commit();

        return true;

    }


    /************************************************************
     * DISSOCIATE
     ************************************************************/

    /**
     * Dissociate a user to the organization
     *
     * @param $userElement
     * @param $organizationElement
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function dissociate(UserElement $userElement, OrganizationElement $organizationElement)
    {

        // Already not associated
        if (!$this->_associationExists($userElement, $organizationElement)) {
            return true;
        }

        // The event
        $event = new ManageOrganizationUser([
            'user' => $userElement,
            'organization' => $organizationElement
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_DISSOCIATE,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Db transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            // Delete
            Craft::$app->getDb()->createCommand()->delete(
                OrganizationUserRecord::tableName(),
                [
                    'userId' => $userElement->id,
                    'organizationId' => $organizationElement->id
                ]
            )->execute();

            // Trigger event
            $this->trigger(
                static::EVENT_AFTER_DISSOCIATE,
                $event
            );

            // Green light?
            if (!$event->isValid) {

                // Roll back on failures
                $transaction->rollBack();

                return false;

            }

        } catch (Exception $e) {

            // Roll back on failures
            $transaction->rollBack();

            throw $e;

        }

        // Commit db transaction
        $transaction->commit();

        return true;

    }

    /**
     * @param OrganizationElement $organizationElement
     * @param int|null $siteId
     * @return array
     */
    private function _getCurrentSortOrder(OrganizationElement $organizationElement, int $siteId = null): array
    {

        return (new Query())
            ->select(['id', 'userId', 'sortOrder'])
            ->from([OrganizationUserRecord::tableName()])
            ->andWhere(
                [
                    'organizationId' => $organizationElement->id,
                    'siteId' => $siteId
                ]
            )
            ->indexBy('userId')
            ->orderBy([
                'sortOrder' => SORT_ASC
            ])
            ->limit(null)
            ->all();

    }

    /*******************************************
     * RECORD CHECKING
     *******************************************/

    /**
     * @param UserElement $userElement
     * @param OrganizationElement $organizationElement
     * @param int|null $siteId
     * @return bool
     */
    private function _associationExists(UserElement $userElement, OrganizationElement $organizationElement, int $siteId = null)
    {
        return null !== OrganizationUserRecord::findOne([
                'organizationId' => $organizationElement->id,
                'userId' => $userElement->id,
                'siteId' => $siteId
            ]);
    }

}
