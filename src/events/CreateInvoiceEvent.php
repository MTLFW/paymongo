<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use yii\base\Event;

/**
 * Class CreateInvoiceEvent
 *
 */
class CreateInvoiceEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The invoice data.
     */
    public $invoiceData;
}
