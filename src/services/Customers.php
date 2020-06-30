<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\services;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\commerce\paymongo\gateways\Gateway;
use craft\commerce\paymongo\Plugin as PaymongoPlugin;
use craft\commerce\paymongo\errors\CustomerException;
use craft\commerce\paymongo\models\Customer;
use craft\commerce\paymongo\records\Customer as CustomerRecord;
use craft\db\Query;
use craft\elements\User;
use yii\base\Component;
use yii\base\Exception;

/**
 * Customer service.
 *
 * @since 2.0
 */
class Customers extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a customer by gateway and user
     *
     * @param int $gatewayId The PayMongo gateway
     * @param User $user The user
     *
     * @return Customer
     * @throws CustomerException
     */
    public function getCustomer(int $gatewayId, User $user): Customer
    {
      
        $result = $this->_createCustomerQuery()
            ->where(['userId' => $user->id, 'gatewayId' => $gatewayId])
            ->one();
         
        if ($result !== null) {
            return new Customer($result);
        }
               
        $customer = new Customer([
            'userId' => $user->id,
            'gatewayId' => $gatewayId,
            'reference' => $user->id,
            'response' => '',
        ]);

        if (!$this->saveCustomer($customer)) {
            throw new CustomerException('Could not save customer: ' . implode(', ', $customer->getErrorSummary(true)));
        }

        return $customer;
    }

    /**
     * Return a customer by its id.
     *
     * @param int $id
     *
     * @return Customer|null
     */
    public function getCustomerById(int $id) {
        $customerRow = $this->_createCustomerQuery()
            ->where(['id' => $id])
            ->one();

        if ($customerRow) {
            return new Customer($customerRow);
        }

        return null;
    }

    /**
     * Return a customer by its reference.
     *
     * @param string $reference
     *
     * @return Customer|null
     */
    public function getCustomerByReference(string $reference) {
        $customerRow = $this->_createCustomerQuery()
            ->where(['reference' => $reference])
            ->one();

        if ($customerRow) {
            return new Customer($customerRow);
        }

        return null;
    }

    /**
     * Save a customer
     *
     * @param Customer $customer The customer being saved.
     * @return bool Whether the payment source was saved successfully
     * @throws Exception if payment source not found by id.
     */
    public function saveCustomer(Customer $customer): bool
    { 
        if ($customer->id) {
            $record = CustomerRecord::findOne($customer->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce-paymongo', 'No customer exists with the ID “{id}”',
                    ['id' => $customer->id]));
            }
        } else {
            $record = new CustomerRecord();
        }

        $record->userId = $customer->userId;
        $record->gatewayId = $customer->gatewayId;
        $record->reference = $customer->reference;
        $record->response = $customer->response;

        $customer->validate();

        if (!$customer->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $customer->id = $record->id;

            return true;
        }

        return false;
    }

    /**
     * Delete a customer by it's id.
     *
     * @param int $id The id
     *
     * @return bool
     * @throws \Throwable in case something went wrong when deleting.
     */
    public function deleteCustomerById($id): bool
    {
        $record = CustomerRecord::findOne($id);

        if ($record) {
            return (bool)$record->delete();
        }

        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving customers.
     *
     * @return Query The query object.
     */
    private function _createCustomerQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'gatewayId',
                'userId',
                'reference',
                'response',
            ])
            ->from(['{{%paymongo_customers}}']);
    }

}
