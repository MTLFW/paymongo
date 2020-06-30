<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\records;

use craft\commerce\records\Subscription;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Invoice record.
 *
 * @property int $id
 * @property string $reference
 * @property int $subscriptionId
 * @property string $invoiceData
 *
 * @since 2.0
 */
class Invoice extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%paymongo_invoices}}';
    }

    /**
     * Return the subscription
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSubscription(): ActiveQueryInterface
    {
        return $this->hasOne(Subscription::class, ['subscriptionId' => 'id']);
    }
}
