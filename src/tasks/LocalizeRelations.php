<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\tasks;

use Craft;
use craft\base\Task;
use craft\db\Query;
use flipbox\organization\records\User;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class LocalizeRelations extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The field ID whose data should be localized
     */
    public $userId;

    /**
     * @var
     */
    private $relations;

    /**
     * @var
     */
    private $allSiteIds;

    /**
     * @var
     */
    private $workingSiteId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
    {
        $this->relations = (new Query())
            ->select(['id', 'userId', 'siteId', 'organizationId', 'sortOrder'])
            ->from([User::tableName()])
            ->where([
                'userId' => $this->userId,
                'sourceSiteId' => null
            ])
            ->all();

        $this->allSiteIds = Craft::$app->getSites()->getAllSiteIds();

        return count($this->relations);
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        $db = Craft::$app->getDb();
        try {
            $this->workingSiteId = $this->allSiteIds[0];

            // Update the existing one.
            $db->createCommand()
                ->update(
                    User::tableName(),
                    ['siteId' => $this->workingSiteId],
                    ['id' => $this->relations[$step]['id']]
                )
                ->execute();

            $totalSiteIds = count($this->allSiteIds);
            for ($counter = 1; $counter < $totalSiteIds; $counter++) {
                $this->workingSiteId = $this->allSiteIds[$counter];

                $db->createCommand()
                    ->insert(
                        User::tableName(),
                        [
                            'userId' => $this->userId,
                            'siteId' => $this->workingSiteId,
                            'organizationId' => $this->relations[$step]['organizationId'],
                            'sortOrder' => $this->relations[$step]['sortOrder'],
                        ]
                    )
                    ->execute();
            }

            return true;
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);

            return 'An exception was thrown while trying to save organization relations for the user with Id ' .
                $this->relations[$step]['id'] .
                ' into the site  “' .
                $this->workingSiteId .
                '”: ' .
                $e->getMessage();
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Localizing organization relations');
    }
}
