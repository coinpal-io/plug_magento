<?php

namespace Glocash\Checkout\Controller\Standard;

use Glocash\Checkout\Helper\Logs;

class Redirectpay extends \Glocash\Checkout\Controller\Pay
{

    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_cancelPayment();
            $this->_checkoutSession->restoreQuote();
            $this->getResponse()->setRedirect(
                $this->getCheckoutHelper()->getUrl('checkout')
            );
        }
        
        $quote = $this->getQuote();
        $email = $this->getRequest()->getParam('email');
        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->getCheckoutSession()->loadCustomerQuote();
            $quote->updateCustomerData($this->getQuote()->getCustomer());
        }
        else
        {
            $quote->setCustomerEmail($email);
        }
        $quote->reserveOrderId();
        $this->quoteRepository->save($quote);
						

        $params = [];
        $params["fields"] = $this->getPaymentMethod()->buildCheckoutRequest($quote);
		
		$orderId=0;
		//create order
		try {
			$paymentMethod = $this->getPaymentMethod();
            $code = $paymentMethod->getCode();
			if ($this->getCustomerSession()->isLoggedIn()) {
				$quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
			}
			else {
				$quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
			}

			$quote->setCustomerEmail($email);
			$quote->setPaymentMethod($code);
			$quote->getPayment()->importData(['method' => $code]);
			$quote->save();

			$this->initCheckout();
			
			$orderId = $this->cartManagement->placeOrder($this->_checkoutSession->getQuote()->getId(), $this->_quote->getPayment());
			
			Logs::logw("#".$quote->getReservedOrderId()." order_id:".$orderId,"coinpal.log","Create_order");


		} catch (\Exception $e) {
			Logs::logw("#".$quote->getReservedOrderId()." ".json_encode($e->getMessage()),"coinpal.log","Error");
		}
		
		$json=$this->getPaymentMethod()->getGlocashUrl($quote,$orderId);
		
		$arr=json_decode($json,true);
        $params["url"] = $arr["url"];
		if(empty($params["url"])){
			$params["url"] =$this->getCheckoutHelper()->getUrl('checkout');
		}	
		

        return  $this->resultJsonFactory->create()->setData($params);
    }
	
	
	

}
