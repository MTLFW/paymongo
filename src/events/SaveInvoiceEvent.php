<?php
/**
 * @link https://craftcms.com/
 * @license MIT
 */

namespace craft\commerce\paymongo\events;

use craft\commerce\paymongo\models\Invoice;
use craft\events\CancelableEvent;

/**
 * Class SaveInvoiceEvent
 * @since 1.0
 */
class SaveInvoiceEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Invoice The invoice being saved.
     */
    public $invoice;
}
