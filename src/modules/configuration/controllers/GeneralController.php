<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use flipbox\organization\models\Settings;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class GeneralController extends AbstractController
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
        $model = $this->module->module->getSettings();

        // Statuses from post
        if ($rawStatuses = $request->getBodyParam('statuses', [])) {

            $statusArray = [];

            foreach (ArrayHelper::toArray($rawStatuses) as $rawStatus) {

                // Make sure we have a label and value
                if (empty($rawStatus['label']) || empty($rawStatus['value'])) {

                    $model->addError('statuses',
                        Craft::t('organization', 'Each status must have a valid label and value.'));

                    break;

                }

                // Add status
                $statusArray[$rawStatus['value']] = $rawStatus['label'];

            }

        }

        // Set settings array
        $model->statuses = !empty($statusArray) ? $statusArray : null;

        // Handle each site's url/template settings
        foreach (Craft::$app->getSites()->getAllSites() as $site) {

            $namespace = 'sites.' . $site->handle;

            $postedSettings = $request->getBodyParam($namespace);

            $siteSettings = $model->getSite($site->id);
            $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

            if ($siteSettings->hasUrls) {
                $siteSettings->uriFormat = $postedSettings['uriFormat'];
                $siteSettings->template = $postedSettings['template'];
            } else {
                $siteSettings->uriFormat = null;
                $siteSettings->template = null;
            }

        }

        // Save settings
        if (!$this->module->getGeneral()->save($model)) {

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