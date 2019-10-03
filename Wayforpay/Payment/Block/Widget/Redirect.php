<?php

namespace Wayforpay\Payment\Block\Widget;

/**
 * Abstract class for Cash On Delivery and Bank Transfer payment method form
 */

use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\OrderFactory;
use Wayforpay\Payment\Model\Wayforpay;

class Redirect extends Template
{
    /**
     * @var Wayforpay
     */
    protected $Config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Config
     */
    protected $_orderConfig;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var string
     */
    protected $_template = 'html/wyforpay_form.phtml';

    /**
     * @param Template\Context                $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Session                         $customerSession
     * @param OrderFactory                    $orderFactory
     * @param Config                          $orderConfig
     * @param Context                         $httpContext
     * @param Wayforpay                       $paymentConfig
     * @param array                           $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        Session $customerSession,
        OrderFactory $orderFactory,
        Config $orderConfig,
        Context $httpContext,
        Wayforpay $paymentConfig,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory    = $orderFactory;
        $this->_orderConfig     = $orderConfig;
        $this->_isScopePrivate  = true;
        $this->httpContext      = $httpContext;
        $this->Config           = $paymentConfig;
    }

    /**
     * Get instructions text from config
     *
     * @return null|string
     */
    public function getGateUrl()
    {
        return $this->Config->getGateUrl();
    }

    /**
     * Получить сумму к оплате
     *
     * @return float|null
     */
    public function getAmount()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        if ($orderId) {
            $incrementId = $this->_checkoutSession->getLastRealOrderId();

            return $this->Config->getAmount($incrementId);
        }

        return null;
    }

    /**
     * Получить данные формы
     *
     * @return array|null
     */
    public function getPostData()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        if ($orderId) {
            $incrementId = $this->_checkoutSession->getLastRealOrderId();
            $fields      = $this->Config->getPostData($incrementId);
            return $this->Config->getFormFields($fields);
        }

        return null;
    }

    /**
     * Получить Pay URL
     *
     * @return string
     */
    public function getPayUrl()
    {
        $baseUrl = $this->getUrl("wayforpay/url");

        //print_R ($baseUrl);die;
        return "{$baseUrl}wayforpaysuccess";
    }

    /**
     * @return \Magento\Sales\Model\Order|null
     */
    public function getLastOrder()
    {
        $orderId  = $this->_checkoutSession->getLastOrderId();
        $order    = $this->_orderFactory->create();
        $resource = $order->getResource()->load($order, $orderId);

        return $order;
    }

    /**
     * @param string $template
     * @return Template
     */
    public function setTemplate($template)
    {
        if ($order = $this->getLastOrder()) {
            if ($payment = $order->getPayment()) {
                if ($method = $payment->getMethodInstance()) {
                    if ($method->getCode() == \Wayforpay\Payment\Model\Wayforpay::METHOD_CODE) {
                        return parent::setTemplate($template);
                    }
                }
            }
        }
        return parent::setTemplate('');
    }
}
