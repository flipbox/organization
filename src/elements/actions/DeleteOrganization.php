<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use flipbox\organization\elements\Organization;
use flipbox\organization\Organization as OrganizationPlugin;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class DeleteOrganization extends ElementAction
{
    /**
     * @var int|null The organization ID that the deleted organization’s users should be transferred to
     */
    public $transferUsersTo;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Delete');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $elementType = Json::encode(Organization::class);
        $redirect = Json::encode(Craft::$app->getSecurity()->hashData('organization'));

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        activate: function(\$selectedItems)
        {
            var modal = new Craft.DeleteOrganizationModal(Craft.elementIndex.getSelectedElementIds(), {
                onSubmit: function()
                {
                    Craft.elementIndex.submitAction({$type}, Garnish.getPostData(modal.\$container));
                    modal.hide();

                    return false;
                },
                redirect: {$redirect},
                elementType: {$elementType}
            });
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }

    /**
     * Performs the action on any elements that match the given criteria.
     *
     * @param ElementQueryInterface $query The element query defining which elements the action should affect.
     *
     * @return bool Whether the action was performed successfully.
     * @throws Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var Organization[] $organizations */
        $organizations = $query->all();

        // Are we transferring the user's content to a different user?
        if (is_array($this->transferUsersTo) && isset($this->transferUsersTo[0])) {
            $this->transferUsersTo = $this->transferUsersTo[0];
        }

        if (!empty($this->transferUsersTo)) {
            $transferUsersTo = OrganizationPlugin::getInstance()->getOrganization()->getById($this->transferUsersTo);
        } else {
            $transferUsersTo = null;
        }

        // Transfer the users
        foreach ($organizations as $organization) {
            if ($transferUsersTo) {
                $users = $organization->getMembers(['status' => null]);
                foreach ($users->all() as $user) {
                    // Remove current association
                    OrganizationPlugin::getInstance()->getUser()->dissociate(
                        $user,
                        $organization
                    );
                    // Apply new association
                    OrganizationPlugin::getInstance()->getUser()->associate(
                        $user,
                        $transferUsersTo
                    );
                }
            }
            Craft::$app->getElements()->deleteElement($organization);
        }

        $this->setMessage(Craft::t('organization', 'Organizations were deleted.'));

        return true;
    }
}
