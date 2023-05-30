<?php

namespace Glocash\Checkout\Controller\Standard;

use Glocash\Checkout\Helper\Logs;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Responsenotify extends Action implements CsrfAwareActionInterface
{
	/** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * PaysoftSuccess constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     *
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): InvalidRequestException
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
	
	
	public function validateResponse($quote)
    {		
		$model = 'Glocash\Checkout\Model\Pay';
		$paymentMethod = $this->_objectManager->create($model);
        $sign = hash("sha256",
            $paymentMethod->getConfigData("api_secret").
            $quote['requestId'].
            $quote["merchantNo"].
            $quote["orderNo"].
            $quote["orderAmount"].
            $quote["orderCurrency"]
        );
        if(strtoupper($sign)==strtoupper($quote['sign'])){
            return true;
        } else{
			return false;
		}
		
    }
	

    public function execute()
    {
        try {
			$data = $this->getRequest()->getPostValue();
			if (empty($data)) {
				$callback = json_decode(file_get_contents("php://input"));
				if (empty($callback)){
					Logs::logw("notify Request Parameter is not matched.","glocash.log","notify");
					throw new Exception(__('Request Parameter is not matched.'));
				}
				$data = array();
				foreach ($callback as $key => $val) {
					$data[$key] = $val;
				}
			}
			Logs::logw("notify Result:".json_encode($data),"glocash.log","notify");

            // Create the order if the response passes validation
			$ordernumber=empty($data["orderNo"])?"":$data["orderNo"];
			
            if ($this->validateResponse($data))
            {
                try {
					Logs::logw("#".$ordernumber." Sign_verification","glocash.log","notify");
					$model = 'Glocash\Checkout\Model\Pay';
					$paymentMethod = $this->_objectManager->create($model);
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					$order = $objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($data['REQ_INVOICE']);
					if(!empty($order->getId())){
                        $payment = $order->getPayment();
                        if($data['status']=="paid"){
                            $paymentMethod->postProcessing($order, $payment, $data);
                        }
                        else if($data['status']=="failed"){
                            $paymentMethod->postClosed($order, $payment, $data);
                        }
					}
                } catch (\Exception $e) {
					Logs::logw("#".$ordernumber." ".$e->getMessage(),"glocash.log","Error");
                     throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            }
            else
            {
				Logs::logw("#".$ordernumber." Validation failed Result:".json_encode($data),"glocash.log","Error");
                 throw new \Magento\Framework\Exception\LocalizedException(__('Validation failed'));
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
			Logs::logw($e->getMessage(),"glocash.log","Error");
             throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
			Logs::logw($e->getMessage(),"glocash.log","Error");
             throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }


    }

}
