<template>
    <div class="order-meta-container" :class="{'loading': recalculateLoading || saveLoading}" v-if="draft">
        <div id="settings" class="meta" v-if="editing">
            <div class="field" id="reference-field">
                <div class="heading">
                    <label id="reference-label"
                           for="reference">{{"Reference"|t('commerce')}}</label>
                </div>
                <div class="input ltr">
                    <input
                            class="text fullwidth"
                            type="text"
                            v-model="reference"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            :placeholder="$options.filters.t('Enter reference', 'commerce')"/>
                </div>
            </div>

            <field :label="$options.filters.t('Coupon Code', 'commerce')" :errors="getErrors('couponCode')[0]">
                <input
                        class="text fullwidth"
                        type="text"
                        v-model="couponCode"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        :placeholder="$options.filters.t('Enter coupon code', 'commerce')"/>
            </field>

            <field v-if="order.isCompleted" :label="$options.filters.t('Date Ordered', 'commerce')">
                <date-ordered-input :date="draft.order.dateOrdered" @update="updateDateOrderedInput"></date-ordered-input>
            </field>

            <field :label="$options.filters.t('Order Site', 'commerce')">
                <order-site
                    :originalOrderSiteId="originalDraft.order.orderSiteId"
                    :order="order"
                    @updateOrder="updateOrder"></order-site>
            </field>

            <div class="field" id="isCompleted-field"
                 v-if="!draft.order.isCompleted">
                <div class="heading">
                    <label id="isCompleted-label"
                           for="isCompleted">{{"Completed"|t('commerce')}}</label>
                </div>
                <div class="input ltr">
                    <div class="buttons">
                        <input type="button" class="btn small"
                               :value="$options.filters.t('Mark as completed', 'commerce')"
                               :class="{ disabled: !hasCustomer || recalculateLoading}"
                               :disabled="!hasCustomer || recalculateLoading"
                               @click="markAsCompleted"/>
                    </div>
                </div>
            </div>

            <template v-if="draft.order.isCompleted">
                <div class="field" id="orderStatus-field">
                    <div class="heading">
                        <label id="orderStatus-label" for="status">{{"Status"|t('commerce')}}</label>
                    </div>
                    <div class="input ltr">
                        <order-status
                                :originalOrderStatusId="originalDraft.order.orderStatusId"
                                :original-message="originalDraft.order.message"
                                :order="order"
                                @updateOrder="updateOrder"></order-status>
                    </div>
                </div>
            </template>

            <div class="field" id="shippingMethod-field">
                <div class="heading">
                    <label id="shippingMethod-label" for="slug">{{"Shipping Method"|t('commerce')}}</label>
                </div>
                <div class="input ltr">
                    <shipping-method :order="order"
                                     @updateOrder="updateOrder"></shipping-method>
                </div>
            </div>
        </div>

        <div class="order-meta meta read-only" v-if="!editing">
            <div class="data">
                <h5 class="heading">{{"Reference"|t('commerce')}}</h5>
                <p class="value">{{draft.order.reference}}</p>
            </div>

            <div class="data">
                <h5 class="heading">{{"Coupon Code"|t('commerce')}}</h5>
                <span class="value code">{{draft.order.couponCode}}</span>
            </div>

            <template v-if="draft.order.isCompleted">
                <div class="data">
                    <h5 class="heading">{{"Date Ordered"|t('commerce')}}</h5>
                    <span class="value">{{draft.order.dateOrdered.date}} {{draft.order.dateOrdered.time}}</span>
                </div>
            </template>

            <div class="data">
                <h5 class="heading">{{"Order Site"|t('commerce')}}</h5>
                <span class="value"
                      v-if="draft.order.orderSite">{{draft.order.orderSite.name}} ({{draft.order.orderSite.language}})</span>
            </div>

            <template v-if="draft.order.isCompleted">
                <div class="data">
                    <h5 class="heading">{{"Status"|t('commerce')}}</h5>
                    <span class="value"
                          v-html="draft.order.orderStatusHtml"></span>
                </div>
            </template>

            <div class="data order-meta-shipping-method">
                <h5 class="heading">{{"Shipping Method"|t('commerce')}}</h5>
                <div class="value" v-if="draft.order.shippingMethodHandle">
                    <span v-if="draft.order.shippingMethodName">{{draft.order.shippingMethodName}}</span>
                    <span class="small code shipping-method-handle">{{draft.order.shippingMethodHandle}}</span>
                </div>
            </div>
        </div>

        <div id="meta" class="meta read-only">
            <div class="data">
                <h5 class="heading">{{"ID"|t('commerce')}}</h5>
                <p class="value">{{draft.order.id}}</p>
            </div>

            <div class="data">
                <h5 class="heading">{{"Short Number"|t('commerce')}}</h5>
                <div class="value order-number-value">
                    <div>
                        {{draft.order.shortNumber}}
                    </div>
                    <div class="hidden-input">
                        <input type="text" ref="orderShortNumber" :value="draft.order.shortNumber" />
                    </div>
                    <btn-link @click="copy($refs.orderShortNumber)">{{"Copy"|t('commerce')}}</btn-link>
                </div>
            </div>

            <div class="data">
                <h5 class="heading">{{"Number"|t('commerce')}}</h5>
                <div class="value order-number-value">
                    <div>
                        {{draft.order.number}}
                    </div>
                    <div class="hidden-input">
                        <input type="text" ref="orderNumber" :value="draft.order.number" />
                    </div>
                    <btn-link @click="copy($refs.orderNumber)">{{"Copy"|t('commerce')}}</btn-link>
                </div>
            </div>

            <div class="data">
                <h5 class="heading">{{"Paid Status"|t('commerce')}}</h5>
                <span class="value"
                      v-html="draft.order.paidStatusHtml"></span>
            </div>

            <div class="data">
                <h5 class="heading">{{"Total Price"|t('commerce')}}</h5>
                <span class="value">{{draft.order.totalPriceAsCurrency}}</span>
            </div>

            <template v-if="draft.order.totalPaid != 0"> <!-- Show positive and negative numbers. -->
            <div class="data">
                <h5 class="heading">{{"Paid Amount"|t('commerce')}}</h5>
                <span class="value">{{draft.order.totalPaidAsCurrency}}</span>
            </div>
            </template>

            <template v-if="!draft.order.datePaid && draft.order.dateAuthorized">
                <div class="data">
                    <h5 class="heading">{{"Date Authorized"|t('commerce')}}</h5>
                    <span class="value">{{draft.order.dateAuthorized.date}} {{draft.order.dateAuthorized.time}}</span>
                </div>
            </template>

            <template v-if="draft.order.datePaid">
                <div class="data">
                    <h5 class="heading">{{"Date Paid"|t('commerce')}}</h5>
                    <span class="value">{{draft.order.datePaid.date}} {{draft.order.datePaid.time}}</span>
                </div>
            </template>

            <div class="data">
                <h5 class="heading">{{"Last Updated"|t('commerce')}}</h5>
                <span class="value">{{draft.order.dateUpdated.date}} {{draft.order.dateUpdated.time}}</span>
            </div>

            <div class="data">
                <h5 class="heading">{{"IP Address"|t('commerce')}}</h5>
                <span class="value">{{draft.order.lastIp}}</span>
            </div>

            <div class="data">
                <h5 class="heading">{{"Origin"|t('commerce')}}</h5>
                <span class="value">{{originLabel(draft.order.origin)}}</span>
            </div>
        </div>
    </div>
