<?php

namespace craft\commerce\base;

/**
 * Interface ShippingMethod
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
interface ShippingMethodInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the type of Shipping Method. This might be the name of the plugin or provider.
     * The core shipping methods have type: `Custom`. This is shown in the control panel only.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Returns the ID of this Shipping Method, if it is managed by Craft Commerce.
     *
     * @return int|null The shipping method ID, or null if it is not managed by Craft Commerce
     */
    public function getId();

    /**
     * Returns the name of this Shipping Method as displayed to the customer and in the control panel.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the unique handle of this Shipping Method.
     *
     * @return string
     */
    public function getHandle(): string;

    /**
     * Returns the control panel URL to manage this method and its rules.
     * An empty string will result in no link.
     *
     * @return string
     */
    public function getCpEditUrl(): string;

    /**
     * Returns an array of rules that meet the `ShippingRules` interface.
     *
     * @return ShippingRuleInterface[] The array of ShippingRules
     */
    public function getShippingRules(): array;

    /**
     * Returns whether this shipping method is enabled for listing and selection by customers.
     *
     * @return bool
     */
    public function getIsEnabled(): bool;
}
