<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\queue\jobs;

use Craft;
use craft\db\Query;
use craft\queue\BaseJob;
use flipbox\organization\records\User;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class LocalizeRelations extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The field ID whose data should be localized
     */
    public $userId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {

        $relations = (new Query())
            ->select(['id', 'userId', 'siteId', 'organizationId', 'sortOrder'])
            ->from([User::tableName()])
            ->where([
                'userId' => $this->userId,
                'sourceSiteId' => null
            ])
            ->all();

        $totalRelations = count($relations);
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();
        $primarySiteId = array_shift($allSiteIds);
        $db = Craft::$app->getDb();

        foreach ($relations as $i => $relation) {
            $this->setProgress($queue, $i / $totalRelations);

            // Set the existing relation to the primary site
            $db->createCommand()
                ->update(
                    User::tableName(),
                    ['siteId' => $primarySiteId],
                    ['id' => $relation['id']]
                )
                ->execute();

            // Duplicate it for the other sites
            foreach ($allSiteIds as $siteId) {
                $db->createCommand()
                    ->insert(
                        User::tableName(),
                        [
                            'userId' => $this->userId,
                            'siteId' => $siteId,
                            'organizationId' => $relation['organizationId'],
                            'sortOrder' => $relation['sortOrder'],
                        ]
                    )
                    ->execute();
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('organization', 'Localizing organization relations');
    }
}
