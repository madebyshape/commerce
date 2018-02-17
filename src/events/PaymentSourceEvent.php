<?php

namespace craft\commerce\events;

use craft\commerce\models\PaymentSource;
use craft\events\CancelableEvent;

/**
 * Class PaymentSourceEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PaymentSourceEvent extends CancelableEvent
{
    // Properties
    // ==========================================================================

    /**
     * @var PaymentSource Payment source
     */
    public $paymentSource;
}
