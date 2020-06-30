<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\gateways;

use Craft;
use craft\commerce\base\Plan as BasePlan;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\elements\Subscription;
use craft\commerce\errors\SubscriptionException;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\subscriptions\SubscriptionForm as BaseSubscriptionForm;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\base\SubscriptionGateway as BaseGateway;
use craft\commerce\paymongo\errors\PaymentSourceException;
use craft\commerce\paymongo\events\SubscriptionRequestEvent;
use craft\commerce\paymongo\models\forms\payment\PaymentIntent as PaymentForm;
use craft\commerce\paymongo\models\forms\Subscription as SubscriptionForm;
use craft\commerce\paymongo\models\PaymentIntent as PaymentIntentModel;
use craft\commerce\paymongo\Plugin as PaymongoPlugin;
use craft\commerce\paymongo\responses\PaymentIntentResponse;
use craft\commerce\paymongo\web\assets\intentsform\IntentsFormAsset;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\View;
use craft\commerce\paymongo\PaymongoObject;
use craft\commerce\paymongo\util;
use craft\commerce\paymongo\PaymentIntent;
use yii\base\NotSupportedException;
use craft\commerce\paymongo\Paymongo;
use craft\commerce\paymongo\gateways\Gateway;
use craft\commerce\paymongo\gateways\PaymentIntents as GatewayPaymentIntent;
use yii\web\BadRequestHttpException;
use yii\web\Response;
/**
 * This class represents the PayMongo Payment Intents gateway
 *
 * @since 2.0
 **/
