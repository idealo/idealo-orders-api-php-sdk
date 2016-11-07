
idealo SDK implementation guide
######################################

Technical requirements:
- Standard apache webserver with at least php 5.3

----------------------------------------------------------

Introduction:
The initial implementation of the idealo SDK is pretty easy and straightforward.
How it is used can be seen in the "sandbox.php" file located in the same folder as this readme file.
The SDK only exists of the Client class right now, which can be found in the namespace idealo\Direktkauf\REST.

----------------------------------------------------------

Basics:
The SDK has an autoloader file, which automatically loads the class(es) of the SDK, so that you can use all of them in your project.
Simply include the autoloader file at the spot in your code, where you create the instance of the Client object using "require_once".

require_once dirname(__FILE__).'/sdk/autoload.php';

Then you can instantiate the REST-client-class from anywhere in your code like this:

$oClient = new idealo\Direktkauf\Client();

The client needs 2 parameters:
1. token - The authentification token for idealo
2. isLiveMode - true for live-mode and fale for test-mode

You can either put them right in the constructor:

$sToken = 'xyz';
$blLiveMode = true;
$oClient = new Fatchip\REST\Client($sToken, $blLiveMode);

or you can set them with their setters:

$oClient = new idealo\Direktkauf\Client();
$oClient->setToken('xyz');
$oClient->setIsLiveMode(true);

----------------------------------------------------------

Implementation:
With this client object you have direct access to all the REST-API functions from idealo.

At the moment there the following 5 requests available:

--> 1. $oClient->getOrders()

Requests all open orders from idealo.
They are delivered an an associative array, directly like idealo delivers them in the following format:

#############################################################
Array
(
    [order_number] => ZNQQQKBP
    [created_at] => 2015-07-17T17:19:25.000+02:00
    [status] => PROCESSING
    [currency] => EUR
    [total_line_items_price] => 225.99
    [total_price] => 228.98
    [total_shipping] => 2.99
    [total_tax] => 36.08
    [vat_rate] => 19.0
    [updated_at] => 2015-07-17T17:19:25.000+02:00
    [customer] => Array
        (
            [email] => m-u2sc9fn6467ujqcw@checkout-sandbox.lvl.bln
            [phone] => 
        )

    [shipping_address] => Array
        (
            [address1] => Straße 123
            [address2] => 
            [city] => Ort
            [country] => DE
            [given_name] => First
            [family_name] => Name
            [zip] => 66666
        )

    [billing_address] => Array
        (
            [address1] => Straße 123
            [address2] => 
            [city] => Ort
            [country] => DE
            [given_name] => First
            [family_name] => Name
            [zip] => 66666
        )

    [line_items] => Array
        (
            [0] => Array
                (
                    [price] => 225.99
                    [quantity] => 1
                    [sku] => ABC234
                    [title] => Canon EOS 700D + 18-55 IS STM
                )

        )

    [fulfillment] => Array
        (
            [type] => POSTAL
            [carrier] => 
            [transaction_code] => 
            [fulfillment_options] => Array
                (
                )

        )

    [payment] => Array
        (
            [payment_method] => CREDITCARD
            [transaction_id] => snakeoil-f026e9c
        )

)
#############################################################

--> 2. $oClient->getSupportedPaymentTypes()

Delivers all currently supported payment types in the following format:

#############################################################
Array
(
    [CREDITCARD] => Credit Card Payment Method (Heidelpay)
    [SOFORT] => SOFORT Überweisung Payment Method
    [PAYPAL] => PayPal Payment Method
)
#############################################################

--> 3. $oClient->sendOrderNr($sIdealoOrderNr, $sShopOrderNr)
Parameters:
sIdealoOrderNr - The order-nr you got from idealo in the "order_number" from the getOrders request
sShopOrderNr - The order-nr this idealo order received in your shop.

This request transmits and connects the order-number from your shop-system to the idealo-order.

--> 4. sendFulfillmentStatus($sIdealoOrderNr, $sTrackingCode, $sCarrier)
Parameters:
sIdealoOrderNr - The order-nr you got from idealo in the "order_number" from the getOrders request
sTrackingCode (optional) - The trackingcode for the current order
sCarrier (optional) - The shipping-carrier for the current order ( DHL, DPD, UPS, FedEx, ...)

This request marks the order in idealo as shipped and adds trackingcode and carrier information to the order.

--> 5. sendRevocationStatus($sIdealoOrderNr, $sReason, $sComment)
Parameters:
sIdealoOrderNr - The order-nr you got from idealo in the "order_number" from the getOrders request
sReason - The reason of revocation - can be "CUSTOMER_REVOKE", "MERCHANT_DECLINE" or "RETOUR"
sComment (optional) - A 255 digit text with a comment from the merchant

----------

For more information concerning the requests, have a look at the API documentation from idealo.

----------------------------------------------------------

Error-handling:
The client will return FALSE when any of the above listed requests failed with a CURL-error.

You can access the information to this error for logging purposes or whatever you need them for, with the following methods:
$oClient->getCurlError() // error-message from CURL
$oClient->getCurlErrno() // error-number from CURL

In any case you can get the HTTP status code from the last request with the following method:
$oClient->getHttpStatus()

When this method returns 200 everything was ok with the last request.

In the idealo API documentation, you can find a list with the HTTP status error-codes and their meanings for every request.

----------------------------------------------------------

Testing:
You can configure a direct link to a test-file filled with json-encoded orders like you would receive them directly from the API.
You have to enter the link in the "$sDebugDirectUrl" parameter in the idealo/Direktkauf/REST/Client.php file for example like this:
"http://*YOUR_SERVER_HERE*/order_test_file.txt"