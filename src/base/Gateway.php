<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\base;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\base\SubscriptionGateway as BaseGateway;
use craft\commerce\errors\PaymentException;
use craft\commerce\errors\TransactionException;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\errors\CustomerException;
use craft\commerce\paymongo\events\BuildGatewayRequestEvent;
use craft\commerce\paymongo\events\ReceiveWebhookEvent;
use craft\commerce\paymongo\Plugin as PaymongoPlugin;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response;
use craft\web\Response as WebResponse;
use craft\commerce\paymongo\ApiResource;
use craft\commerce\paymongo\Customer;
use yii\base\NotSupportedException;

/**
 * This class represents the abstract PayMongo base gateway
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 1.0
 */
abstract class Gateway extends BaseGateway
{
    // Constants
    // =========================================================================

    /**
     * @event BuildGatewayRequestEvent The event that is triggered when a gateway request is being built.
     *
     * Plugins get a chance to provide additional data to any request that is made to PayMongo in the context of paying for an order. This includes capturing and refunding transactions.
     *
     * There are some restrictions:
     *     Changes to the `Transaction` model available as the `transaction` property will be ignored;
     *     Changes to the `order_id`, `order_number`, `transaction_id`, `client_ip`, and `transaction_reference` metadata keys will be ignored;
     *     Changes to the `amount`, `currency` and `description` request keys will be ignored;
     *
     * ```php
     * use craft\commerce\models\Transaction;
     * use craft\commerce\paymongo\events\BuildGatewayRequestEvent;
     * use craft\commerce\paymongo\base\Gateway as PaymongoGateway;
     * use yii\base\Event;
     *
     * Event::on(PaymongoGateway::class, PaymongoGateway::EVENT_BUILD_GATEWAY_REQUEST, function(BuildGatewayRequestEvent $e) {
     *     if ($e->transaction->type === 'refund') {
     *         $e->request['someKey'] = 'some value';
     *     }
     * });
     * ```
     *
     */
    const EVENT_BUILD_GATEWAY_REQUEST = 'buildGatewayRequest';

    /**
     * @event ReceiveWebhookEvent The event that is triggered when a valid webhook is received.
     *
     * Plugins get a chance to do something whenever a webhook is received. This event will be fired regardless the Gateway has done something with the webhook or not.
     *
     * ```php
     * use craft\commerce\paymongo\events\ReceiveWebhookEvent;
     * use craft\commerce\paymongo\base\Gateway as PaymongoGateway;
     * use yii\base\Event;
     *
     * Event::on(PaymongoGateway::class, PaymongoGateway::EVENT_RECEIVE_WEBHOOK, function(ReceiveWebhookEvent $e) {
     *     if ($e->webhookData['type'] == 'charge.dispute.created') {
     *         if ($e->webhookData['data']['object']['amount'] > 1000000) {
     *             // Be concerned that USD 10,000 charge is being disputed.
     *         }
     *     }
     * });
     * ```
     */
    const EVENT_RECEIVE_WEBHOOK = 'receiveWebhook';

     /**
     * string The PayMongo API version to use.
     */
    const PAYMONGO_API_VERSION = '2020-06-19';

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


    // Public Methods
    // =========================================================================

    public function init()
    {       
        parent::init();        
    }

    /**
     * @inheritdoc
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->authorizeOrPurchase($transaction, $form, false);
    }

    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // It's exactly the same thing,
        return $this->completePurchase($transaction);
    }

   
    /**
     * @inheritdoc
     */
    public function processWebHook(): WebResponse
    {
        
    }

    /**
     * @inheritdoc
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->authorizeOrPurchase($transaction, $form);
    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return true;
    }


    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPlanSwitch(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsReactivation(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return true;
    }

    /**
     * Returns response model wrapping the passed data.
     *
     * @param mixed $data
     *
     * @return RequestResponseInterface
     */
    abstract public function getResponseModel($data): RequestResponseInterface;

    // Protected methods
    // =========================================================================

