<?php

namespace craft\commerce\helpers;

use craft\commerce\models\Currency as CurrencyModel;
use craft\commerce\Plugin;

/**
 * Class Currency
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Currency
{
    // Public Methods
    // =========================================================================

    /**
     * Rounds the amount as per the currency minor unit information. Not passing
     * a currency model results in rounding in default currency.
     *
     * @param float $amount
     * @param CurrencyModel|null $currency
     * @return float
     */
    public static function round($amount, $currency = null): float
    {
        if (!$currency) {
            $defaultPaymentCurrency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrency();
            $currency = Plugin::getInstance()->getCurrencies()->getCurrencyByIso($defaultPaymentCurrency->iso);
        }

        $decimals = $currency->minorUnit;

        return round($amount, $decimals);
    }

    /**
     * @return int
     */
    public static function defaultDecimals(): int
    {
        $currency = Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

        $decimals = Plugin::getInstance()->getCurrencies()->getCurrencyByIso($currency)->minorUnit;

        return $decimals;
    }
}
