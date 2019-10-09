<?php

namespace Wayforpay\Payment\Controller\Url;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class WayforpaySuccess
 *
 * @package Wayforpay\Payment\Controller\Url
 */
class WayforpaySuccess extends Action
{
    /** @var PageFactory  */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Load the page defined
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        //load model

        /* @var $paymentMethod \Magento\Authorizenet\Model\DirectPost */
        $paymentMethod = $this->_objectManager->create('Wayforpay\Payment\Model\Wayforpay');

        //get request data
        $data = $this->getRequest()->getPostValue();
        if (empty($data)) {
            $callback = json_decode(file_get_contents("php://input"));
            $data = [];
            foreach ($callback as $key => $val) {
                $data[$key] = $val;
            }
        }

        $response = $paymentMethod->processResponse($data);
//        return $this->resultPageFactory->create()->setPath('checkout/cart');
        if ($response) {
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_redirect('checkout/onepage/failure');
        }
    }
}
