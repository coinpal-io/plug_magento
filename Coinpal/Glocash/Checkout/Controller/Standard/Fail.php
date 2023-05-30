<?php
namespace Glocash\Checkout\Controller\Standard;

use Glocash\Checkout\Helper\Logs;

class Fail extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
		// Get params from response
		$params = $this->getRequest()->getParams();
		
		Logs::logw("return:".json_encode($params),"glocash.log","return");
		
		$orderNumber = $params['invoice'];
		
		$page="";
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($orderNumber);
		$order->setStatus("canceled");
        $order->setExtOrderId($orderNumber);
        $order->save();
		
		$page='glocash_checkout_fail';
		
		$resultPage = $this->_pageFactory->create();
		$resultPage = $resultPage->addHandle($page);
		return $resultPage;


		

    }
}
