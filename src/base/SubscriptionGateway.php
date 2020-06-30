<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\base;

use Craft;
use craft\commerce\base\Plan as BasePlan;
use craft\commerce\base\PlanInterface;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\elements\Subscription;
use craft\commerce\errors\SubscriptionException;
use craft\commerce\models\Currency;
use craft\commerce\models\subscriptions\CancelSubscriptionForm as BaseCancelSubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionForm as BaseSubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionPayment;
use craft\commerce\models\subscriptions\SwitchPlansForm;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\events\CreateInvoiceEvent;
use craft\commerce\paymongo\models\forms\CancelSubscription;
use craft\commerce\paymongo\models\forms\Subscription as SubscriptionForm;
use craft\commerce\paymongo\models\forms\SwitchPlans;
use craft\commerce\paymongo\models\Invoice;
use craft\commerce\paymongo\models\Plan;
use craft\commerce\paymongo\Plugin as PaymongoPlugin;
use craft\commerce\paymongo\responses\SubscriptionResponse;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\View;

/**
 * This class represents the abstract PayMongo base gateway
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 1.0
 */
abstract class SubscriptionGateway extends Gateway
{
    // Constants
    // =========================================================================

    /**
     * @event CreateInvoiceEvent The event that is triggered when an invoice is being created on the gateway.
     *
     * Plugins get a chance to do something when an invoice is created on the PayMongo gateway.
     *
     * ```php
     * use craft\commerce\paymongo\events\CreateInvoiceEvent;
     * use craft\commerce\paymongo\base\SubscriptionGateway as PaymongoGateway;
     * use yii\base\Event;
     *
     * Event::on(PaymongoGateway::class, PaymongoGateway::EVENT_CREATE_INVOICE, function(CreateInvoiceEvent $e) {
     *     if ($e->invoiceData['billing'] === 'send_invoice') {
     *         // Forward this invoice to the accounting dpt.
     *     }
     * });
     * ```
     */
    const EVENT_CREATE_INVOICE = 'createInvoice';

    /**
     * @event SubscriptionRequestEvent The event that is triggered when a subscription request is being built.
     *
     * Plugins get a chance to tweak subscription parameters when subscribing.
     *
     * ```php
     * use craft\commerce\paymongo\events\SubscriptionRequestEvent;
     * use craft\commerce\paymongo\base\SubscriptionGateway as PaymongoGateway;
     * use yii\base\Event;
     *
     * Event::on(PaymongoGateway::class, PaymongoGateway::EVENT_BEFORE_SUBSCRIBE, function(SubscriptionRequestEvent $e) {
     *     $e->parameters['someKey'] = 'some value';
     *     unset($e->parameters['unneededKey']);
     * });
     * ```
     */
    const EVENT_BEFORE_SUBSCRIBE = 'beforeSubscribe';

    /**
     * string The PayMongo API version to use.
     */
    const PAYMONGO_API_VERSION = '2020-06-19';

    // Public Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    public function cancelSubscription(Subscription $subscription, BaseCancelSubscriptionForm $parameters): SubscriptionResponseInterface
    {
        //TODO : cleanup
    }

    // /**
    //  * @inheritdoc
    //  */
    public function getCancelSubscriptionFormHtml(Subscription $subscription): string
    {
         //TODO : cleanup
    }

    /**
     * @inheritdoc
     */
    public function getCancelSubscriptionFormModel(): BaseCancelSubscriptionForm
    {
        return new CancelSubscription();
    }

    /**
     * @inheritdoc
     */
    public function getNextPaymentAmount(Subscription $subscription): string
    {
        $data = $subscription->subscriptionData;
        $currencyCode = strtoupper($data['plan']['currency']);
        $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso($currencyCode);

        if (!$currency) {
            Craft::warning('Unsupported currency - ' . $currencyCode, 'paymongo');

            return (float)0;
        }

        return $data['plan']['amount'] / (10 ** $currency->minorUnit) . ' ' . $currencyCode;
    }

    /**
     * @inheritdoc
     */
    public function getPlanModel(): BasePlan
    {
        return new Plan();
    }

