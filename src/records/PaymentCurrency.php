<?php

namespace craft\commerce\records;

use craft\db\ActiveRecord;

/**
 * Currency record.
 *
 * @property int $id
 * @property string $iso
 * @property bool $primary
 * @property float $rate
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PaymentCurrency extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%commerce_paymentcurrencies}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['iso'], 'unique']
        ];
    }
}
