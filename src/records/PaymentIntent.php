<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\records;

use craft\commerce\records\Gateway;
use craft\commerce\records\Order;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Payment source record.
 *
 * @property int $id
 * @property int $customerId
 * @property int $gatewayId
 * @property int $orderId
 * @property string $reference
 * @property string $intentData
 * @property Gateway $gateway
 *
 * @since 2.0
 */
class PaymentIntent extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%paymongo_paymentintents}}';
    }

    /**
     * Return the payment intent's gateway
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGateway(): ActiveQueryInterface
    {
        return $this->hasOne(Gateway::class, ['gatewayId' => 'id']);
    }

    /**
     * Return the payment intent's order
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getorder(): ActiveQueryInterface
    {
        return $this->hasOne(Order::class, ['gatewayId' => 'id']);
    }

    /**
     * Return the payment intent's PayMongo customer
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCustomer(): ActiveQueryInterface
    {
        return $this->hasOne(Customer::class, ['customerId' => 'id']);
    }

}