    /**
     * @inheritdoc
     */
    public function getPlanSettingsHtml(array $params = [])
    {
        return Craft::$app->getView()->renderTemplate('commerce-paymongo/planSettings', $params);
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionFormModel(): BaseSubscriptionForm
    {
        return new SubscriptionForm();
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPayments(Subscription $subscription): array
    {
        $payments = [];

        $invoices = PaymongoPlugin::getInstance()->getInvoices()->getSubscriptionInvoices($subscription->id);

        foreach ($invoices as $invoice) {
            $data = $invoice->invoiceData;

            $currency = Commerce::getInstance()->getCurrencies()->getCurrencyByIso(strtoupper($data['currency']));

            if (!$currency) {
                Craft::warning('Unsupported currency - ' . $data['currency'], 'paymongo');
                continue;
            }

            $data['created'] = isset($data['date']) && $data['date'] ? $data['date'] : $data['created'];
            $payments[$data['created']] = $this->createSubscriptionPayment($data, $currency);
        }

        // Sort them by time invoiced, not the time they were saved to DB
        krsort($payments);

        return $payments;
    }
    
    /**
     * @inheritdoc
     */
    public function refreshPaymentHistory(Subscription $subscription)
    {
         //TODO : cleanup
    }


    /**
     * @inheritdoc
     */
    public function getSubscriptionPlanByReference(string $reference): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPlans(): array
    {
         return [];
    }

    /**
     * @inheritdoc
     */
    public function getSwitchPlansFormHtml(PlanInterface $originalPlan, PlanInterface $targetPlan): string
    {   
         //TODO : cleanup
    }


    /**
     * @inheritdoc
     */
    public function getSwitchPlansFormModel(): SwitchPlansForm
    {
        return new SwitchPlans();
    }

    /**
     * @inheritdoc
     */
    public function reactivateSubscription(Subscription $subscription): SubscriptionResponseInterface
    {
         //TODO : cleanup
    }

    /**
     * @inheritdoc
     */
    public function supportsPlanSwitch(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsReactivation(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function switchSubscriptionPlan(Subscription $subscription, BasePlan $plan, SwitchPlansForm $parameters): SubscriptionResponseInterface
    {
         //TODO : cleanup
    }

    /**
     * Preview a subscription plan switch cost for a subscription.
     *
     * @param Subscription $subscription
     * @param BasePlan $plan
     * @return float
     */
    public function previewSwitchCost(Subscription $subscription, BasePlan $plan): float
    {
        //TODO : cleanup
    }

    /**
     * @inheritdoc
     */
    public function handleWebhook(array $data)
    {
        switch ($data['type']) {
            case 'plan.deleted':
            case 'plan.updated':
                $this->handlePlanEvent($data);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoiceSucceededEvent($data);
                break;
            case 'invoice.created':
                $this->handleInvoiceCreated($data);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionExpired($data);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($data);
                break;
        }

        parent::handleWebhook($data);
    }

    // Protected methods
    // =========================================================================

    /**
     * Create a subscription payment model from invoice.
     *
     * @param array $data
     * @param Currency $currency the currency used for payment
     *
     * @return SubscriptionPayment
     */
    protected function createSubscriptionPayment(array $data, Currency $currency): SubscriptionPayment
    {
        //TODO : cleanup
    }

    /**
     * Create a Subscription Response object from an ApiResponse object.
     *
     * @param ApiResource $resource
     *
     * @return SubscriptionResponseInterface
     */
    protected function createSubscriptionResponse(ApiResource $resource): SubscriptionResponseInterface
    {
        $data = $resource->jsonSerialize();

        return new SubscriptionResponse($data);
    }

    /**
     * Handle a created invoice.
     *
     * @param array $data
     */
    protected function handleInvoiceCreated(array $data)
    {
        //TODO : cleanup
    }

    /**
     * Handle a successful invoice payment event.
     *
     * @param array $data
     * @throws \Throwable if something went wrong when processing the invoice
     */
    protected function handleInvoiceSucceededEvent(array $data)
    {
         //TODO : cleanup
    }

    /**
     * Handle Plan events
     *
     * @param array $data
     * @throws \yii\base\InvalidConfigException If plan not available
     */
    protected function handlePlanEvent(array $data)
    {
         //TODO : cleanup
    }

    /**
     * Handle an expired subscription.
     *
     * @param array $data
     *
     * @throws \Throwable
     */
    protected function handleSubscriptionExpired(array $data)
    {
         //TODO : cleanup
    }
}