</template>

<script>
    import debounce from 'lodash.debounce'
    import {mapActions, mapGetters, mapState} from 'vuex'
    import OrderStatus from '../components/meta/OrderStatus'
    import OrderSite from '../components/meta/OrderSite'
    import ShippingMethod from '../components/meta/ShippingMethod'
    import DateOrderedInput from '../components/meta/DateOrderedInput'
    import Field from '../../base/components/Field'
    import mixins from '../mixins'

    export default {
        name: 'order-meta-app',

        components: {
            OrderStatus,
            OrderSite,
            ShippingMethod,
            DateOrderedInput,
            Field,
        },

        mixins: [mixins],

        computed: {
            ...mapState({
                draft: state => state.draft,
                originalDraft: state => state.originalDraft,
                recalculateLoading: state => state.recalculateLoading,
                saveLoading: state => state.saveLoading,
                editing: state => state.editing,
            }),

            ...mapGetters([
                'getErrors',
                'hasCustomer'
            ]),

            reference: {
                get() {
                    return this.draft.order.reference
                },

                set: debounce(function(value) {
                    const draft = JSON.parse(JSON.stringify(this.draft))
                    draft.order.reference = value

                    this.recalculateOrder(draft)
                        .then(() => {
                            this.$store.dispatch('displayNotice', "Order recalculated.")
                        })
                        .catch((error) => {
                            this.$store.dispatch('displayError', error);
                        })
                }, 1000)
            },

            couponCode: {
                get() {
                    return this.draft.order.couponCode
                },

                set: debounce(function(value) {
                    const draft = JSON.parse(JSON.stringify(this.draft))
                    draft.order.couponCode = value

                    this.recalculateOrder(draft)
                        .then(() => {
                            this.$store.dispatch('displayNotice', "Order recalculated.")
                        })
                        .catch((error) => {
                            this.$store.dispatch('displayError', error);
                        })
                }, 1000)
            },

            order: {
                get() {
                    return this.draft.order
                },

                set(value) {
                    const draft = this.draft
                    draft.order = value
                    this.recalculateOrder(draft)
                        .then(() => {
                            this.$store.dispatch('displayNotice', "Order recalculated.")
                        })
                        .catch((error) => {
                            this.$store.dispatch('displayError', error);
                        })
                }
            },

            shippingMethod() {
                let handle = this.draft.order.shippingMethodHandle;

                if (!handle) {
                    return null;
                }

                let shippingMethods = this.$store.getters.shippingMethods;
                if (!shippingMethods || !shippingMethods.length) {
                    return null;
                }

                let shippingMethod = shippingMethods.find(method => method.handle && method.handle == handle);

                return shippingMethod ? shippingMethod : handle;
            }
        },

        methods: {
            ...mapActions([
                'recalculateOrder',
            ]),

            markAsCompleted() {
                if (!window.confirm(this.$options.filters.t("Are you sure you want to complete this order?", 'commerce'))) {
                    return false
                }

                const draft = JSON.parse(JSON.stringify(this.draft))
                draft.order.isCompleted = true
                this.saveOrder(draft)
            },

            updateOrder(order) {
                const draft = JSON.parse(JSON.stringify(this.draft))
                draft.order = order
                this.recalculateOrder(draft)
                    .then(() => {
                        this.$store.dispatch('displayNotice', "Order recalculated.")
                    })
                    .catch((error) => {
                        this.$store.dispatch('displayError', error);
                    })
            },

            copy(ref) {
                ref.select()

                window.document.execCommand('copy');

                this.$store.dispatch('displayNotice', this.$options.filters.t("Copied!", 'commerce'));
            },

            updateDateOrderedInput(dateTime) {
                const draft = JSON.parse(JSON.stringify(this.draft))
                draft.order.dateOrdered = dateTime
                this.recalculateOrder(draft)
                    .then(() => {
                        this.$store.dispatch('displayNotice', "Order recalculated.")
                    })
                    .catch((error) => {
                        this.$store.dispatch('displayError', error);
                    })
            },

            originLabel(origin) {
                switch (origin) {
                    case 'web':
                        return this.$options.filters.t('Web', 'commerce');
                    case 'cp':
                        return this.$options.filters.t('Control panel', 'commerce');
                    default:
                        origin = origin.toString();
                        return origin.charAt(0).toUpperCase() + origin.slice(1);
                }
            }
        }
    }
</script>

<style lang="scss">
    @import "craftcms-sass/mixins";

    .order-meta-container {
        position: relative;
    }

    .order-meta-container.loading::after {
        background: #fff;
        bottom: 0;
        content: '';
        display: block;
        left: 0;
        opacity: .5;
        position: absolute;
        right: 0;
        top: 0;
        z-index: 2;
    }

    .order-meta .value {
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .order-number-value {
        display: flex;

        div {
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .hidden-input {
            width: 1px;
            height: 1px;
            overflow: hidden;
            input {
                margin: -3000px;
            }
        }

        .btn-link {
            @include margin-left(7px);
        }
    }

    .meta .data.order-meta-shipping-method .value {
        align-items: start;
        flex-direction: column;
    }

    .shipping-method-handle {
        color: $mediumTextColor;
    }
</style>
