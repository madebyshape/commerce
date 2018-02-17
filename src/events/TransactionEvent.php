<?php

namespace craft\commerce\events;

use craft\commerce\models\Transaction;
use yii\base\Event;

/**
 * Class TransactionEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class TransactionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Transaction The transaction model
     */
    public $transaction;
}
