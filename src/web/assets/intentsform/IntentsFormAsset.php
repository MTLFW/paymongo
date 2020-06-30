<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\web\assets\intentsform;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Payment Form
 */
class IntentsFormAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;

        $this->css = [
            'css/paymentForm.css',
        ];

        $this->js = [
            'js/card.js',
            'js/paymentForm.js',
           
            
        ];
        parent::init();
    }
}
