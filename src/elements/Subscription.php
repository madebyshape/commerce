<?php

namespace craft\commerce\elements;

use Craft;
use craft\commerce\base\Element;
use craft\commerce\base\Plan;
use craft\commerce\base\PlanInterface;
use craft\commerce\base\SubscriptionGatewayInterface;
use craft\commerce\elements\db\SubscriptionQuery;
use craft\commerce\models\subscriptions\SubscriptionPayment;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Subscription as SubscriptionRecord;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use DateInterval;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Class Subscription
 *
 * @property bool $isOnTrial whether the subscription is still on trial
 * @property DateTime $trialExpires datetime of trial expiry
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @since 1.0
 */
class Subscription extends Element
{
    // Constants
    // =========================================================================

    /**
     * @var string
     */
    const STATUS_ACTIVE = 'live';

    /**
     * @var string
     */
    const STATUS_EXPIRED = 'expired';

    /**
     * @var string
     */
    const STATUS_CANCELED = 'canceled';

    /**
     * @var string
     */
    const STATUS_TRIAL = 'trial';

    // Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int User id
     */
    public $userId;

    /**
     * @var int Plan id
     */
    public $planId;

    /**
     * @var int Gateway id
     */
    public $gatewayId;

    /**
     * @var int|null Order id
     */
    public $orderId;

    /**
     * @var string Subscription reference on the gateway
     */
    public $reference;

    /**
     * @var int Trial days granted
     */
    public $trialDays;

    /**
     * @var DateTime Date of next payment
     */
    public $nextPaymentDate;

    /**
     * @var string The subscription data from gateway
     */
    public $subscriptionData;

    /**
     * @var bool Whether the subscription is canceled
     */
    public $isCanceled;

    /**
     * @var DateTime Time when subscription was canceled
     */
    public $dateCanceled;

    /**
     * @var bool Whether the subscription has expired
     */
    public $isExpired;

    /**
     * @var DateTime Time when subscription expired
     */
    public $dateExpired;

    /**
     * @var SubscriptionGatewayInterface
     */
    private $_gateway;

    /**
     * @var Plan
     */
    private $_plan;

    /**
     * @var User
     */
    private $_user;

    /**
     * @var Order
     */
    private $_order;

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function __toString()
    {
        return Craft::t('commerce', 'Subscription to “{plan}”', ['plan' => (string)$this->getPlan()]);
    }

    /**
     * Whether this subscription can be reactivated.
     *
     * @return bool
     * @throws InvalidConfigException if gateway misconfigured
     */
    public function canReactivate()
    {
        return $this->isCanceled && !$this->isExpired && $this->getGateway()->supportsReactivation();
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return Craft::$app->getFields()->getLayoutByType(static::class);
    }

    /**
     * Whether this subscription is on trial.
     *
     * @return bool
     */
    public function getIsOnTrial()
    {
        return $this->trialDays > 0 && time() > $this->getTrialExpires()->getTimestamp();
    }

    /**
     * Return the subscription plan for this subscription
     *
     * @return PlanInterface
     */
    public function getPlan(): PlanInterface
    {
        if (null === $this->_plan) {
            $this->_plan = Commerce::getInstance()->getPlans()->getPlanById($this->planId);
        }

        return $this->_plan;
    }

    /**
     * Return the User that is subscribed.
     *
     * @return User
     */
    public function getSubscriber(): User
    {
        if (null === $this->_user) {
            $this->_user = Craft::$app->getUsers()->getUserById($this->userId);
        }

        return $this->_user;
    }

    /**
     * Return the datetime of trial expiry.
     *
     * @return DateTime
     */
    public function getTrialExpires(): DateTIme
    {
        return (clone $this->dateCreated)->add(new DateInterval('P'.$this->trialDays.'D'));
    }

    /**
     * Return the next payment amount with currency code as a string.
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getNextPaymentAmount(): string
    {
        return $this->getGateway()->getNextPaymentAmount($this);
    }

    /**
     * Return the order that included this subscription, if any.
     *
     * @return null|Order
     */
    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

    /**
     * Return the product type for the product tied to the license.
     *
     * @return SubscriptionGatewayInterface
     * @throws InvalidConfigException if gateway misconfigured
     */
    public function getGateway(): SubscriptionGatewayInterface
    {
        if (null === $this->_gateway) {
            $this->_gateway = Commerce::getInstance()->getGateways()->getGatewayById($this->gatewayId);
            if (!$this->_gateway instanceof SubscriptionGatewayInterface) {
                throw new InvalidConfigException('The gateway set for subscription does not support subsriptions.');
            }
        }

        return $this->_gateway;
    }

    /**
     * @return string
     */
    public function getPlanName(): string
    {
        return (string)$this->getPlan();
    }

    /**
     * Return possible alternative plans for this subscription
     *
     * @return Plan[]
     */
    public function getAlternativePlans(): array
    {
        $plans = Commerce::getInstance()->getPlans()->getAllGatewayPlans($this->gatewayId);

        /** @var Plan $currentPlan */
        $currentPlan = $this->getPlan();

        $alternativePlans = [];

        foreach ($plans as $plan) {
            // For all plans that are not the current plan
            if ($plan->id !== $currentPlan->id && $plan->canSwitchFrom($currentPlan)) {
                $alternativePlans[] = $plan;
            }
        }

        return $alternativePlans;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('commerce/subscriptions/'.$this->id);
    }

