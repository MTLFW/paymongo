<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use craft\commerce\models\Transaction;
use yii\base\Event;

/**
 * Class ReceiveWebhookEvent
 *
 * @since 1.0
 * @deprecated since 2.0
 */
class Receive3dsPaymentEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Transaction The successful transaction
     */
    public $transaction;
}