class PaymentIntents extends BaseGateway
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $publicKey;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var bool
     */
    public $sendReceiptEmail;

    /**
     * @var string
     */
    public $signingSecret;

    // Public methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce-paymongo', 'PayMongo Payment Intents');
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
     
        $defaults = [
            'gateway' => $this,
            'paymentForm' => $this->getPaymentFormModel(),
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
        
        $view->registerAssetBundle(IntentsFormAsset::class);

        $html = $view->renderTemplate('commerce-paymongo/paymentForms/intentsForm', $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        try {
            /** @var PaymentIntent $intent */
            $intent = PaymentIntent::retrieve($reference);
            $intent->capture([], ['idempotency_key' => $reference]);

            return $this->createPaymentResponseFromApiResource($intent);
        } catch (\Exception $exception) {
            return $this->createPaymentResponseFromError($exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new PaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function getResponseModel($data): RequestResponseInterface
    {
        return new PaymentIntentResponse($data);
    }

    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {       
        $paymentIntentReference = Craft::$app->getRequest()->getParam('payment_intent');
       
        /** @var PaymentIntent $paymentIntent */
    
        $transactionResponse = json_decode($transaction->response,true);
        
        $PAYMONGO = new Paymongo();
        $apikey = Craft::parseEnv(Commerce::getInstance()->getGateways()->getGatewayById($transaction->gatewayId)->apiKey);
        $retreiveResponse= $PAYMONGO->paymentIntent()->find($transactionResponse['client_key'],$paymentIntentReference,$apikey);
           
        $paymentIntentResponse = $retreiveResponse['data']['attributes'];
        $paymentIntentResponse['id'] = $retreiveResponse['data']['id'];
        $paymentIntentResponse['object'] = $retreiveResponse['data']['type'];
         
        $paymentIntentResponse = util\Util::convertToPaymongoObject($paymentIntentResponse, null);
        $paymentIntentResponse->setLastResponse($paymentIntentResponse);
         
        
        // Update the intent with the latest.
        $paymentIntentsService = PaymongoPlugin::getInstance()->getPaymentIntents();       
        $paymentIntent = $paymentIntentsService->getPaymentIntentByReference($paymentIntentReference);
       
        // Make sure we have the payment intent before we attempt to do anything with it.
        if ($paymentIntent) {
            $paymentIntent->intentData = $paymentIntentResponse->jsonSerialize();
            $paymentIntentsService->savePaymentIntent($paymentIntent);
        }

        $intentData = $paymentIntentResponse->jsonSerialize();
        
        if (!empty($intentData['payment_method'])) {
          
            try {
                
                $this->_confirmPaymentIntent($paymentIntentResponse, $transaction);
            } catch (\Exception $exception) {
                return $this->createPaymentResponseFromError($exception);
            }
        }

        return $this->createPaymentResponseFromApiResource($paymentIntentResponse);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('commerce-paymongo/gatewaySettings/intentsSettings', ['gateway' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        
    }
    
    /**
     * @inheritdoc
     * @throws SubscriptionException if there was a problem subscribing to the plan
     */
    public function subscribe(User $user, BasePlan $plan, BaseSubscriptionForm $parameters): SubscriptionResponseInterface
    {
       
    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource($token): bool
    {
        try {
            /** @var PaymentMethod $paymentMethod */
            $paymentMethod = PaymentMethod::retrieve($token);
            $paymentMethod->detach();
        } catch (\Throwable $throwable) {
            // Assume deleted.
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getBillingIssueDescription(Subscription $subscription): string
    {
        $subscriptionData = $this->_getExpandedSubscriptionData($subscription);
        $intentData = $subscriptionData['latest_invoice']['payment_intent'];

        if (in_array($subscriptionData['status'], ['incomplete', 'past_due', 'unpaid'])) {
            switch ($intentData['status']) {
                case 'requires_payment_method':
                    return $subscription->hasStarted ? Craft::t('commerce-paymongo', 'To resume the subscription, please provide a valid payment method.') : Craft::t('commerce-paymongo', 'To start the subscription, please provide a valid payment method.');
                case 'requires_action':
                    return $subscription->hasStarted ? Craft::t('commerce-paymongo', 'To resume the subscription, please complete 3DS authentication.') : Craft::t('commerce-paymongo', 'To start the subscription, please complete 3DS authentication.');
            }
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getBillingIssueResolveFormHtml(Subscription $subscription): string
    {
        $subscriptionData = $this->_getExpandedSubscriptionData($subscription);
        $intentData = $subscriptionData['latest_invoice']['payment_intent'];

        if (in_array($subscriptionData['status'], ['incomplete', 'past_due', 'unpaid'])) {
            $clientSecret = $intentData['client_secret'];
            switch ($intentData['status']) {
                case 'requires_payment_method':
                case 'requires_confirmation':
                    return $this->getPaymentFormHtml(['clientSecret' => $clientSecret]);
                case 'requires_action':
                    return $this->getPaymentFormHtml(['clientSecret' => $clientSecret, 'scenario' => '3ds']);
            }
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getHasBillingIssues(Subscription $subscription): bool
    {
        $subscription = $this->refreshSubscriptionData($subscription);
        $subscriptionData = $subscription->getSubscriptionData();
        $intentData = $subscriptionData['latest_invoice']['payment_intent'];

        return in_array($subscriptionData['status'], ['incomplete', 'past_due', 'unpaid']) && in_array($intentData['status'], ['requires_payment_method', 'requires_confirmation', 'requires_action']);
    }

    /**
     * @inheritdoc
     */
    public function handleWebhook(array $data)
    {
        switch ($data['type']) {
            case 'invoice.payment_failed':
                $this->handleInvoiceFailed($data);
                break;
        }

        parent::handleWebhook($data);
    }

    // Protected methods
    // =========================================================================

    /**
     * Handle a failed invoice by updating the subscription data for the subscription it failed.
     *
     * @param $data
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function handleInvoiceFailed(array $data)
    {
        $paymongoInvoice = $data['data']['object'];

        // Sanity check
        if ($paymongoInvoice['paid']) {
            return;
        }

        $subscriptionReference = $paymongoInvoice['subscription'] ?? null;

        if (!$subscriptionReference || !($subscription = Subscription::find()->anyStatus()->reference($subscriptionReference)->one())) {
            Craft::warning('Subscription with the reference “' . $subscriptionReference . '” not found when processing webhook ' . $data['id'], 'paymongo');

            return;
        }

        $this->refreshSubscriptionData($subscription);
    }

    /**
     * @inheritdoc
     */
    protected function handleSubscriptionUpdated(array $data)
    {
        parent::handleSubscriptionUpdated($data);
    }

    /**
     * @inheritdoc
     */
    protected function authorizeOrPurchase(Transaction $transaction, BasePaymentForm $form, bool $capture = true): RequestResponseInterface
    {
        
        //attachmentHappens here

        /** @var PaymentForm $form */
        $requestData = $this->buildRequestData($transaction);
        $paymentMethodId = $form->paymentMethodId;

        $customer = null;
        $paymentIntent = null;

        $paymongoPlugin = PaymongoPlugin::getInstance();
       
        if ($form->customer) {           
            $requestData['customer'] = $form->customer;
            $customer = $paymongoPlugin->getCustomers()->getCustomerByReference($form->customer);
        } else if ($user = $transaction->getOrder()->getUser()) {
     
            $customer = $paymongoPlugin->getCustomers()->getCustomer($this->id, $user);
           
            $requestData['customer'] = $customer->reference;
        }

        $requestData['payment_method'] = $paymentMethodId;
      
        try {
            // If this is a customer that's logged in, attempt to continue the timeline
            if ($customer) {
                $paymentIntentService = $paymongoPlugin->getPaymentIntents();
                $paymentIntent = $paymentIntentService->getPaymentIntent($this->id, $transaction->orderId, $customer->id);
            }
          
                
            $requestData['capture_method'] = 'automatic';
            $requestData['confirmation_method'] = 'manual';
            $requestData['confirm'] = false;
            $request = [
                'amount' => $transaction->paymentAmount,
                'currency' => $transaction->paymentCurrency,
                'description' => Craft::t('commerce-paymongo', 'Order') . ' #' . Commerce::getInstance()->getCarts()->getCart()->id,
                'method_allowed' => 'card',
                'request_three_d_secure' => 'automatic'
            ];
              
            $PAYMONGO = new Paymongo();
            $apikey = Craft::parseEnv(Commerce::getInstance()->getGateways()->getGatewayById($transaction->gatewayId)->apiKey);
                  
            $reponseData = $PAYMONGO->paymentIntent()->create([
                'amount' => $request['amount'],
                'payment_method_allowed' => [
                    $request['method_allowed']
                ],
                'payment_method_options' => [
                    'card' => [
                        'request_three_d_secure' => $request['request_three_d_secure']
                    ]
                ],
                'description' =>  $request['description']. " : " . Commerce::getInstance()->getCarts()->getCart()->number,
                'statement_descriptor' => $request['description']. " : " . Commerce::getInstance()->getCarts()->getCart()->number,
                'currency' => $request['currency'],
            ],$apikey); 
            $reponseData['data']['object'] = $reponseData['data']['type'];
             
               
            $paymongoPaymentIntent = util\Util::convertToPaymongoObject($reponseData['data'], null);
            $paymongoPaymentIntent->setLastResponse($paymongoPaymentIntent);
                
        
            if ($customer) {                    
                if( isset($paymongoPaymentIntent->id) ){
                    $reference =  $paymongoPaymentIntent->id;
                }else{
                    $reference =  $paymongoPaymentIntent->getId();
                }
                $paymentIntent = new PaymentIntentModel([
                    'orderId' => $transaction->orderId,
                    'customerId' => $customer->id,
                    'gatewayId' => $this->id,
                    'reference' => $reference,
                ]);
            }
         
            if ($paymentIntent) {               
                // Save data before confirming.
                $paymentIntent->intentData = json_encode($paymongoPaymentIntent);                
                $paymentIntentService->savePaymentIntent($paymentIntent);             
                
            }          
            
            $this->_confirmPaymentIntent($paymongoPaymentIntent, $transaction,$requestData);
                
         
            return $this->createPaymentResponseFromApiResource($paymongoPaymentIntent);
        } catch (\Exception $exception) {
            return $this->createPaymentResponseFromError($exception);
        }
    }

  
    // Private methods
    // =========================================================================

    /**
     * Confirm a payment intent and set the return URL.
     *
     * @param PaymentIntent $paymongoPaymentIntent
     */
    private function _confirmPaymentIntent($paymongoPaymentIntent, Transaction $transaction,$requestData = null)
    {  
         //attach payment method to payment intent here.
         $attachment = [               
            'payment_method' => $requestData['payment_method'],
            'client_key' => $paymongoPaymentIntent->client_key,
            'return_url' => UrlHelper::actionUrl('commerce/payments/complete-payment', ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash, 'payment_intent' => $paymongoPaymentIntent->id])
        ];
     
        $PAYMONGO = new Paymongo();
    
        $apikey = Craft::parseEnv(Commerce::getInstance()->getGateways()->getGatewayById($transaction->gatewayId)->apiKey);
        
 
        $attachmentResponse= $PAYMONGO->paymentIntent()->attach([
            'return_url' => $attachment['return_url'],
            'payment_method' =>$attachment['payment_method'],
            'client_key' => $attachment['client_key']
            ], $paymongoPaymentIntent->id,$apikey);
          
            $paymentIntentConfirmResponse = $attachmentResponse['data']['attributes'];
            $paymentIntentConfirmResponse['id'] = $attachmentResponse['data']['id'];
            $paymentIntentConfirmResponse['object'] = $attachmentResponse['data']['type'];
            
           // $paymentIntentConfirmResponse = PaymentIntent::create($paymentIntentConfirmResponse);
           $paymentIntentConfirmResponse = util\Util::convertToPaymongoObject($paymentIntentConfirmResponse, null);
         
           $paymentIntentConfirmResponse->setLastResponse(json_encode($paymentIntentConfirmResponse));
                   
           $paymongoPaymentIntent->refreshFrom($paymentIntentConfirmResponse, null);    
                       
          
    }

    /**
     * Get the expanded subscription data, including payment intent for latest invoice.
     *
     * @param Subscription $subscription
     * @return array
     */
    private function _getExpandedSubscriptionData(Subscription $subscription): array
    {
        $subscriptionData = $subscription->getSubscriptionData();       

        return $subscriptionData;
    }
}
