<?php

namespace Wayforpay\Payment\Controller\Url;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;


class WayforpayService extends Action
{
	/** @var \Magento\Framework\View\Result\PageFactory  */
	protected $resultPageFactory;

	
	/**
	* @param RequestInterface $request
	* @return InvalidRequestException|null
	*/
	public function createCsrfValidationException(RequestInterface $request	): InvalidRequestException
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
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
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
