<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models\forms;

use craft\commerce\models\subscriptions\SwitchPlansForm;

/**
 * Switch Plans form model.
 *
 * @since 1.0
 */
class SwitchPlans extends SwitchPlansForm
{
    /**
     * Whether plan change should be prorated
     *
     * @var bool
     */
    public $prorate = false;

    /**
     * @var bool Whether the plan change should be billed immediately.
     */
    public $billImmediately = false;

    /**
     * @var bool The billing cycle anchor. Can be set to `now` or `unchanged` (default).
     */
    public $billingCycleAnchor;

    /**
     * @var int Timestamp on which to base the proration calculation
     */
    public $prorationDate;
}
