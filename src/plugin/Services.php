<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\plugin;

use craft\commerce\paymongo\services\Customers;
use craft\commerce\paymongo\services\Invoices;
use craft\commerce\paymongo\services\PaymentIntents;

/**
 * Trait Services
 *
 * @since 1.0
 */
trait Services
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the customers service
     *
     * @return Customers The customers service
     */
    public function getCustomers(): Customers
    {
        return $this->get('customers');
    }

    /**
     * Returns the invoices service
     *
     * @return Invoices The invoices service
     */
    public function getInvoices(): Invoices
    {
        return $this->get('invoices');
    }

    /**
     * Returns the payment intents service
     *
     * @return PaymentIntents The payment intents service
     */
    public function getPaymentIntents(): PaymentIntents
    {
        return $this->get('paymentIntents');
    }

    // Private Methods
    // =========================================================================

    /**
     * Set the components of the commerce plugin
     */
    private function _setPluginComponents()
    {
        $this->setComponents([
            'customers' => Customers::class,
            'paymentIntents' => PaymentIntents::class,
        ]);
    }
}
