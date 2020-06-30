<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\controllers;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\base\SubscriptionGateway;
use craft\web\Controller as BaseController;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * This controller provides functionality to load data from AWS.
 * 
 */
class DefaultController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->defaultAction = 'fetch-plans';
    }

    /**
     * Load PayMongo Subscription plans for a gateway.
     *
     * @return Response
     */
    public function actionFetchPlans()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $gatewayId = $request->getRequiredBodyParam('gatewayId');

        if (!$gatewayId) {
            return $this->asJson([]);
        }

        try {
            $gateway = Commerce::getInstance()->getGateways()->getGatewayById((int)$gatewayId);

            if (!$gateway || !$gateway instanceof SubscriptionGateway) {
                throw new BadRequestHttpException('That is not a valid gateway id.');
            }

            return $this->asJson($gateway->getSubscriptionPlans());
        } catch (\Throwable $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    public function actionCreatePaymentForm()
    {
        try{
            $gateway = Commerce::getInstance()->getGateways()->getGatewayById((int)$gatewayId);

            return $this->asJson($gateway);
        }catch(\Throwable $e){
            return $this->asErrorJson($e->getMessage());
        }
    }
}
