<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\web\assets\plansettings;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Asset bundle for editing Craft subscription plans
 */
class PlanSettingsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;

        $this->js = [
            'js/planSettings.js',
        ];

        parent::init();
    }
}
