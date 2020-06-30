<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\services;

use Craft;
use craft\commerce\paymongo\models\Customer;
use craft\commerce\paymongo\models\PaymentIntent;
use craft\commerce\paymongo\records\PaymentIntent as PaymentIntentRecord;
use craft\db\Query;
use yii\base\Component;
use yii\base\Exception;

/**
 * Customer service.
 *
 * @since 2.0
 */
class PaymentIntents extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a payment intent by gateway, order id and PayMongo plugin customer id
     *
     * @param int $gatewayId The PayMongo gateway
     *
     * @return PaymentIntent|null
     */
    public function getPaymentIntent(int $gatewayId, $orderId, $customerId)
    {
        $result = $this->_createIntentQuery()
            ->where(['orderId' => $orderId, 'gatewayId' => $gatewayId, 'customerId' => $customerId])
            ->one();

        if ($result !== null) {
            return new PaymentIntent($result);
        }

       return null;
    }

    /**
     * Returns a payment intent by its reference
     *
     * @param string $reference
     *
     * @return PaymentIntent|null
     */
    public function getPaymentIntentByReference(string $reference) {
        $result = $this->_createIntentQuery()
            ->where(['reference' => $reference])
            ->one();

        if ($result !== null) {
            return new PaymentIntent($result);
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
    public function savePaymentIntent(PaymentIntent $paymentIntent): bool
    {  
        $paymentID = NULL;
        if(!isset($paymentIntent->id)){
            $record = $this->_createIntentQuery()
            ->where(['customerId' => $paymentIntent->customerId])
            ->andWhere(['gatewayId' => $paymentIntent->gatewayId])
            ->andWhere(['orderId' => $paymentIntent->orderId])
            ->one();
            if ($record) {
                $paymentID = $record['id'];
            }           
        }else{
            $paymentID = $paymentIntent->id;
        }

        if ($paymentID != NULL) {
            $record = PaymentIntentRecord::findOne($paymentID);
           
            if (!$record) {
                throw new Exception(Craft::t('commerce-paymongo', 'No customer exists with the ID “{id}”',
                    ['id' => $paymentID]));
            }
        } else {
            $record = new PaymentIntentRecord();
        }
      
        $record->reference = $paymentIntent->reference;
        $record->gatewayId = $paymentIntent->gatewayId;
        $record->customerId = $paymentIntent->customerId;
        $record->orderId = $paymentIntent->orderId;
        $record->intentData = $paymentIntent->intentData;
      
        $paymentIntent->validate();
      
        if (!$paymentIntent->hasErrors()) {
            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $paymentIntent->id = $record->id;

            return true;
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
    private function _createIntentQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'gatewayId',
                'customerId',
                'reference',
                'orderId',
                'intentData',
            ])
            ->from(['{{%paymongo_paymentintents}}']);
    }

}