    /**
     * Build the request data array.
     *
     * @param Transaction $transaction the transaction to be used as base
     *
     * @return array
     * @throws NotSupportedException
     */
    protected function buildRequestData(Transaction $transaction, $context = 'charge'): array
    {
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($transaction->paymentCurrency);

        if (!$currency) {
            throw new NotSupportedException('The currency “' . $transaction->paymentCurrency . '” is not supported!');
        }

        $metadata = [
            'order_id' => $transaction->getOrder()->id,
            'order_number' => $transaction->getOrder()->number,
            'transaction_id' => $transaction->id,
            'transaction_reference' => $transaction->hash,
        ];

        $appRequest = Craft::$app->getRequest();
        if (!$appRequest->getIsConsoleRequest()) {
            $metadata['client_ip'] = $appRequest->getUserIP();
        }

        $request = [
            'amount' => $transaction->paymentAmount * (10 ** $currency->minorUnit),
            'currency' => $transaction->paymentCurrency,
            'description' => Craft::t('commerce-paymongo', 'Order') . ' #' . $transaction->orderId,
            'metadata' => $metadata
        ];

        $event = new BuildGatewayRequestEvent([
            'transaction' => $transaction,
            'metadata' => $metadata,
            'request' => $request
        ]);

        // TODO provide context
        $this->trigger(self::EVENT_BUILD_GATEWAY_REQUEST, $event);

        $request = array_merge($event->request, $request);
        $request['metadata'] = array_merge($event->metadata, $metadata);

        if ($this->sendReceiptEmail) {
            $request['receipt_email'] = $transaction->getOrder()->email;
        }

        return $request;
    }

    /**
     * Create a Response object from an ApiResponse object.
     *
     * @param ApiResource $resource
     *
     * @return RequestResponseInterface
     */
    protected function createPaymentResponseFromApiResource($resource): RequestResponseInterface
    {
        $data = $resource->jsonSerialize();

        return $this->getResponseModel($data);
    }

    /**
     * Create a Response object from an Exception.
     *
     * @param \Exception $exception
     *
     * @return RequestResponseInterface
     * @throws \Exception if not a PayMongo exception
     */
    protected function createPaymentResponseFromError(\Exception $exception): RequestResponseInterface
    {
        if ($exception instanceof CardException) {
            $body = $exception->getJsonBody();
            $data = $body;
            $data['code'] = $body['error']['code'];
            $data['message'] = $body['error']['message'];
            $data['id'] = $body['error']['charge'];
        } else if ($exception instanceof ExceptionInterface) {
            // So it's not a card being declined but something else. ¯\_(ツ)_/¯
            $body = $exception->getJsonBody();
            $data = $body;
            $data['id'] = null;
            $data['message'] = $body['error']['message'] ?? $exception->getMessage();
            $data['code'] = $body['error']['code'] ?? $body['error']['type'] ?? $exception->getPaymongoCode();
        } else {
            throw $exception;
        }

        return $this->getResponseModel($data);
    }

    /**
     * Get the PayMongo customer for a User.
     *
     * @param int $userId
     *
     * @return Customer
     * @throws CustomerException if wasn't able to create or retrieve PayMongo Customer.
     */
    protected function getPaymongoCustomer(int $userId): Customer
    {
        try {
            $user = Craft::$app->getUsers()->getUserById($userId);
            $customers = PaymongoPlugin::getInstance()->getCustomers();
            $customer = $customers->getCustomer($this->id, $user);
            $paymongoCustomer = Customer::retrieve($customer->reference);

            if (!empty($paymongoCustomer->deleted)) {
                // Okay, retry one time.
                $customers->deleteCustomerById($customer->id);
                $customer = $customers->getCustomer($this->id, $user);
                $paymongoCustomer = Customer::retrieve($customer->reference);
            }

            return $paymongoCustomer;
        } catch (\Exception $exception) {
            throw new CustomerException('Could not fetch PayMongo customer: ' . $exception->getMessage());
        }
    }

    /**
     * Normalize one-time payment token to a source token, that may or may not be multi-use.
     *
     * @param string $token
     * @return string
     */
    protected function normalizePaymentToken(string $token = ''): string
    {
        if (StringHelper::substr($token, 0, 4) === 'tok_') {
            try {
                /** @var Source $tokenSource */
                $tokenSource = Source::create([
                    'type' => 'card',
                    'token' => $token
                ]);

                return $tokenSource->id;
            } catch (\Exception $exception) {
                Craft::error('Unable to normalize payment token: ' . $token . ', because ' . $exception->getMessage());
            }
        }

        return $token;
    }

    /**
     * Make an authorize or purchase request to PayMongo
     *
     * @param Transaction $transaction the transaction on which this request is based
     * @param BasePaymentForm $form payment form parameters
     * @param bool $capture whether funds should be captured immediately, defaults to true.
     *
     * @return RequestResponseInterface
     * @throws NotSupportedException if unrecognized currency specified for transaction
     * @throws PaymentException if unexpected payment information provided.
     * @throws \Exception if reasons
     */
    abstract protected function authorizeOrPurchase(Transaction $transaction, BasePaymentForm $form, bool $capture = true): RequestResponseInterface;

    /**
     * Handle a webhook.
     *
     * @param array $data
     * @throws TransactionException
     */
    protected function handleWebhook(array $data)
    {
        // Do nothing
    }
}
