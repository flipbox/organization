<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use Craft;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\events\ManageOrganizationType;
use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\records\OrganizationType as OrganizationTypeRecord;
use flipbox\spark\helpers\RecordHelper;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @method TypeModel|null find($identifier, string $toScenario = null)
 * @method TypeModel get($identifier, string $toScenario = null)
 */
class Type extends AbstractType
{

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered before an organization type is associated to an organization.
     *
     * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the associate action.
     */
    const EVENT_BEFORE_ASSOCIATE = 'beforeAssociate';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered after an organization type is associated to an organization.
     *
     * * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the associate action.
     */
    const EVENT_AFTER_ASSOCIATE = 'afterAssociate';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered before an organization type is dissociated to an organization.
     *
     * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the dissociate action.
     */
    const EVENT_BEFORE_DISSOCIATE = 'beforeDissociate';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered after an organization type is dissociated to an organization.
     *
     * * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the dissociate action.
     */
    const EVENT_AFTER_DISSOCIATE = 'afterDissociate';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered before an organization type is associated to an organization.
     *
     * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the associate action.
     */
    const EVENT_BEFORE_ASSOCIATE_PRIMARY = 'beforeAssociatePrimary';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered after an organization type is associated to an organization.
     *
     * * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the associate action.
     */
    const EVENT_AFTER_ASSOCIATE_PRIMARY = 'afterAssociatePrimary';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered before an organization type is dissociated to an organization.
     *
     * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the dissociate action.
     */
    const EVENT_BEFORE_DISSOCIATE_PRIMARY = 'beforeDissociatePrimary';

    /**
     * @event ManageOrganizationTypeEvent The event that is triggered after an organization type is dissociated to an organization.
     *
     * * You may set [[ManageOrganizationTypeEvent::isValid]] to `false` to prevent the dissociate action.
     */
    const EVENT_AFTER_DISSOCIATE_PRIMARY = 'afterDissociatePrimary';

