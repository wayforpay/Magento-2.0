<?php

namespace Wayforpay\Payment\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class Wayforpay
 *
 * @package Wayforpay\Payment\Model
 */
class Wayforpay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'wayforpay';

    const SIGNATURE_SEPARATOR = ';';

    const ORDER_SEPARATOR = '#';

    /** @var array */
    protected $keysForResponseSignature
        = [
            'merchantAccount',
            'orderReference',
            'amount',
            'currency',
            'authCode',
            'cardPan',
            'transactionStatus',
            'reasonCode'
        ];

    /** @var array */
    protected $keysForSignature
        = [
            'merchantAccount',
            'merchantDomainName',
            'orderReference',
            'orderDate',
            'amount',
            'currency',
            'productName',
            'productCount',
            'productPrice'
        ];

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    protected $_isGateway          = true;
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     *
     * @var bool
     */
    protected $_isOffline = false;

    protected $_gateUrl = "https://secure.wayforpay.com/pay";

    protected $_encryptor;

    protected $orderFactory;

    protected $urlBuilder;

    protected $_transactionBuilder;

    protected $_logger;

    protected $_order;

    /**
     * Wayforpay constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Magento\Framework\Module\ModuleListInterface                $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
     * @param \Magento\Framework\Encryption\EncryptorInterface             $encryptor
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param Transaction\BuilderInterface                                 $builderInterface
     * @param \Magento\Sales\Model\OrderFactory                            $orderFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderFactory        = $orderFactory;
        $this->urlBuilder          = $urlBuilder;
        $this->_transactionBuilder = $builderInterface;
        $this->_encryptor          = $encryptor;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/wayforpay.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
        $this->_gateUrl = 'https://secure.wayforpay.com/pay';
    }

    /**
     *
     * @param $orderId
     * @return Order
     */
    public function getOrder($orderId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderId);
    }

    /**
     *
     * @param $orderId
     * @return float
     */
    public function getAmount($orderId)
    {
        return $this->getOrder($orderId)->getGrandTotal();
    }

    public function getSignature($option, $keys)
    {
        $hash = [];
        foreach ($keys as $dataKey) {
            if (!isset($option[$dataKey])) {
                continue;
            }
            if (is_array($option[$dataKey])) {
                foreach ($option[$dataKey] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$dataKey];
            }
        }
        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);

        $secret = $this->getConfigData('secret_key');
        return hash_hmac('md5', $hash, $secret);
    }

    /**
     * @param $options
     * @return string
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForSignature);
    }

    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }

    /**
     *
     * @param $orderId
     * @return int|null
     */
    public function getCustomerId($orderId)
    {
        return $this->getOrder($orderId)->getCustomerId();
    }

    /**
     *
     * @param $orderId
     * @return null|string
     */
    public function getCurrencyCode($orderId)
    {
        return $this->getOrder($orderId)->getBaseCurrencyCode();
    }

    /**
     *
     * @param string                        $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos(strval($this->getConfigData('allowed_carrier')), strval($shippingMethod)) !== false;
    }

    /**
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
            );
    }

    /**
     *
     * @return string
     */
    public function getGateUrl()
    {
        return $this->getConfigData('request_url') ? $this->getConfigData('request_url') : $this->_gateUrl;
    }

    /**
     *
     * @return mixed
     */
    public function getDataIntegrityCode()
    {
        return $this->_encryptor->decrypt($this->getConfigData('secret_key'));
    }

    /**
     *
     * @param $orderId
     * @return array
     */
    public function getPostData($orderId)
    {
        $order  = $this->getOrder($orderId);
        $amount = $this->getAmount($orderId);

        $fields = [
            'merchantAccount'               => $this->getConfigData('merchant'),
            'orderReference'                => $orderId . self::ORDER_SEPARATOR . time(),
            'orderDate'                     => strtotime($order->getCreatedAt()),
            'merchantAuthType'              => 'simpleSignature',
            'merchantDomainName'            => $_SERVER['HTTP_HOST'],
            'merchantTransactionSecureType' => 'AUTO',
            'order_desc'                    => 'Order description',
            'amount'                        => $amount,
            'currency'                      => $order->getOrderCurrencyCode(),
            'serviceUrl'                    => $this->urlBuilder->getUrl('wayforpay/url/wayforpayservice'),
            'returnUrl'                     => $this->urlBuilder->getUrl('wayforpay/url/wayforpaysuccess'),
            'language'                      => $this->getConfigData('language'),
        ];

        $cartItems = $order->getAllVisibleItems();

        $productNames  = [];
        $productQty    = [];
        $productPrices = [];
        foreach ($cartItems as $_item) {
            $productNames[]  = $_item->getName();
            $productPrices[] = round($_item->getPrice(), 2);
            $productQty[]    = (int)$_item->getQtyOrdered();
        }
        $fields['productName']  = $productNames;
        $fields['productPrice'] = $productPrices;
        $fields['productCount'] = $productQty;

        /**
         * Check phone
         */
        $phone = str_replace(['+', ' ', '(', ')'], ['', '', '', ''], $order->getBillingAddress()->getTelephone());
        if (strlen($phone) == 10) {
            $phone = '38' . $phone;
        } elseif (strlen($phone) == 11) {
            $phone = '3' . $phone;
        }

        $fields['clientFirstName'] = $order->getCustomerFirstname();
        $fields['clientLastName']  = $order->getCustomerLastname();
        $fields['clientEmail']     = $order->getCustomerEmail();
        $fields['clientPhone']     = $phone;
        $fields['clientCity']      = $order->getBillingAddress()->getCity();

        $fields['merchantSignature'] = $this->getRequestSignature($fields);

        return $fields;
    }

    public function getFormFields($data)
    {
        $html = '';
        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
            } elseif (is_array($value)) {
                foreach ($value as $avalue) {
                    $html .= '<input type="hidden" name="' . $name . '[]" value="' . htmlspecialchars($avalue) . '">';
                }
            }
        }
        return $html;
    }

    /**
     * @param $responseData
     * @return bool
     */
    public function processResponse($responseData)
    {
        if (empty($responseData)) {
            $callback     = json_decode(file_get_contents("php://input"));
            $responseData = [];
            if (!empty($callback)) {
                foreach ($callback as $key => $val) {
                    $responseData[$key] = $val;
                }
            }
        }
        $debugData = ['response' => $responseData];
        $this->_logger->debug("processResponse", $debugData);
        if (empty($responseData['orderReference'])) {
            return false;
        }

        list($orderId, ) = explode(self::ORDER_SEPARATOR, $responseData['orderReference']);
        $order = $this->getOrder($orderId);
        if ($order && ($this->_processOrder($order, $responseData) === true)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param Order $order
     * @param mixed $response
     * @return bool
     */
    protected function _processOrder(Order $order, $response)
    {
        $this->_logger->debug(
            "_processWayforpay",
            [
                "\$order"    => $order,
                "\$response" => $response
            ]
        );
        try {
            if ($order->getGrandTotal() != $response["amount"]) {
                $this->_logger->debug("_processOrder: amount mismatch, order FAILED");
                return false;
            }
            if ($response["reasonCode"] == '1100') {
                $this->createTransaction($order, $response);
                $order
                    ->setState($this->getConfigData("order_status"))
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW))
                    ->save();
                
                if (!isset($_POST['merchantAccount'])) {// this is service request
                    $this->sendAnswerToGateway($response['orderReference']);
                }
                
                $this->_logger->debug("_processOrder: order state changed: STATE_PROCESSING");
                $this->_logger->debug("_processOrder: order data saved, order OK");
            } else {
                $order
                    ->setState(Order::STATE_CANCELED)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED))
                    ->save();

                $this->_logger->debug("_processOrder: order state not STATE_CANCELED");
                $this->_logger->debug("_processOrder: order data saved, order not approved");
            }
            return true;
        } catch (\Exception $e) {
            $this->_logger->debug("_processOrder exception", $e->getTrace());
            return false;
        }
    }

    public function isPaymentValid($response)
    {
        $merchant = $this->getConfigData('merchant');
        if ($merchant != $response['merchantAccount']) {
            return 'An error has occurred during payment. Merchant data is incorrect.';
        }
        if ($response['reasonCode'] != '1100') {
            return 'An error has occurred during payment. Order is declined.';
        }

        $responseSignature = $response['merchantSignature'];
        if ($this->getSignature($response, $this->keysForResponseSignature) != $responseSignature) {
            return 'An error has occurred during payment. Signature is not valid.';
        }
        return true;
    }

    public function createTransaction($order = null, $paymentData = [])
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();

            $payment->setLastTransId($paymentData['orderReference']);
            $payment->setTransactionId($paymentData['orderReference']);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans       = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['orderReference'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$paymentData]
                )
                ->setFailSafe(true)
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();
        } catch (\Exception $e) {
            $this->_logger->debug("_processOrder exception", $e->getTrace());
        }
    }

    private function sendAnswerToGateway($orderReference)
    {
        $time                           = time();
        $responseToGateway              = [
            'orderReference' => $orderReference,
            'status'         => 'accept',
            'time'           => $time
        ];
        $sign                           = implode(self::SIGNATURE_SEPARATOR, $responseToGateway);
        $sign                           = hash_hmac('md5', $sign, $this->getConfigData('secret_key'));
        $responseToGateway['signature'] = $sign;

        echo json_encode($responseToGateway);
        exit();
    }
}
