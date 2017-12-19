<?php

namespace Wayforpay\Payment\Block\Form;

/**
 * Abstract class for Wayforpay payment method form
 */
abstract class Wayforpay extends \Magento\Payment\Block\Form
{
    protected $_instructions;
    protected $_template = 'html/wayforpay_form.phtml';
}
