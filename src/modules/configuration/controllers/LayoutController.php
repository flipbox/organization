<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers;

use Craft;
use flipbox\organization\models\Settings;
use flipbox\organization\Plugin as OrganizationPlugin;

/**
 * @package flipbox\organization\modules\configuration\controllers
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class LayoutController extends AbstractController
{

    /**
     * @return \yii\web\Response
     */
    public function actionSave()
    {

        // Admins and Post requests only
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        /** @var Settings $model */
        $model = OrganizationPlugin::getInstance()->getSettings();

        // Handle each site's url/template settings
        foreach (Craft::$app->getSites()->getAllSites() as $site) {

            $namespace = 'sites.' . $site->handle;

            $siteSettings = $model->getSite($site->id);
            $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

            // Group the field layout
            $siteSettings->setFieldLayout(
                Craft::$app->getFields()->assembleLayoutFromPost($namespace)
            );

        }

        // Save settings
        if (!$this->module->getLayout()->save($model)) {

            // Ajax request
            if (!$request->getAcceptsJson()) {

                // Fail message
                $message = Craft::t('organization', 'Settings NOT saved successfully.');

                // Flash fail message
                $session->setError($message);

                // Send the element back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'settings' => $model
                ]);

                // Redirect
                return $this->redirectToPostedUrl($model);

            }

            return $this->asJson([
                'success' => false,
                'errors' => $model->getErrors(),
            ]);

        }

        // Ajax request
        if (!$request->getAcceptsJson()) {

            // Success message
            $message = Craft::t('organization', 'Settings saved successfully.');

            // Flash success message
            $session->setNotice($message);

            return $this->redirectToPostedUrl($model);

        }

        return $this->asJson($model);

    }

}
