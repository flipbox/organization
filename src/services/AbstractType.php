<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\services;

use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\models\Type as TypeModel;
use flipbox\organization\models\TypeSettings as TypeSettingsModel;
use flipbox\organization\records\OrganizationType as OrganizationTypeRecord;
use flipbox\organization\records\Type as TypeRecord;
use flipbox\organization\records\TypeSettings as TypeSettingsRecord;
use flipbox\spark\helpers\ModelHelper;
use flipbox\spark\helpers\QueryHelper;
use flipbox\spark\services\ModelByIdOrHandle;
use yii\db\ActiveQuery;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class AbstractType extends ModelByIdOrHandle
{

    /**
     * @inheritdoc
     */
    public static function modelClass(): string
    {
        return TypeModel::class;
    }

    /**
     * @inheritdoc
     */
    public static function recordClass(): string
    {
        return TypeRecord::class;
    }

    /**
     * @var [TypeSettingsModel[]]
     */
    protected $_cacheSettingsById = [];


    /*******************************************
     * MODELS
     *******************************************/

    /**
     * @param OrganizationElement $organization
     * @param array $criteria
     * @param string $scenario
     * @return TypeModel|null
     */
    public function findPrimaryByOrganization(OrganizationElement $organization, $criteria = [], $scenario = ModelHelper::DEFAULT_SCENARIO)
    {

        $query = $this->getByOrganizationQuery($organization, $criteria)
            ->andWhere([
                'primary' => true
            ]);

        $model = null;

        /** @var OrganizationTypeRecord $record */
        if ($record = $query->one()) {

            /** @var TypeModel $model */
            if ($model = $this->findByRecord($record->type)) {
                if ($scenario) {
                    $model->setScenario($scenario);
                }
            }
        }

        return $model;
    }

    /**
     * @param $organization
     * @param array $criteria
     * @param string $scenario
     * @return TypeModel[]
     */
    public function findAllByOrganization(OrganizationElement $organization, $criteria = [], $scenario = ModelHelper::DEFAULT_SCENARIO)
    {

        /** @var ActiveQuery $query */
        $query = $this->getByOrganizationQuery($organization, $criteria);

        /** @var OrganizationTypeRecord[] $records */
        if (!$records = $query->all()) {
            return [];
        }

        $models = [];

        /** @var OrganizationTypeRecord $record */
        foreach ($records as $record) {

            /** @var TypeModel $model */
            if ($model = $this->findByRecord($record->type)) {
                if ($scenario) {
                    $model->setScenario($scenario);
                }

                $models[] = $model;
            }
        }

        return $models;
    }

    /**
     * @param $organization
     * @param array $criteria
     * @param string $scenario
     * @return TypeModel
     */
    public function findByOrganization(OrganizationElement $organization, $criteria = [], $scenario = ModelHelper::DEFAULT_SCENARIO)
    {

        /** @var ActiveQuery $query */
        $query = $this->getByOrganizationQuery($organization, $criteria);

        /** @var OrganizationTypeRecord $record */
        if (!$record = $query->one()) {
            return null;
        }

        /** @var TypeModel $model */
        if ($model = $this->findByRecord($record->type)) {
            if ($scenario) {
                $model->setScenario($scenario);
            }
        }

        return $model;
    }

    /**
     * @param OrganizationElement $organization
     * @param array $criteria
     * @return ActiveQuery
     */
    protected function getByOrganizationQuery(OrganizationElement $organization, $criteria = [])
    {

        /** @var ActiveQuery $query */
        $query = OrganizationTypeRecord::find();

        if ($criteria) {
            QueryHelper::configure(
                $query,
                $criteria
            );
        }

        $query->with('type')
            ->andWhere([
                'organizationId' => $organization->getId()
            ]);

        return $query;
    }

    /*******************************************
     * SETTINGS
     *******************************************/

    /**
     * @param TypeModel $type
     * @return TypeSettingsModel[]
     */
    public function findAllSettings(TypeModel $type): array
    {

        if (!$type->id) {
            return [];
        }

        if (!array_key_exists($type->id, $this->_cacheSettingsById)) {
            $this->_cacheSettingsById[$type->id] = [];

            /** @var TypeSettingsRecord[] $settings */
            $settings = TypeSettingsRecord::find()
                ->where(['typeId' => $type->id])
                ->all();

            foreach ($settings as $setting) {
                $this->_cacheSettingsById[$type->id][$setting->id] = new TypeSettingsModel($setting->toArray([
                    'id',
                    'typeId',
                    'siteId',
                    'hasUrls',
                    'uriFormat',
                    'template',
                    'fieldLayoutId',
                    'dateCreated',
                    'dateUpdated',
                ]));
            }
        }

        return $this->_cacheSettingsById[$type->id];
    }
}
