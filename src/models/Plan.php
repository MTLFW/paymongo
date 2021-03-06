<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\models;

use craft\commerce\base\Plan as BasePlan;
use craft\commerce\base\PlanInterface;
use craft\helpers\Json;

/**
 * PayMongo Payment form model.
 *
 * @since 1.0
 */
class Plan extends BasePlan
{
    /**
     * @inheritdoc
     */
    public function canSwitchFrom(PlanInterface $currentPlant): bool
    {
        /** @var BasePlan $currentPlant */
        return $currentPlant->gatewayId === $this->gatewayId;
    }

    /**
     * Returns true if this plan is on the same payment cycle as another plan.
     *
     * @param Plan $plan
     * @return bool
     */
    public function isOnSamePaymentCycleAs(Plan $plan): bool
    {
        $thisPlanData = Json::decode($this->planData);
        $otherPlanData = Json::decode($plan->planData);

        return $thisPlanData['plan']['interval'] === $otherPlanData['plan']['interval'] && $thisPlanData['plan']['interval_count'] === $otherPlanData['plan']['interval_count'];
    }
}