    /**
     * Get the link for editing the order that purchased this license.
     *
     * @return string
     */
    public function getOrderEditUrl(): string
    {
        if ($this->orderId) {
            return UrlHelper::cpUrl('commerce/orders/'.$this->orderId);
        }

        return '';
    }

    /**
     * Return an array of all payments for this subscription.
     *
     * @return SubscriptionPayment[]
     * @throws InvalidConfigException
     */
    public function getAllPayments(): array
    {
        return $this->getGateway()->getSubscriptionPayments($this);
    }

    /**
     * @return null|string
     */
    public function getName()
    {

        return Craft::t('commerce', 'Subscription for {plan}', ['plan' => $this->getPlanName()]);
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if ($this->isExpired) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isCanceled) {
            return self::STATUS_CANCELED;
        }

        if ($this->isOnTrial) {
            return self::STATUS_TRIAL;
        }

        return self::STATUS_ACTIVE;
    }


    /**
     * @inheritdoc
     */
    public static function defineSources(string $context = null): array
    {
        $plans = Commerce::getInstance()->getPlans()->getAllPlans();

        $planIds = [];

        foreach ($plans as $plan) {
            $planIds[] = $plan->id;
        }


        $sources = [
            '*' => [
                'key' => '*',
                'label' => Craft::t('commerce', 'All subscriptions'),
                'criteria' => ['planId' => $planIds],
                'defaultSort' => ['dateCreated', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('commerce', 'Subscription plans')];

        foreach ($plans as $plan) {
            $key = 'plan:'.$plan->id;

            $sources[$key] = [
                'key' => $key,
                'label' => $plan->name,
                'data' => [
                    'handle' => $plan->handle
                ],
                'criteria' => ['planId' => $plan->id]
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        if ($handle === 'subscriber') {
            $map = (new Query())
                ->select('id as source, userId as target')
                ->from('{{%commerce_subscriptions}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'order') {
            $this->_order = $elements[0] ?? null;

            return;
        }

        if ($handle === 'subscriber') {
            $this->_user = $elements[0] ?? null;

            return;
        }

        parent::setEagerLoadedElements($handle, $elements);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['userId', 'planId', 'gatewayId', 'reference', 'subscriptionData'], 'required'];

        return $rules;
    }

    /**
     * @inheritdocs
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => Craft::t('commerce', 'Active'),
            self::STATUS_EXPIRED => Craft::t('commerce', 'Expired'),
            self::STATUS_CANCELED => ['label' => Craft::t('commerce', 'Canceled'), 'color' => 'yellow'],
            self::STATUS_TRIAL => ['label' => Craft::t('commerce', 'Trial'), 'color' => 'blue'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'nextPaymentDate';
        $attributes[] = 'dateExpired';
        $attributes[] = 'dateCanceled';
        return $attributes;
    }

    /**
     * @inheritdoc
     * @return SubscriptionQuery The newly created [[SubscriptionQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new SubscriptionQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $subscriptionRecord = SubscriptionRecord::findOne($this->id);

            if (!$subscriptionRecord) {
                throw new InvalidConfigException('Invalid subscription id: '.$this->id);
            }
        } else {
            $subscriptionRecord = new SubscriptionRecord();
            $subscriptionRecord->id = $this->id;
        }

        $subscriptionRecord->planId = $this->planId;
        $subscriptionRecord->nextPaymentDate = $this->nextPaymentDate;
        $subscriptionRecord->subscriptionData = $this->subscriptionData;
        $subscriptionRecord->isCanceled = $this->isCanceled;
        $subscriptionRecord->dateCanceled = $this->dateCanceled;
        $subscriptionRecord->isExpired = $this->isExpired;
        $subscriptionRecord->dateExpired = $this->dateExpired;

        // Some properties of the license are immutable
        if ($isNew) {
            $subscriptionRecord->gatewayId = $this->gatewayId;
            $subscriptionRecord->orderId = $this->orderId;
            $subscriptionRecord->reference = $this->reference;
            $subscriptionRecord->trialDays = $this->trialDays;
            $subscriptionRecord->userId = $this->userId;
        }

        $subscriptionRecord->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('commerce', 'Subscription plan')],
            'subscriber' => ['label' => Craft::t('commerce', 'Subscribing user')],
            'reference' => ['label' => Craft::t('commerce', 'Subscription reference')],
            'dateCanceled' => ['label' => Craft::t('commerce', 'Cancellation date')],
            'dateExpired' => ['label' => Craft::t('commerce', 'Expiry date')],
            'trialExpires' => ['label' => Craft::t('commerce', 'Trial expiry date')]
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        $attributes[] = 'subscriber';
        $attributes[] = 'orderLink';
        $attributes[] = 'orderLink';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['subscriber', 'plan'];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'plan':
                return $this->getPlanName();

            case 'subscriber':
                return $this->getSubscriber();

            case 'orderLink':
                $url = $this->getOrderEditUrl();

                return $url ? '<a href="'.$url.'">'.Craft::t('commerce', 'View order').'</a>' : '';

            default:
                {
                    return parent::tableAttributeHtml($attribute);
                }
        }
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'commerce_subscriptions.dateCreated' => Craft::t('commerce', 'Subscription date'),
        ];
    }


    // Protected methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        /** @var ElementQuery $elementQuery */
        if ($attribute === 'subscriber') {
            $with = $elementQuery->with ?: [];
            $with[] = 'subscriber';
            $elementQuery->with = $with;
            return;
        }

        if ($attribute === 'orderLink') {
            $with = $elementQuery->with ?: [];
            $with[] = 'order';
            $elementQuery->with = $with;
            return;
        }

        parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
    }
}
