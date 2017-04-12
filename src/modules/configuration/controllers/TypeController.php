<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\modules\configuration\controllers;

use Craft;
use flipbox\organization\models\Type;

/**
 * @package flipbox\organization\modules\configuration\controllers
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class TypeController extends AbstractController
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

        // They type identifier (optional)
        if ($identifier = Craft::$app->getRequest()->getBodyParam('identifier')) {
            $model = $this->module->module->getType()->get($identifier);
        } else {
            $model = $this->module->module->getType()->create();
        }

        /** @var Type $model */

        // Set name/handle
        $model->name = Craft::$app->getRequest()->getBodyParam('name', $model->name);
        $model->handle = Craft::$app->getRequest()->getBodyParam('handle', $model->handle);

        // Handle each site's settings
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

            $siteSettings->setFieldLayout(
                Craft::$app->getFields()->assembleLayoutFromPost($namespace)
            );

        }

        // Save
        if (!$this->module->getType()->save($model)) {

            /// Ajax request
            if (!$request->getAcceptsJson()) {

                // Fail message
                $message = Craft::t('organization', 'Organization type NOT saved successfully.');

                // Flash fail message
                $session->setError($message);

                // Send the element back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'type' => $model
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
            $message = Craft::t('organization', 'Organization type saved successfully.');

            // Flash success message
            $session->setNotice($message);

            return $this->redirectToPostedUrl($model);

        }

        return $this->asJson($model);

    }

    /**
     * @return \yii\web\Response
     */
    public function actionDelete()
    {

        // Admins and Post requests only
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $model = $this->module->module->getType()->get(
            Craft::$app->getRequest()->getBodyParam('identifier')
        );

        // Delete
        if (!$this->module->getType()->delete($model)) {

            /// Ajax request
            if (!$request->getAcceptsJson()) {

                // Fail message
                $message = Craft::t('organization', 'Organization type NOT deleted successfully.');

                // Flash fail message
                $session->setError($message);

                // Send the element back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'type' => $model
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
            $message = Craft::t('organization', 'Organization type deleted successfully.');

            // Flash success message
            $session->setNotice($message);

            return $this->redirectToPostedUrl($model);

        }

        return $this->asJson($model);

    }

}
