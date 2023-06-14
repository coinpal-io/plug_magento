# Magento Coinpal Checkout Installation

## Step 1: Log in to the Coinpal Admin Dashboard to get the Merchant Number and Secret Key.
1. [Register](https://portal.coinpal.io/#/admin/register)/[login](https://portal.coinpal.io/#/admin/login) and go to Coinpal's Admin Dashboard 

![](./img/register.png)

2. Follow the Dashboard guidelines to fill in the relevant information
![](./img/kyb.png)
3. Click the 'Integration' button in the lower left corner to get the corresponding Merchant Id and Secret Key
![](./img/api-key.png)

## Step 2: Installing the Coinpal Plugin on your Magento Site.
1. Click on the [Coinpal plugin](https://github.com/coinpal-io/plug_magento/blob/master/magento.zip) to download the Coinpal Magento Payment Plug.
2. Unzip the magento.zip file and enter the magento folder

![](./img/file1.png)

3. Copy the Coinpal folder to the Magento root app/code directory

![](./img/file2.png)

4. In command line, navigate to the magento root folder
Enter the following commands:

```
php bin/magento module:enable Coinpal_Checkout --clear-static-content
php bin/magento setup:upgrade
```

   If the page prompts: "There has been an error processing your request", run the following:
   
```
php bin/magento setup:static-content:deploy -f
```


5. Activate the Coinpal Magento Gateway

    Navigate to your Magento admin area and follow this path: Stores -> Configuration-> SALES -> Payment Methods.

    Find the payment method named 'Coinpal'.
![](./img/set.png)

![](./img/set2.png)

Copy and Paste all of the Settings you generated in your Coinpal Dashboard on Step #1.

Click Save Config Changes.

![](./img/set3.png)


## Step 3: Testing your Coinpal Magento Integration.

To confirm your Integration is properly working create a test order:

Add a test item to your shopping cart and view the cart.

Proceed to Checkout

Select 'Pay Crypto with Coinpal' as the payment method.

Click Continue Coinpal button

If you like you can now proceed to making a test payment.

![](./img/checkout.png)

## Step 4: Marking a Payment as Received on Magento.

Login to your Magento Admin Dashboard.

Go to the Magento Section and Click Orders.

You will see the test orders marked as “Paid”.

Check whether coins are settled to the CoinPal wallet.

You may also use a Block Explorer to verify if the transaction was processed.

After the verification of the above steps is completed, it means that the connection with Coinpal is successful.





