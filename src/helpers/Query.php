<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\helpers;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use flipbox\organization\records\Organization as OrganizationRecord;
use flipbox\organization\records\User as OrganizationUsersRecord;
use flipbox\spark\helpers\ArrayHelper;
use flipbox\spark\helpers\QueryHelper;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Query extends QueryHelper
{

    /**
     * @var array
     */
    private static $operators = ['not ', '!=', '<=', '>=', '<', '>', '='];

    /**
     * @param ElementQueryInterface $query
     * @param array $params
     */
    public static function applyOrganizationParam(ElementQueryInterface $query, array $params = [])
    {

        if (array_key_exists('owner', $params)) {
            self::applyOrganizationOwnerParam($query, $params['owner']);
        }

        if (array_key_exists('user', $params)) {
            self::applyOrganizationUserParam($query, $params['user']);
        }

        if (array_key_exists('member', $params)) {
            self::applyOrganizationMemberParam($query, $params['member']);
        }

        return;
    }

    /**
     * @param $value
     * @return array
     */
    public static function parseUserValue($value)
    {

        // Default join type
        $join = 'and';

        // Parse as single param?
        if (false === static::parseBaseParam($value, $join)) {
            // Add one by one
            foreach ($value as $operator => &$v) {
                // attempt to assemble value (return false if it's a handle)
                if (false === static::findParamValue($v, $operator)) {
                    // get element by string
                    if (is_string($v)) {
                        if ($element = Craft::$app->getUsers()->getUserByUsernameOrEmail($v)) {
                            $v = $element->id;
                        }
                    }

                    if ($v instanceof static) {
                        $v = $v->id;
                    }

                    if ($v) {
                        $v = static::assembleParamValue($v, $operator);
                    }
                }
            }
        }

        // parse param to allow for mixed variables
        return array_merge([$join], ArrayHelper::filterEmptyStringsFromArray($value));
    }

    /**
     * Standard param parsing.
     *
     * @param $value
     * @param $join
     * @return bool
     */
    public static function parseBaseParam(&$value, &$join)
    {

        // Force array
        // This is causing some crazy recursive error issue
        // ... adding a simple array cast below.
        /* $value = ArrayHelper::toArray($value); */
        if (!is_array($value)) {
            $value = [$value];
        }

        // Get join type ('and' , 'or')
        $join = static::getJoinType($value, $join);

        // Check for object array (via 'id' key)
        if ($id = static::findIdFromObjectArray($value)) {
            $value = [$id];
        }

        return false;
    }

    /**
     * Format the param value so that we return a string w/ a prepended operator.
     *
     * @param $value
     * @param $operator
     * @return string
     */
    public static function assembleParamValue($value, $operator)
    {

        // Handle arrays as values
        if (is_array($value) || is_object($value)) {
            // Look for an 'id' key in an array
            if ($id = static::findIdFromObjectArray($value, $operator)) {
                // Prepend the operator
                return static::prependOperator($id, $operator);
            }
        }

        return static::prependOperator($value, $operator);
    }

    /**
     * Attempt to resolve a param value by the value.
     * Return false if a 'handle' or other string identifier is detected.
     *
     * @param $value
     * @param $operator
     * @return bool
     */
    public static function findParamValue(&$value, &$operator)
    {

        if (is_array($value) || is_object($value)) {
            $value = static::assembleParamValue($value, $operator);
        } else {
            static::normalizeEmptyValue($value);

            $operator = static::parseParamOperator($value);

            if (is_numeric($value)) {
                $value = static::assembleParamValue($value, $operator);
            } else {
                $value = StringHelper::toLowerCase($value);

                if ($value !== ':empty:' || $value !== 'not :empty:') {
                    // Trim any whitespace from the value
                    $value = StringHelper::trim($value);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Attempt to resolve a param value by the value.
     * Return false if a 'handle' or other string identifier is detected.
     *
     * @param $value
     * @param $operator
     * @return bool
     */
    public static function prepParamValue(&$value, &$operator)
    {

        if (is_array($value)) {
            return true;
        } else {
            static::normalizeEmptyValue($value);
            $operator = static::parseParamOperator($value);

            if (is_numeric($value)) {
                return true;
            } else {
                $value = StringHelper::toLowerCase($value);

                if ($value !== ':empty:' || $value !== 'not :empty:') {
                    // Trim any whitespace from the value
                    $value = StringHelper::trim($value);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $value
     * @param string $default
     * @return mixed|string
     */
    private static function getJoinType(&$value, $default = 'or')
    {

        // Get first value in array
        $joinType = ArrayHelper::firstValue($value);

        // Make sure first value is a string
        $firstVal = is_string($joinType) ? StringHelper::toLowerCase($joinType) : '';

        if ($firstVal == 'and' || $firstVal == 'or') {
            $join = array_shift($value);
        } else {
            $join = $default;
        }

        return $join;
    }

    /**
     * Attempt to get a numeric value from an object array.
     * @param $value
     * @param null $operator
     * @return mixed|string
     */
    private static function findIdFromObjectArray($value, $operator = null)
    {

        if ($id = ArrayHelper::getValue($value, 'id', '')) {
            return static::prependOperator($id, $operator);
        }

        return $id;
    }

    /**
     * Prepend the operator to a value
     *
     * @param $value
     * @param null $operator
     * @return string
     */
    private static function prependOperator($value, $operator = null)
    {

        if ($operator) {
            $operator = StringHelper::toLowerCase($operator);

            if (in_array($operator, static::$operators) || $operator === 'not') {
                if (is_array($value)) {
                    $values = [];

                    foreach ($value as $v) {
                        $values[] = $operator . ($operator === 'not' ? ' ' : '') . $v;
                    }

                    return $values;
                }

                return $operator . ($operator === 'not' ? ' ' : '') . $value;
            }
        }

        return $value;
    }

    /**
     * Normalizes “empty” values.
     *
     * @param string &$value The param value.
     */
    private static function normalizeEmptyValue(&$value)
    {
        if ($value === null) {
            $value = ':empty:';
        } else {
            if (StringHelper::toLowerCase($value) == ':notempty:') {
                $value = 'not :empty:';
            }
        }
    }

    /**
     * Extracts the operator from a DB param and returns it.
     *
     * @param string &$value Te param value.
     *
     * @return string The operator.
     */
    private static function parseParamOperator(&$value)
    {
        foreach (static::$operators as $testOperator) {
            // Does the value start with this operator?
            $operatorLength = strlen($testOperator);

            if (strncmp(
                StringHelper::toLowerCase($value),
                $testOperator,
                $operatorLength
            ) == 0
            ) {
                $value = mb_substr($value, $operatorLength);

                if ($testOperator == 'not ') {
                    return 'not';
                } else {
                    return $testOperator;
                }
            }
        }

        return '';
    }


    /**
     * @param ElementQueryInterface $query
     * @param $owner
     *
     * @return void
     */
    private static function applyOrganizationOwnerParam(ElementQueryInterface $query, $owner)
    {

        /** @var ElementQuery $query */

        $value = self::parseUserValue($owner);

        $alias = OrganizationUsersRecord::tableAlias();

        $query->subQuery->leftJoin(
            OrganizationRecord::tableName() . ' ' . $alias,
            $alias . '.ownerId=users.id'
        );
        $query->subQuery->andWhere(Db::parseParam($alias . '.id', $value));

        return;
    }

    /**
     * @param ElementQueryInterface $query
     * @param $user
     *
     * @return void
     */
    private static function applyOrganizationUserParam(ElementQueryInterface $query, $user)
    {

        /** @var ElementQuery $query */

        $value = self::parseUserValue($user);

        $alias = OrganizationUsersRecord::tableAlias() . StringHelper::randomString(12);

        $query->subQuery->leftJoin(
            OrganizationUsersRecord::tableName() . ' ' . $alias,
            $alias . '.userId=users.id'
        );
        $query->subQuery->andWhere(Db::parseParam($alias . '.organizationId', $value));

        return;
    }

    /**
     * @param ElementQueryInterface $query
     * @param $member
     *
     * @return void
     */
    private static function applyOrganizationMemberParam(ElementQueryInterface $query, $member)
    {

        /** @var ElementQuery $query */

        $value = self::parseUserValue($member);

        $userAlias = OrganizationUsersRecord::tableAlias();
        $alias = OrganizationRecord::tableAlias();

        $query->subQuery->leftJoin(
            OrganizationUsersRecord::tableName() . ' ' . $userAlias,
            $userAlias . '.userId=users.id'
        );

        $query->subQuery->leftJoin(
            OrganizationRecord::tableName() . ' ' . $alias,
            $alias . '.ownerId=users.id'
        );

        // If looking for empty, join on 'and'
        $joinType = in_array(':empty:', $value, true) ? 'and' : 'or';


        $query->subQuery->andWhere([
            $joinType,
            Db::parseParam($userAlias . '.organizationId', $value),
            Db::parseParam($alias . '.id', $value)
        ]);

        return;
    }
}
