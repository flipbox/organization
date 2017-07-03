<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers;

use Craft;
use flipbox\organization\elements\Organization as OrganizationElement;
use flipbox\organization\Organization as OrganizationPlugin;
use yii\web\ForbiddenHttpException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class OrganizationController extends AbstractController
{


    /**
     * @return null|\yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionSave()
    {

        // POST, PUT, PATCH
        $this->requirePostPutPatchRequest();
        $this->requireAdmin();

        $organizationService = OrganizationPlugin::getInstance()->getOrganization();

        // Organization Id
        if ($organizationIdentifier = Craft::$app->getRequest()->getBodyParam('identifier')) {

            $organizationElement = $organizationService->get($organizationIdentifier);

        } else {

            $organizationElement = $organizationService->create();

        }

        /** @var OrganizationElement $organizationElement */
        $userElement = Craft::$app->getUser()->getIdentity();

        if ($organizationElement->getId()) {

            if (!OrganizationPlugin::getInstance()->getPermission()->canCreateOrganization($userElement)) {
                throw new ForbiddenHttpException("You do not have permission to create an organization.");
            }

        } else {

            if (!OrganizationPlugin::getInstance()->getPermission()->canUpdateOrganization($userElement, $organizationElement)) {
                throw new ForbiddenHttpException("You do not have permission to update an organization.");
            }

        }

        // Populate element
        $organizationService->populateFromRequest($organizationElement);

        // Save
        if (Craft::$app->getElements()->saveElement($organizationElement)) {

            // Success message
            $message = Craft::t('organization', 'Successfully saved organization.');

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
            return $this->redirectToPostedUrl($organizationElement);

        }

        // Fail message
        $message = Craft::t('organization', 'Failed to saved organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {

            return $this->asErrorJson(
                $organizationElement->getErrors()
            );

        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        // Send the element back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'organization' => $organizationElement
        ]);

        return null;

    }

    /**
     * @return \yii\web\Response
     */
    public function actionDelete()
    {

        // POST, DELETE
        $this->requirePostDeleteRequest();

        // Optional attributes
        $organizationId = Craft::$app->getRequest()->getRequiredBodyParam('identifier');

        /** @var OrganizationElement $organizationElement */
        $organizationElement = OrganizationPlugin::getInstance()->getOrganization()->getById($organizationId);

        // Delete
        if (Craft::$app->getElements()->deleteElement($organizationElement)) {

            // Success message
            $message = Craft::t('organization', 'Successfully deleted organization.');

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
            return $this->redirectToPostedUrl($organizationElement);

        }

        // Fail message
        $message = Craft::t('organization', 'Failed to delete organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {

            return $this->asErrorJson(
                $organizationElement->getErrors()
            );

        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        return null;

    }
}
