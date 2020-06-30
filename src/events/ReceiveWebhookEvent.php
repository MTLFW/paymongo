<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use yii\base\Event;

/**
 * Class ReceiveWebhookEvent
 *
 * @since 1.0
 */
class ReceiveWebhookEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The webhook data
     */
    public $webhookData;
}
