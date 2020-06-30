<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models\forms;

use craft\commerce\models\subscriptions\SubscriptionForm;

/**
 * Subscription form model.
 *
 * @since 2.2
 */
class Subscription extends SubscriptionForm
{
    /**
     * Timestamp for when the trial must end
     *
     * @var int
     */
    public $trialEnd;
}
