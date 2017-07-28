<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers;

use Craft;
use flipbox\organization\elements\Organization;
use flipbox\organization\helpers\User as UserHelper;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class UserController extends AbstractController
{

    /**
     * @return \yii\web\Response
     */
    public function actionDissociate()
    {

        // POST, DELETE
        $this->requirePostDeleteRequest();

        // Get body params
        $userIdentifier = Craft::$app->getRequest()->getBodyParam('user');
        $organizationIdentifier = Craft::$app->getRequest()->getBodyParam('organization');

        // Get user element
        $userElement = UserHelper::get($userIdentifier);

        // Get organization element
        /** @var Organization $organizationElement */
        $organizationElement = OrganizationPlugin::getInstance()->getOrganization()->get($organizationIdentifier);

        // Remove
        $success = OrganizationPlugin::getInstance()->getUser()->dissociate(
            $userElement,
            $organizationElement
        );

        // Action successful
        if ($success) {
            // Success message
            $message = Craft::t('organization', 'Successfully removed user from organization.');

            // Ajax request
            if (Craft::$app->getRequest()->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // Flash success message
            Craft::$app->getSession()->setNotice($message);

            // Redirect
            return $this->redirectToPostedUrl();
        }

        // Fail message
        $message = Craft::t('organization', 'Failed to remove user from organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {
            return $this->asErrorJson($message);
        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        return null;
    }

    /**
     * @return \yii\web\Response
     */
    public function actionAssociate()
    {

        // POST, PUT, PATCH
        $this->requirePostPutPatchRequest();

        // Get body params
        $userIdentifier = Craft::$app->getRequest()->getBodyParam('user');
        $organizationIdentifier = Craft::$app->getRequest()->getBodyParam('organization');
        $siteId = Craft::$app->getRequest()->getBodyParam('siteId');
        $sortOrder = Craft::$app->getRequest()->getBodyParam('sortOrder');

        // Get user element
        $userElement = UserHelper::get($userIdentifier);

        // Get organization element
        /** @var Organization $organizationElement */
        $organizationElement = OrganizationPlugin::getInstance()->getOrganization()->get($organizationIdentifier);

        // Action successful
        if (OrganizationPlugin::getInstance()->getUser()->associate($userElement, $organizationElement, $siteId, $sortOrder)) {
            // Success message
            $message = Craft::t('organization', 'Successfully associated user to organization.');

            // Ajax request
            if (Craft::$app->getRequest()->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // Flash success message
            Craft::$app->getSession()->setNotice($message);

            // Redirect
            return $this->redirectToPostedUrl();
        }

        // Fail message
        $message = Craft::t('organization', 'Failed to associate user to organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {
            return $this->asErrorJson($message);
        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        return null;
    }
}
