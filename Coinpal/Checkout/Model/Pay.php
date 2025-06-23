<?php

namespace Coinpal\Checkout\Model;

use Magento\Quote\Model\Quote\Payment;
use Coinpal\Checkout\Helper\Logs;

class Pay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'coinpal_pay';
    protected $_code = self::CODE;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;

    protected $httpClientFactory;
    protected $orderSender;
	
	protected $urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Coinpal\Checkout\Helper\Pay $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
		\Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
		$this->urlBuilder = $urlBuilder;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        //$this->_minAmount = $this->getConfigData('min_order_total');
        //$this->_maxAmount = $this->getConfigData('max_order_total');
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function buildCheckoutRequest($quote)
    {
        $billing_address = $quote->getBillingAddress();
        
        $params = array();

        $params["sid"]                  = $this->getConfigData("merchant_id");
        $params["merchant_order_id"]    = $quote->getReservedOrderId();
        $params["cart_order_id"]        = $quote->getReservedOrderId();
        $params["currency_code"]        = $quote->getQuoteCurrencyCode();
        $params["total"]                = round($quote->getGrandTotal(), 2);
        $params["card_holder_name"]     = $billing_address->getName();
        $params["street_address"]       = $billing_address->getStreet()[0];
        if (count($billing_address->getStreet()) > 1) {
            $params["street_address2"]  = $billing_address->getStreet()[1];
        }
        $params["city"]                 = $billing_address->getCity();
        $params["state"]                = $billing_address->getRegion();
        $params["zip"]                  = $billing_address->getPostcode();
        $params["country"]              = $billing_address->getCountryId();
        $params["email"]                = $quote->getCustomerEmail();
        $params["phone"]                = $billing_address->getTelephone();
        //$params["return_url"]           = $this->getCancelUrl();
        //$params["x_receipt_link_url"]   = $this->getReturnUrl();
        $params["purchase_step"]        = "payment-method";
        $params["pay_direct"]        = "Y";

        return $params;
    }

	public function getCoinpalUrl($quote,$orderId){
		Logs::logw("#".$quote->getReservedOrderId()." order_id:".$orderId,"coinpal.log","getCoinpalUrl");

        $param = array(
            'version'=>'2',
            'requestId'=>"M".time().rand(1000,9999),
            'merchantNo'=> $this->getConfigData("merchant_id"),
            'storeId'=> $this->getConfigData("store_id"),
            'orderNo'=> $quote->getReservedOrderId(),
            'orderCurrencyType'=>'fiat',
            'orderAmount'=>round($quote->getGrandTotal(), 2),
            'orderCurrency'=>$quote->getQuoteCurrencyCode(),
            'payerEmail'=>$quote->getCustomerEmail(),
            'payerIP'=>$_SERVER['REMOTE_ADDR'],
            'successUrl'=>$this->helper->getUrl('coinpal/standard/responsepay')."?invoice=".$quote->getReservedOrderId().'&t=s&CUS_EMAIL='.$quote->getCustomerEmail(),
            'redirectURL'=>$this->helper->getUrl('coinpal/standard/responsepay')."?invoice=".$quote->getReservedOrderId().'&t=s&CUS_EMAIL='.$quote->getCustomerEmail(),
            'cancelURL'=>$this->helper->getUrl('coinpal/standard/fail')."?invoice=".$quote->getReservedOrderId().'&t=f',
            'notifyURL'=>$this->helper->getUrl('coinpal/standard/responsenotify'),
        );

        $param['sign'] = hash("sha256",
            $this->getConfigData("api_secret").
            $param['requestId'].
            $param['merchantNo'].
            $param['orderNo'].
            $param['orderAmount'].
            $param['orderCurrency']
        );

//        $gatewayUrl = "https://pay-dev.coinpal.io/gateway/pay/checkout";
        $gatewayUrl = "https://pay.coinpal.io/gateway/pay/checkout";
		$httpCode = $this->paycurl($gatewayUrl, http_build_query($param), $result);
		$datas = json_decode($result, true);
		if ($httpCode!=200 || empty($datas['nextStepContent'])) {
			// 请求失败
			Logs::logw("#".$quote->getReservedOrderId()." Request connection failed \n url:".$gatewayUrl." method:post Request:".json_encode($param)." Result:".$result,"coinpal.log","payment_url");
			$action=$this->helper->getUrl('coinpal/standard/fail')."?invoice=".$quote->getReservedOrderId().'&t=f&error='.$datas['respMessage'];
		} else {
			$action=$datas['nextStepContent'];
			//$action=str_replace("https","http",$action);   //测试
			Logs::logw("#".$quote->getReservedOrderId()." url:".$gatewayUrl." method:post Request:".json_encode($param)." Result:".$result,"coinpal.log","payment_url");
		}
		
		$arr=array(
			"url"=>$action,
			"message"=>$result,
		);

		return json_encode($arr);
	}
	
	private function paycurl( $url, $postData, &$result ){
		$options = array();
		if (!empty($postData)) {
			$options[CURLOPT_CUSTOMREQUEST] = 'POST';
			$options[CURLOPT_POSTFIELDS] = $postData;
		}
		$options[CURLOPT_USERAGENT] = 'Coinpal/v2.*/CURL';
		$options[CURLOPT_ENCODING] = 'gzip,deflate';
		$options[CURLOPT_HTTPHEADER] = [
		'Accept: text/html,application/xhtml+xml,application/xml',
		'Accept-Language: en-US,en',
		'Pragma: no-cache',
		'Cache-Control: no-cache'
				];
		$options[CURLOPT_RETURNTRANSFER] = 1;
		$options[CURLOPT_HEADER] = 0;
		if (substr($url,0,5)=='https') {
			$options[CURLOPT_SSL_VERIFYPEER] = false;
		}
		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $httpCode;
	}

    public function validateResponse($quote)
    {
        $sign = hash("sha256",
            $this->getConfigData("api_secret").
            $quote['requestId'].
            $quote["merchantNo"].
            $quote["orderNo"].
            $quote["orderAmount"].
            $quote["orderCurrency"]
        );
        if($sign==$quote['sign']){
            return true;
        }
        else{
            return false;
        }
    }

    public function postProcessing(\Magento\Sales\Model\Order $order, \Magento\Framework\DataObject $payment, $response) {
        // Update payment details
        /*$payment->setTransactionId($response['REQ_INVOICE']);
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionAdditionalInfo('coinpal_order_number', $response['REQ_INVOICE']);
        $payment->setAdditionalInformation('coinpal_order_number', $response['REQ_INVOICE']);
        $payment->setAdditionalInformation('coinpal_order_status', 'approved');
        $payment->place();*/


        // Update order status
        $order->setStatus($this->getOrderStatus());
        $order->setExtOrderId($response['orderNo']);
        $order->save();

		Logs::logw("#".$response['orderNo']." Successful modification of order status,ID:".$order->getId()." Number:".$response['orderNo']." Status:".$this->getOrderStatus(),"coinpal.log","Order_modification");
		
        // Send email confirmation
        //$this->orderSender->send($order);
    }
	
	public function postClosed(\Magento\Sales\Model\Order $order, \Magento\Framework\DataObject $payment, $response) {
		// Update order status
        $order->setStatus("canceled");
        $order->setExtOrderId($response['orderNo']);
        $order->save();

		Logs::logw("#".$response['orderNo']." Successful modification of order status,ID:".$order->getId()." Number:".$response['orderNo']." Status:".$this->getOrderStatus(),"coinpal.log","Order_modification");
		
	}
    
    public function getRedirectUrl()
    {
        $url = $this->helper->getUrl($this->getConfigData('redirect_url'));
        return $url;
    }

    public function getOrderStatus()
    {
        $value = $this->getConfigData('order_status');
        return $value;
    }

    public function getGoodsName($quote) {
        $goodsName = [];
        foreach($quote->getAllItems() as $item){
            $goodsName[] = $item->getName() . ' x ' . $item->getQty();
        }
        return implode(';', $goodsName);
    }

}