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

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class ChangeOrganizationStatus extends ElementAction
{

    public $status;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('organization', 'Change Status');
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
        $statuses = Json::encode(
            OrganizationPlugin::getInstance()->getOrganization()->getStatuses()
        );
        $redirect = Json::encode(Craft::$app->getSecurity()->hashData('organization'));

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        activate: function(\$selectedItems)
        {
            var modal = new Craft.ChangeOrganizationStatusModal(Craft.elementIndex.getSelectedElementIds(), {
                onSubmit: function()
                {
                    Craft.elementIndex.submitAction({$type}, Garnish.getPostData(modal.\$container));
                    modal.hide();

                    return false;
                },
                redirect: {$redirect},
                statuses: {$statuses}
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

        $successful = true;

        // Transfer the users
        foreach ($organizations as $organization) {
            if (!OrganizationPlugin::getInstance()->getOrganization()->changeStatus(
                $organization,
                $this->status
            )) {
                $successful = false;
            }
        }

        if ($successful) {
            $this->setMessage(Craft::t('organization', 'Organization statuses were changed.'));
            return true;
        }

        $this->setMessage(Craft::t('organization', 'Unable to change srganization statuses.'));
        return false;
    }
}
