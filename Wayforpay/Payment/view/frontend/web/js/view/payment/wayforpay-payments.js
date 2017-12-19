/*browser:true*/
/*global define*/
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
                type: 'wayforpay',
                component: 'Wayforpay_Payment/js/view/payment/method-renderer/wayforpay-method'
            }
            
        );
        return Component.extend({});
    }
);