<?php

namespace craft\commerce\records;

use craft\db\ActiveRecord;
use craft\elements\Category;
use yii\db\ActiveQueryInterface;

/**
 * Discount Product type record.
 *
 * @property ActiveQueryInterface $category
 * @property int $categoryId
 * @property ActiveQueryInterface $discount
 * @property int $discountId
 * @property int $id
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class DiscountCategory extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%commerce_discount_categories}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['discountId', 'categoryId'], 'unique', 'targetAttribute' => ['discountId', 'categoryId']]
        ];
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getDiscount(): ActiveQueryInterface
    {
        return $this->hasOne(Discount::class, ['id' => 'discountId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getCategory(): ActiveQueryInterface
    {
        return $this->hasOne(Category::class, ['id' => 'categoryId']);
    }
}
