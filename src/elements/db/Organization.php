<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\elements\db;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\User as UserElement;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\UserGroup;
use craft\records\UserGroup as UserGroupRecord;
use craft\records\UserGroup_User as UserGroupUsersRecord;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\helpers\Query as QueryHelper;
use flipbox\organization\models\Type;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\organization\records\OrganizationType as OrganizationTypeOrganizationRecord;
use flipbox\organization\records\User as OrganizationUserRecord;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @method OrganizationElement|null one($db = null)
 * @method OrganizationElement[] all($db = null)
 */
class Organization extends ElementQuery
{

    /**
     * @var int|int[]|null The user ID(s) that the resulting organizations’ owners must have.
     */
    public $ownerId;

    /**
     * @var int|int[]|null The user group ID(s) that the resulting organizations’ owners must be in.
     */
    public $ownerGroupId;

    /**
     * @var int|int[]|null The organization type ID(s) that the resulting organizations must have.
     */
    public $typeId;

    /**
     * @var int|int[]|null The organization type ID(s) that the resulting organizations must have.
     */
    public $userId;

    /**
     * @var int|int[]|null The organization type ID(s) that the resulting organizations must have.
     */
    public $memberId;

    /**
     * @var mixed The Join Date that the resulting organization must have.
     */
    public $dateJoined;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {

        switch ($name) {
            case 'owner':
            case 'ownerId':
                $this->setOwner($value);
                break;
            case 'ownerGroup':
                $this->ownerGroup($value);
                break;
            case 'type':
            case 'typeId':
                $this->setType($value);
                break;
            case 'user':
            case 'userId':
                $this->setUser($value);
                break;
            case 'member':
            case 'memberId':
                $this->setMember($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }


    /**
     * Sets the [[typeId]] property based on a given element owner.
     *
     * @param string|string[]|integer|integer[]|Type|Type[] $value The property value
     *
     * @return self The query object itself
     */
    public function setType($value)
    {

        $this->typeId = $this->parseTypeValue($value);

        return $this;
    }

    /**
     * Sets the [[typeId]] property.
     *
     * @param string|string[]|integer|integer[]|Type|Type[] $type The property value
     *
     * @return self The query object itself
     */
    public function type($type)
    {

        $this->setType($type);

        return $this;
    }

    /**
     * @param $typeId
     * @return $this
     */
    public function setTypeId($typeId)
    {

        $this->setType($typeId);

        return $this;
    }

    /**
     * Sets the [[typeId]] property.
     *
     * @param integer|integer[] $type The property value
     *
     * @return self The query object itself
     */
    public function typeId($type)
    {

        $this->setType($type);

        return $this;
    }

    /**
     * Sets the [[ownerId]] property based on a given element owner.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $value The property value
     *
     * @return self The query object itself
     */
    public function setOwner($value)
    {

        // parse param to allow for mixed variables
        $this->ownerId = $this->parseUserValue($value);

        return $this;
    }

    /**
     * Sets the [[ownerId]] property.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $owner The property value
     *
     * @return self The query object itself
     */
    public function owner($owner)
    {

        $this->setOwner($owner);

        return $this;
    }

    /**
     * @param $ownerId
     * @return $this
     */
    public function setOwnerId($ownerId)
    {

        $this->setOwner($ownerId);

        return $this;
    }

    /**
     * Sets the [[ownerId]] property.
     *
     * @param integer|integer[] $owner The property value
     *
     * @return self The query object itself
     */
    public function ownerId($owner)
    {

        $this->setOwner($owner);

        return $this;
    }


    /**
     * Sets the [[ownerGroupId]] property based on a given user group(s)’s handle(s).
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function ownerGroup($value)
    {
        if ($value instanceof UserGroup) {
            $this->ownerGroupId = $value->id;
        } elseif ($value !== null) {
            $this->ownerGroupId = (new Query())
                ->select(['id'])
                ->from([UserGroupRecord::tableName()])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->ownerGroupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[authorGroupId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function ownerGroupId($value)
    {
        $this->ownerGroupId = $value;

        return $this;
    }


    /**
     * Sets the [[userId]] property based on a given element owner.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $value The property value
     *
     * @return self The query object itself
     */
    public function setUser($value)
    {

        $this->userId = $this->parseUserValue($value);

        return $this;
    }

    /**
     * Sets the [[userId]] property.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $users The property value
     *
     * @return self The query object itself
     */
    public function user($users)
    {

        $this->setUser($users);

        return $this;
    }

    /**
     * @param $userIds
     * @return $this
     */
    public function setUserId($userIds)
    {

        $this->setUser($userIds);

        return $this;
    }

    /**
     * Sets the [[userId]] property.
     *
     * @param integer|integer[] $user The property value
     *
     * @return self The query object itself
     */
    public function userId($user)
    {

        $this->setUser($user);

        return $this;
    }

    /**
     * Sets the [[userId]] property based on a given element owner.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $value The property value
     *
     * @return self The query object itself
     */
    public function setMember($value)
    {

        $this->memberId = $this->parseUserValue($value);

        return $this;
    }

    /**
     * Sets the [[userId]] property.
     *
     * @param string|string[]|integer|integer[]|UserElement|UserElement[] $members The property value
     *
     * @return self The query object itself
     */
    public function member($members)
    {

        $this->setMember($members);

        return $this;
    }

    /**
     * @param $memberIds
     * @return $this
     */
    public function setMemberId($memberIds)
    {

        $this->setMember($memberIds);

        return $this;
    }

    /**
     * Sets the [[userId]] property.
     *
     * @param integer|integer[] $member The property value
     *
     * @return self The query object itself
     */
    public function memberId($member)
    {

        $this->setMember($member);

        return $this;
    }

    /**
     * Sets the [[dateJoined]] property.
     *
     * @param mixed $value The property value
     *
     * @return static self reference
     */
    public function dateJoined($value)
    {
        $this->dateJoined = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {

        $statuses = OrganizationPlugin::getInstance()->getSettings()->getStatuses();

        if (array_key_exists($status, $statuses)) {
            return [OrganizationRecord::tableAlias() . '.status' => $status];
        }

        return parent::statusCondition($status);
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {

        // invalid handles
        if ($this->ownerGroupId === []) {
            return false;
        }

        $alias = OrganizationRecord::tableAlias();

        $this->joinElementTable($alias);

        $this->query->select([
            $alias . '.status',
            $alias . '.ownerId',
            $alias . '.dateJoined'
        ]);

        if ($this->dateJoined) {
            $this->subQuery->andWhere(Db::parseDateParam($alias . '.dateJoined', $this->dateJoined));
        }

        if ($this->typeId) {
            $this->subQuery->innerJoin(
                $this->organizationDbTableReference(),
                'elements.id = ' . OrganizationTypeOrganizationRecord::tableAlias() . '.organizationId'
            );

            $this->subQuery->andWhere(Db::parseParam(
                OrganizationTypeOrganizationRecord::tableAlias() . '.typeId',
                $this->typeId
            ));
        }

        // Owner only
        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam($alias . '.ownerId', $this->ownerId));
        }

        if ($this->ownerGroupId) {
            $this->subQuery
                ->innerJoin(
                    UserGroupUsersRecord::tableName() . ' usergroups_users',
                    '[[usergroups_users.userId]] = [[' . $alias . '.ownerId]]'
                )
                ->andWhere(Db::parseParam('usergroups_users.groupId', $this->ownerGroupId));
        }

        // Join user table
        if ($this->userId || $this->memberId) {
            $this->subQuery->leftJoin(
                OrganizationUserRecord::tableName() . ' ' . OrganizationUserRecord::tableAlias(),
                'elements.id = ' . OrganizationUserRecord::tableAlias() . '.organizationId'
            );
            $this->subQuery->addSelect([OrganizationUserRecord::tableAlias() . '.sortOrder']);
        }

        // User only
        if ($this->userId) {
            $this->subQuery->andWhere(
                Db::parseParam(OrganizationUserRecord::tableAlias() . '.userId', $this->userId)
            );
        }

        // User or Owner
        if ($this->memberId) {
            $this->subQuery->distinct(true);
            $this->subQuery->andWhere([
                'or',
                Db::parseParam(OrganizationUserRecord::tableAlias() . '.userId', $this->memberId),
                Db::parseParam($alias . '.ownerId', $this->memberId)
            ]);
        }

        return parent::beforePrepare();
    }

    /**
     * @return string
     */
    private function organizationDbTableReference(): string
    {
        return OrganizationTypeOrganizationRecord::tableName() . ' ' . OrganizationTypeOrganizationRecord::tableAlias();
    }

    /**
     * @param $value
     * @return array
     */
    private function parseTypeValue($value)
    {

        // Default join type
        $join = 'or';

        // Parse as single param?
        if (false === QueryHelper::parseBaseParam($value, $join)) {
            // Add one by one
            foreach ($value as $operator => &$v) {
                // attempt to assemble value (return false if it's a handle)
                if (false === QueryHelper::findParamValue($v, $operator)) {
                    // create new query
                    if (is_string($v)) {
                        if ($model = OrganizationPlugin::getInstance()->getType()->find($v)) {
                            $v = $model;
                        }
                    }

                    if ($v instanceof Type) {
                        $v = $v->id;
                    }

                    if ($v) {
                        $v = QueryHelper::assembleParamValue($v, $operator);
                    }
                }
            }
        }

        // parse param to allow for mixed variables
        return array_merge([$join], ArrayHelper::filterEmptyStringsFromArray($value));
    }

    /**
     * @param $value
     * @return array
     */
    private function parseUserValue($value)
    {

        // Default join type
        $join = 'and';

        // Parse as single param?
        if (false === QueryHelper::parseBaseParam($value, $join)) {
            // Add one by one
            foreach ($value as $operator => &$v) {
                // attempt to assemble value (return false if it's a handle)
                if (false === QueryHelper::findParamValue($v, $operator)) {
                    // get element by string
                    if (is_string($v)) {
                        if ($element = Craft::$app->getUsers()->getUserByUsernameOrEmail($v)) {
                            $v = $element->id;
                        }
                    }

                    if ($v instanceof UserElement) {
                        $v = $v->id;
                    }

                    if ($v) {
                        $v = QueryHelper::assembleParamValue($v, $operator);
                    }
                }
            }
        }

        // parse param to allow for mixed variables
        return array_merge([$join], ArrayHelper::filterEmptyStringsFromArray($value));
    }
}
