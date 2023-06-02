<?php

namespace Coinpal\Checkout\Controller\Standard;

class Cancelpay extends \Coinpal\Checkout\Controller\Pay
{

    public function execute()
    {
        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->getCheckoutHelper()->getUrl('checkout')
        );
    }

}
