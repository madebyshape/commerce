<?php

namespace craft\commerce\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Country record.
 *
 * @property int $id
 * @property string $iso
 * @property string $name
 * @property bool $stateRequired
 * @property State[] $states
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class Country extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%commerce_countries}}';
    }

    /**
     * Returns the country's states
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStates(): ActiveQueryInterface
    {
        return $this->hasMany(State::class, ['id' => 'countryId']);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['iso', 'name'], 'required'],
            [['iso'], 'string', 'length' => 2],
        ];
    }
}
