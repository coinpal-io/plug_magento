define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'coinpal_pay',
                component: 'Coinpal_Checkout/js/view/payment/method-renderer/coinpal-pay'
            }
        );
        return Component.extend({});
    }
 );