    /*******************************************
     * ASSOCIATE
     *******************************************/

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @param bool $primary
     * @return bool
     * @throws \Exception
     */
    public function associate(
        TypeModel $typeModel,
        OrganizationElement $organizationElement,
        $primary = false
    ) {
    

        // Set the first association as the primary
        if (!$this->hasPrimaryAssociation($organizationElement)) {
            $primary = true;
        }

        // Already set
        if (($primary && $this->isPrimaryAssociation($typeModel, $organizationElement)) ||
            $this->associationExists($typeModel, $organizationElement)
        ) {
            return true;
        }

        // The event
        $event = new ManageOrganizationType([
            'type' => $typeModel,
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

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        // Existing association?
        if (!$this->associationExists($typeModel, $organizationElement)) {
            try {
                // Ensure record
                $organizationTypeRecord = new OrganizationTypeRecord();

                // Transfer attribute(s) to record
                $organizationTypeRecord->typeId = $typeModel->id;
                $organizationTypeRecord->organizationId = $organizationElement->id;

                // Save record
                if (!$result = $organizationTypeRecord->save()) {
                    // Roll back on failures
                    $transaction->rollBack();

                    return false;
                }

                // Todo - do we need this here -- or just on the primary ??
                Craft::$app->getElements()->updateElementSlugAndUri($organizationElement, false, false);

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
            } catch (\Exception $e) {
                // Roll back on failures
                $transaction->rollBack();

                throw $e;
            }
        }

        // Set as primary
        if ($primary) {
            if (!$this->_associateAsPrimary($typeModel, $organizationElement)) {
                // Roll back on failures
                $transaction->rollBack();

                return false;
            }
        }

        // Commit db transaction
        $transaction->commit();

        return true;
    }


    /*******************************************
     * DISSOCIATE
     *******************************************/

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @return bool
     * @throws \Exception
     */
    public function dissociate(
        TypeModel $typeModel,
        OrganizationElement $organizationElement
    ) {
    

        // The event
        $event = new ManageOrganizationType([
            'type' => $typeModel,
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

        if (!$this->associationExists($typeModel, $organizationElement)) {
            return true;
        }

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {
            if ($isPrimary = $this->isPrimaryAssociation($typeModel, $organizationElement)) {
                // Remove primary association first
                if (!$this->_dissociateAsPrimary($typeModel, $organizationElement)) {
                    // Roll back on failures
                    $transaction->rollBack();

                    return false;
                }
            }

            // Delete record
            Craft::$app->getDb()->createCommand()->delete(
                OrganizationTypeRecord::tableName(),
                [
                    'typeId' => $typeModel->id,
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

            // Did we just delete the primary association (caution ... an event might have created a new one)
            if ($isPrimary && !$this->hasPrimaryAssociation($organizationElement)) {
                // Assign new primary
                if ($primaryType = $this->findByOrganization($organizationElement)) {
                    if (!$this->_associateAsPrimary($primaryType, $organizationElement)) {
                        // Roll back on failures
                        $transaction->rollBack();

                        return false;
                    }
                }
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
     * SET PRIMARY TYPE
     *******************************************/

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @return bool
     * @throws \Exception
     */
    private function _associateAsPrimary(
        TypeModel $typeModel,
        OrganizationElement $organizationElement
    ) {
    

        // Already set as the primary?
        if ($primaryTypeModel = $this->findPrimaryByOrganization($organizationElement)) {
            // Existing primary?
            if ($primaryTypeModel->getId() === $typeModel->getId()) {
                return true;
            }
        }

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {
            // Make sure association is made prior to making it primary
            if (!$this->associationExists(
                $typeModel,
                $organizationElement
            )
            ) {
                if (!$this->associate($typeModel, $organizationElement)) {
                    // Roll back on failures
                    $transaction->rollBack();

                    return false;
                }
            }

            // The event
            $event = new ManageOrganizationType([
                'type' => $typeModel,
                'organization' => $organizationElement
            ]);

            // Trigger event
            $this->trigger(
                static::EVENT_BEFORE_ASSOCIATE_PRIMARY,
                $event
            );

            // Green light?
            if (!$event->isValid) {
                // Roll back on failures
                $transaction->rollBack();

                return false;
            }

            // Remove existing association
            if ($primaryTypeModel && !$this->_dissociateAsPrimary($primaryTypeModel, $organizationElement)) {
                // Roll back on failures
                $transaction->rollBack();

                return false;
            }

            // Update
            Craft::$app->getDb()->createCommand()->update(
                OrganizationTypeRecord::tableName(),
                [
                    'primary' => true
                ],
                [
                    'typeId' => $typeModel->id,
                    'organizationId' => $organizationElement->id
                ]
            )->execute();

            // Trigger event
            $this->trigger(
                static::EVENT_AFTER_ASSOCIATE_PRIMARY,
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
     * UNSET PRIMARY TYPE
     *******************************************/

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @return bool
     * @throws \Exception
     */
    private function _dissociateAsPrimary(
        TypeModel $typeModel,
        OrganizationElement $organizationElement
    ) {
    

        // Already not the primary?
        if ($primaryTypeModel = $this->findPrimaryByOrganization($organizationElement)) {
            if ($primaryTypeModel->getId() !== $typeModel->getId()) {
                return true;
            }
        }

        // The event
        $event = new ManageOrganizationType([
            'type' => $typeModel,
            'organization' => $organizationElement
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_DISSOCIATE_PRIMARY,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Db transaction
        $transaction = RecordHelper::beginTransaction();

        try {
            // Update
            Craft::$app->getDb()->createCommand()->update(
                OrganizationTypeRecord::tableName(),
                [
                    'primary' => false
                ],
                [
                    'typeId' => $typeModel->id,
                    'organizationId' => $organizationElement->id
                ]
            )->execute();

            // Trigger event
            $this->trigger(
                static::EVENT_BEFORE_DISSOCIATE_PRIMARY,
                $event
            );

            // Green light?
            if (!$event->isValid) {
                // Roll back on failures
                $transaction->rollBack();

                return false;
            }

            // Assign new primary
            if (!$this->hasPrimaryAssociation($organizationElement) &&
                $primaryType = $this->findByOrganization($organizationElement)
            ) {
                if (!$this->_associateAsPrimary($primaryType, $organizationElement)) {
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

        // Commit db transaction
        $transaction->commit();

        return true;
    }


    /*******************************************
     * UTILITIES
     *******************************************/

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @return bool
     */
    private function associationExists(TypeModel $typeModel, OrganizationElement $organizationElement)
    {
        return null !== OrganizationTypeRecord::findOne([
                'organizationId' => $organizationElement->id,
                'typeId' => $typeModel->id
            ]);
    }

    /**
     * @param TypeModel $typeModel
     * @param OrganizationElement $organizationElement
     * @return bool
     */
    private function isPrimaryAssociation(TypeModel $typeModel, OrganizationElement $organizationElement)
    {
        return null !== OrganizationTypeRecord::findOne([
                'organizationId' => $organizationElement->id,
                'typeId' => $typeModel->id,
                'primary' => true
            ]);
    }

    /**
     * @param OrganizationElement $organizationElement
     * @return bool
     */
    private function hasPrimaryAssociation(OrganizationElement $organizationElement)
    {
        return null !== OrganizationTypeRecord::findOne([
                'organizationId' => $organizationElement->id,
                'primary' => true
            ]);
    }
}
