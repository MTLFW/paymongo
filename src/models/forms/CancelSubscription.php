<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models\forms;

use craft\commerce\models\subscriptions\CancelSubscriptionForm as BaseCancelSubscriptionForm;

/**
 * PayMongo cancel subscription form model.
 *
 * @since 1.0
 */
class CancelSubscription extends BaseCancelSubscriptionForm
{
    /**
     * @var bool whether the subscription should be canceled immediately
     */
    public $cancelImmediately = false;
}
