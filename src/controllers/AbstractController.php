<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/organization/license
 * @link       https://www.flipboxfactory.com/software/organization/
 */

namespace flipbox\organization\controllers;

use Craft;
use craft\web\Controller;
use flipbox\organization\Organization;
use yii\web\HttpException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property Organization $module
 */
abstract class AbstractController extends Controller
{

    /**
     * Throws a 400 error if this isn’t a POST, PUT, or PATCH request
     *
     * @throws HttpException
     * @return void
     */
    public function requirePostPutPatchRequest()
    {
        if (!(Craft::$app->getRequest()->getIsPost() || !Craft::$app->getRequest()->getIsPatch() || !Craft::$app->getRequest()->getIsPut())) {
            throw new HttpException(400);
        }
    }

    /**
     * Throws a 400 error if this isn’t a POST or DELETE request
     *
     * @throws HttpException
     * @return void
     */
    public function requirePostDeleteRequest()
    {
        if (!(Craft::$app->getRequest()->getIsPost() || !Craft::$app->getRequest()->getIsDelete())) {
            throw new HttpException(400);
        }
    }

}
