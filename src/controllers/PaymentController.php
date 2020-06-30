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
use craft\web\View;
use craft\commerce\paymongo\responses\PaymentIntentResponse;
use craft\commerce\paymongo\web\assets\intentsform\IntentsFormAsset;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\commerce\paymongo\Paymongo;
use craft\commerce\paymongo\gateways\Gateway;
use craft\commerce\paymongo\gateways\PaymentIntents as GatewayPaymentIntent;


use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\paymongo\models\forms\payment\PaymentIntent as PaymentForm;



/**
 * This controller provides functionality to load data from AWS.
 *
 */
class PaymentController extends BaseController
{
  /**
     * @var string
     */
    public $apiKey;

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    // Protected Methods
    // =========================================================================

    // Properties
    // =========================================================================

   

    /**
     * @inheritDoc
     */
    public function beforeAction($action): bool
    {
        // Don't enable CSRF validation for complete-payment requests
        if ($action->id === 'complete-payment') {
            $this->enableCsrfValidation = false;
            
        }

        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function init()
    { 
        parent::init();
        $this->defaultAction = 'create-payment-form';
    }

   
    public function actionCreatePaymentForm()
    {
     
        try{
           
            $params = array();     
            $gateway = Craft::$app->request->getQueryParam('gateway');       
            $defaults = [
                'gateway' => Commerce::getInstance()->getGateways()->getGatewayById($gateway),
                'paymentForm' => Commerce::getInstance()->getGateways()->getGatewayById($gateway)->getPaymentFormModel(),
                'scenario' => 'payment',
            ];
         
         

            $params = array_merge($defaults, $params);
        
            // If there's no order passed, add the current cart if we're not messing around in backend.
            if (!isset($params['order']) && !Craft::$app->getRequest()->getIsCpRequest()) {
                $billingAddress = Commerce::getInstance()->getCarts()->getCart()->getBillingAddress();
    
                if (!$billingAddress) {
                    $billingAddress = Commerce::getInstance()->getCustomers()->getCustomer()->getPrimaryBillingAddress();
                }
            } else {
                $billingAddress = $params['order']->getBillingAddress();
            }
 
            if ($billingAddress) {
                $params['billingAddress'] = $billingAddress;
            }
 
            $view = Craft::$app->getView();
    
            $previousMode = $view->getTemplateMode();
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        
            $html = $view->renderTemplate('commerce-paymongo/paymentForms/createIntentsForm', $params);
            $view->setTemplateMode($previousMode);
 
         return $html;

        }catch(\Throwable $e){
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new PaymentForm();
    }
}


