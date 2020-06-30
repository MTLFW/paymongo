<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models\forms\payment;

use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\paymongo\Plugin;

/**
 * Charge Payment form model.
 *
 * @since 2.0
 */
class PaymentIntent extends CreditCardPaymentForm
{
    /**
     * @var string $customer the PayMongo customer token.
     */
    public $customer;

    /**
     * @var string $customer the PayMongo payment method id.
     */
    public $paymentMethodId;


    // Public methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [[['paymentMethodId'], 'required']];
    }

    /**
     * @inheritdoc
     */
    public function populateFromPaymentSource(PaymentSource $paymentSource)
    {
        $this->paymentMethodId = $paymentSource->token;

        $customer = Plugin::getInstance()->getCustomers()->getCustomer($paymentSource->gatewayId, $paymentSource->getUser());
        $this->customer = $customer->reference;
    }
}
