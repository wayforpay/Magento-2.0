<?php

namespace Wayforpay\Payment\Controller\Url;

use Magento\Authorizenet\Model\DirectPost;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Class WayforpaySuccess
 *
 * @package Wayforpay\Payment\Controller\Url
 */
class WayforpaySuccess extends Action /* implements CsrfAwareActionInterface*/
{
    /** @var PageFactory  */
    protected $resultPageFactory;

   /**
    * @param RequestInterface $request
    * @return InvalidRequestException|null
    */
/*    public function createCsrfValidationException(RequestInterface $request): InvalidRequestException
    {
        return null;
    }
*/
    /**
    * @param RequestInterface $request
    * @return bool|null
    */
/*    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }
*/
    
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
