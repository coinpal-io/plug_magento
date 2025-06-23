<?php
namespace Coinpal\Checkout\Controller\Standard;

use Coinpal\Checkout\Helper\Logs;

class Responsepay extends \Coinpal\Checkout\Controller\Pay
{

    public function execute()
    {
		// Get params from response
		$params = $this->getRequest()->getParams();
		
		Logs::logw("return:".json_encode($params),"coinpal.log","return");
		
		$orderNumber = $params['invoice'];
		
		$returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
		
		try {
			
			if($params['t']=="f"){
				$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$order = $objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($orderNumber);
				$order->setStatus("closed");
				$order->setExtOrderId($orderNumber);
				$order->save();
			}
			else{
				$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
			}

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
			Logs::logw("order_number:".$orderNumber."  ".$e->getMessage(),"coinpal.log","Error");
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
			$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
        } catch (\Exception $e) {
			Logs::logw("order_number:".$orderNumber."  ".$e->getMessage(),"coinpal.log","Error");
            $this->messageManager->addExceptionMessage($e, __('We can\'t place the order.'));
			$returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
        }

        $this->getResponse()->setRedirect($returnUrl);


		

    }
}
