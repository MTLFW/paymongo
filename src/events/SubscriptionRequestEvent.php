<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use yii\base\Event;

/**
 * Class SubscriptionRequestEvent
 * @since 1.0
 */
class SubscriptionRequestEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The subscription parameters
     */
    public $parameters;
}
