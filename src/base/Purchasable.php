<?php

namespace craft\commerce\base;

use craft\commerce\helpers\Currency;
use craft\commerce\models\LineItem;
use craft\commerce\models\Sale;
use craft\commerce\Plugin;
use craft\commerce\records\Purchasable as PurchasableRecord;

/**
 * Base Purchasable
 *
 * @property string $description the element's title or any additional descriptive information
 * @property bool $isAvailable whether the purchasable is currently available for purchase
 * @property bool $isPromotable whether this purchasable can be subject to discounts or sales
 * @property int $purchasableId the ID of the Purchasable element that will be be added to the line item
 * @property float $price the base price the item will be added to the line item with
 * @property null|float $salePrice the base price the item will be added to the line item with
 * @property null|array|Sale[] $sales sales models which are currently affecting the salePrice of this purchasable
 * @property int $shippingCategoryId the purchasable's shipping category ID
 * @property string $sku a unique code as per the commerce_purchasables table
 * @property array $snapshot
 * @property int $taxCategoryId the purchasable's tax category ID
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
abstract class Purchasable extends Element implements PurchasableInterface
{
    /**
     * @var float|null
     */
    private $_salePrice;

    /**
     * @var Sale[]|null
     */
    private $_sales;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }


    /**
     * @inheritdoc
     */
    public function getPurchasableId(): int
    {
        throw new NotImplementedException('Purchasable needs a purchasable ID');
    }

    /**
     * @inheritdoc
     */
    public function getSnapshot(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getSalePrice(): float
    {
        $this->_loadSales();

        return $this->_salePrice;
    }

    /**
     * @param float|null $value
     */
    public function setSalePrice(float $value = null)
    {
        $this->_salePrice = $value;
    }

    /**
     * An array of sales models which are currently affecting the salePrice of this purchasable.
     *
     * @return Sale[]|null
     */
    public function getSales()
    {
        $this->_loadSales();

        return $this->_sales;
    }

    /**
     * sets an array of sales models which are currently affecting the salePrice of this purchasable.
     *
     * @param Sale[] $sales
     */
    public function setSales(array $sales)
    {
        $this->_sales = $sales;
    }

    /**
     * @inheritdoc
     */
    public function getTaxCategoryId(): int
    {
        return Plugin::getInstance()->getTaxCategories()->getDefaultTaxCategory()->id;
    }

    /**
     * @inheritdoc
     */
    public function getShippingCategoryId(): int
    {
        return Plugin::getInstance()->getShippingCategories()->getDefaultShippingCategory()->id;
    }

    /**
     * @inheritdoc
     */
    public function getIsAvailable(): bool
    {
        // remove the item from the cart if the product is not enabled
        return $this->getStatus() === Element::STATUS_ENABLED;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return (string)$this;
    }

    /**
     * @inheritdoc
     */
    public function populateLineItem(LineItem $lineItem)
    {
    }

    /**
     * @inheritdoc
     */
    public function getLineItemRules(LineItem $lineItem): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function hasFreeShipping(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getIsPromotable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPromotionRelationSource()
    {
        return $this->id;
    }

    /**
     * Update purchasable table
     *
     * @param bool $isNew
     */
    public function afterSave(bool $isNew)
    {
        $purchasable = PurchasableRecord::findOne($this->id) ?? new PurchasableRecord();

        $purchasable->sku = $this->getSku();
        $purchasable->price = $this->getPrice();
        $purchasable->id = $this->id;

        $purchasable->save(false);

        parent::afterSave($isNew);
    }

    /**
     * Clean up purchasable table
     */
    public function afterDelete()
    {
        $purchasable = PurchasableRecord::findOne($this->id);

        if ($purchasable) {
            $purchasable->delete();
        }

        parent::afterDelete();
    }

    /**
     * Reloads any sales applicable to the purchasable for the current user.
     */
    private function _loadSales()
    {
        if (null === $this->_sales) {
            // Default the sales and salePrice to the original price without any sales
            $this->_sales = [];
            $this->_salePrice = Currency::round($this->getPrice());

            if ($this->getPurchasableId() && $this->getIsPromotable()) {
                $this->_sales = Plugin::getInstance()->getSales()->getSalesForPurchasable($this);
                $this->_salePrice = Plugin::getInstance()->getSales()->getSalePriceForPurchasable($this);
            }
        }
    }
}
