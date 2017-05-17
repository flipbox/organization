<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers;

use Craft;
use flipbox\organization\elements\Organization;
use flipbox\organization\models\Type;
use flipbox\organization\Organization as OrganizationPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class TypeController extends AbstractController
{

    /**
     * @return \yii\web\Response
     */
    public function actionDissociate()
    {

        // POST, DELETE
        $this->requirePostDeleteRequest();

        // Optional attributes
        $typeIdentifier = Craft::$app->getRequest()->getBodyParam('type');
        $organizationIdentifier = Craft::$app->getRequest()->getBodyParam('organization');

        /** @var Type $typeModel */
        $typeModel = OrganizationPlugin::getInstance()->getType()->get($typeIdentifier);

        /** @var Organization $organizationElement */
        $organizationElement = OrganizationPlugin::getInstance()->getOrganization()->get($organizationIdentifier);

        // Dissociate
        if (OrganizationPlugin::getInstance()->getType()->dissociate($typeModel, $organizationElement)) {

            // Success message
            $message = Craft::t('organization', 'Type was successfully dissociated from organization.');

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
        $message = Craft::t('organization', 'Failed to dissociate type from organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {

            return $this->asErrorJson($message);

        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        // Send the model back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'type' => $typeModel,
            'organization' => $organizationElement
        ]);

        return null;

    }

    /**
     * @return \yii\web\Response
     */
    public function actionAssociate()
    {

        // POST, PUT, PATCH
        $this->requirePostPutPatchRequest();

        // Required attributes
        $typeIdentifier = Craft::$app->getRequest()->getRequiredBodyParam('type');
        $organizationIdentifier = Craft::$app->getRequest()->getRequiredBodyParam('organization');

        // Optional attribute
        $isPrimary = Craft::$app->getRequest()->getBodyParam('primary', false);

        /** @var Type $typeModel */
        $typeModel = OrganizationPlugin::getInstance()->getType()->get($typeIdentifier);

        /** @var Organization $organizationElement */
        $organizationElement = OrganizationPlugin::getInstance()->getOrganization()->get($organizationIdentifier);

//        // Check if already associated
//        if ($organizationElement->getHasType($typeModel)) {
//
//            // Success message
//            $message = Craft::t('organization', 'Type was already associated to organization.');
//
//            // Ajax request
//            if (Craft::$app->getRequest()->isAjax) {
//
//                return $this->asJson([
//                    'success' => true,
//                    'message' => $message
//                ]);
//
//            }
//
//            // Flash success message
//            Craft::$app->getSession()->setNotice($message);
//
//            // Redirect
//            return $this->redirectToPostedUrl();
//
//        }

        // Associate
        if (OrganizationPlugin::getInstance()->getType()->associate($typeModel, $organizationElement, $isPrimary)) {

            // Success message
            $message = Craft::t('organization', 'Type was successfully associated to organization.');

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
        $message = Craft::t('organization', 'Failed to associate type to organization.');

        // Ajax request
        if (Craft::$app->getRequest()->isAjax) {

            return $this->asErrorJson($message);

        }

        // Flash fail message
        Craft::$app->getSession()->setError($message);

        // Send the model back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'type' => $typeModel,
            'organization' => $organizationElement
        ]);

        return null;

    }

}
