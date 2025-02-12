<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftcommercetests\unit\services;

use Codeception\Test\Unit;
use craft\commerce\elements\Order;
use craft\commerce\models\Discount;
use craft\commerce\Plugin;
use craft\commerce\records\Discount as DiscountRecord;
use craft\commerce\services\Customers;
use craft\commerce\services\Discounts;
use craft\elements\User;
use UnitTester;

/**
 * UserGroupConditionDiscountTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 2.1
 */
class UserGroupConditionDiscountTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     *
     */
    protected function _before()
    {
        parent::_before();
        $this->discounts = Plugin::getInstance()->getDiscounts();
    }

    public function testIsUserGroupsConditionAnyOrNoneValid()
    {
        $this->_mockCustomers();

        $mockDiscount = $this->_getMockDiscount([3, 4]);

        $discountAdjuster = new Discounts();

        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_ANY_OR_NONE;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertTrue($isValid);
    }

    public function testIsUserGroupsConditionIncludeAllValid()
    {
        $discountAdjuster = new Discounts();
        $this->_mockCustomers();

        $mockDiscount = $this->_getMockDiscount([3, 4]);


        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_INCLUDE_ALL;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertFalse($isValid);

        $mockDiscount = $this->_getMockDiscount([2, 3]);

        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_INCLUDE_ALL;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertFalse($isValid);

        $this->_mockCustomers([2]);
        $mockDiscount = $this->_getMockDiscount([2, 1]);

        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_INCLUDE_ALL;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertFalse($isValid);
    }

    public function testUserGroupsConditionIncludeAnyValid()
    {
        $this->_mockCustomers();

        $mockDiscount = $this->_getMockDiscount([2, 3]);

        $discountAdjuster = new Discounts();

        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_INCLUDE_ANY;

        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertTrue($isValid);

        $mockDiscount = $this->_getMockDiscount([3, 4]);
        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_INCLUDE_ANY;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertFalse($isValid);
    }

    public function testIsUserGroupsConditionExcludeValid()
    {
        $discountAdjuster = new Discounts();
        $this->_mockCustomers();

        $mockDiscount = $this->_getMockDiscount([3, 4]);
        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_EXCLUDE;


        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertTrue($isValid);

        $mockDiscount = $this->_getMockDiscount([2, 4]);
        $mockDiscount->userGroupsCondition = DiscountRecord::CONDITION_USER_GROUPS_EXCLUDE;
        $isValid = $discountAdjuster->isDiscountUserGroupValid($mockDiscount, new User());
        self::assertFalse($isValid);
    }

    public function _mockCustomers(array $ids = [1, 2])
    {
        $mockCustomers = $this->make(Customers::class, [
            'getUserGroupIdsForUser' => $ids,
        ]);

        Plugin::getInstance()->set('customers', $mockCustomers);
    }

    public function _getMockDiscount(array $ids)
    {
        /** @var Discount $mockDiscount */
        return $this->make(Discount::class, [
            'getUserGroupIds' => $ids,
        ]);
    }
}
