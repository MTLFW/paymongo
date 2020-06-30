<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use craft\commerce\models\Transaction;
use yii\base\Event;

/**
 * Class BuildGatewayRequestEvent
 *
 */
class BuildGatewayRequestEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The metadata of the gateway request
     * @deprecated in 1.1 Use [[request]] instead.
     */
    public $metadata;

    /**
     * @var Transaction The transaction being used as the base for request
     */
    public $transaction;

    /**
     * @var array The request being used
     */
    public $request;
}
