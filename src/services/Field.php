<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use Craft;
use craft\elements\User as UserElement;
use craft\helpers\ArrayHelper;
use flipbox\organization\elements\db\Organization as OrganizationQuery;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\fields\User as OrganizationUserField;
use flipbox\organization\Organization as OrganizationPlugin;
use flipbox\organization\records\User;
use flipbox\spark\helpers\RecordHelper;
use yii\base\Component;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Field extends Component
{

    /**
     * @param OrganizationUserField $field
     * @param UserElement $source
     * @param OrganizationQuery $target
     * @return bool
     */
    public function beforeSaveUserRelations(OrganizationUserField $field, UserElement $source, OrganizationQuery $target)
    {

        // Check cache for explicitly set (and possibly not saved) organizations
        if (null !== ($targets = $target->getCachedResult())) {

            /** @var OrganizationElement $target */
            foreach ($targets as $target) {

                // New organization?
                if (!$target->id) {

                    if (!Craft::$app->getElements()->saveElement($target)) {

                        $source->addError(
                            $field->handle,
                            Craft::t('organization', 'Unable to save organization.')
                        );

                        return false;

                    }

                }

            }

        }

        return true;

    }

    /**
     * @param OrganizationUserField $field
     * @param UserElement $user
     * @param OrganizationQuery $organizationQuery
     * @throws \Exception
     * @return void
     */
    public function afterSaveUserRelations(OrganizationUserField $field, UserElement $user, OrganizationQuery $organizationQuery)
    {

        /** @var OrganizationElement[]|null $targets */
        if (null === ($targets = $organizationQuery->getCachedResult())) {
            if (null !== $organizationQuery->id) {
                $targets = OrganizationPlugin::getInstance()->getOrganization()->getQuery([
                    'status' => null,
                    'id' => $organizationQuery->id
                ])->all();
            }
        }

        // Nothing to change
        if (null === $targets) {
            return;
        }

        $transaction = RecordHelper::beginTransaction();

        try {

            // Delete the existing relations
            $oldRelationConditions = [
                'and',
                [
                    'userId' => $user->id
                ]
            ];

            if ($field->localizeRelations) {
                $oldRelationConditions[] = [
                    'or',
                    ['siteId' => null],
                    ['siteId' => $user->siteId]
                ];
            }

            $oldTargetIds = User::find()
                ->select(['organizationId'])
                ->where($oldRelationConditions)
                ->column();

            // Find organization ids that we need to remove
            foreach ($targets as $organizationQuery) {
                ArrayHelper::remove($oldTargetIds, $organizationQuery->id);
            }

            /** @var OrganizationElement[] $organizations */
            $organizations = OrganizationPlugin::getInstance()->getOrganization()->getQuery()
                ->id($oldTargetIds)
                ->status(null)
                ->all();

            foreach ($organizations as $organization) {
                OrganizationPlugin::getInstance()->getUser()->dissociate($user, $organization);
            }

            // Add the new ones
            if (!empty($targets)) {

                $siteId = null;

                if ($field->localizeRelations) {
                    $siteId = $user->siteId;
                }

                foreach ($targets as $organizationQuery) {
                    if (!OrganizationPlugin::getInstance()->getUser()->associate($user, $organizationQuery, $siteId, 0)) {
                        throw new Exception('Unable to associate user to organization');
                    }
                }

            }

            $transaction->commit();

        } catch (\Exception $e) {

            $transaction->rollBack();

            throw $e;

        }

    }

}
