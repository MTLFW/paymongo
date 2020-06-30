<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\commerce\paymongo;

use craft\commerce\services\Gateways;
use craft\commerce\paymongo\gateways\Gateway;
use craft\commerce\paymongo\gateways\PaymentIntents;
use craft\commerce\paymongo\models\Settings;
use craft\commerce\paymongo\plugin\Services;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;


/**
 * Plugin represents the PayMongo integration plugin.
 *
 * @since 1.0
 */
class Plugin extends \craft\base\Plugin
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public $schemaVersion = '2.2.0';

    // Traits
    // =========================================================================

    use Services;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->_setPluginComponents();

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES, function(RegisterComponentTypesEvent $event) {
      
            $event->types[] = PaymentIntents::class;
        });
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
