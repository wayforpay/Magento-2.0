<?php

namespace Wayforpay\Payment\Controller\Url;

use Magento\Authorizenet\Model\DirectPost;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class WayforpayService
 *
 * @package Wayforpay\Payment\Controller\Url
 */
class WayforpayService extends Action
{
    /** @var PageFactory  */
    protected $resultPageFactory;

    /**
    * @param RequestInterface $request
    * @return InvalidRequestException|null
    */
    public function createCsrfValidationException(RequestInterface $request): InvalidRequestException
    {
        return null;
    }

    /**
    * @param RequestInterface $request
    * @return bool|null
    */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

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
     * @return Page
     */
    public function execute()
    {
        //load model

        /* @var $paymentMethod DirectPost */
        $paymentMethod = $this->_objectManager->create('Wayforpay\Payment\Model\Wayforpay');

        //get request data
        $data = json_decode(file_get_contents("php://input"), true);
        //		if (empty($data)) {
        //			$callback = json_decode(file_get_contents("php://input"));
        //			$data = array();
        //			foreach ($callback as $key => $val) {
        //				$data[$key] = $val;
        //			}
        //		}

        $paymentMethod->processResponse($data);
        //		return $this->resultPageFactory->create();
    }
}
