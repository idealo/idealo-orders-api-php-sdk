<?php

    function getDefault($sKey)
    {
        $sValue = '';
        switch ($sKey) {
            case 'token':
                $sValue = 'YOUR_SANDBOX_TOKEN';
                break;
            case 'mode':
                $sValue = 'Test';
                break;
        }
        return $sValue;
    }

    function getPostValue($sKey, $blGetDefault = true)
    {
        if(isset($_POST[$sKey])) {
            return $_POST[$sKey];
        } elseif($blGetDefault === true) {
            return getDefault($sKey);
        }
        return '';
    }
    
    function getClient()
    {
        require_once dirname(__FILE__).'/autoload.php';
        $sToken = getPostValue('token', false);
        $blIsLive = getPostValue('mode', false) == 'Live' ? true : false;
        $oClient = new idealo\Direktkauf\REST\Client($sToken, $blIsLive);
        return $oClient;
    }
    
    function getErrorOutput($oClient)
    {
        $sOutput  = '';
        $sOutput .= 'HTTP-Code: '.$oClient->getHttpStatus().'<br>';
        $sOutput .= 'CURL-Error: '.$oClient->getCurlError().'<br>';
        $sOutput .= 'CURL-Error-Nr: '.$oClient->getCurlErrno().'<br>';
        return $sOutput;
    }
    
    function getOrders()
    {
        $oClient = getClient();
        $aOrders = $oClient->getOrders();
        
        if($aOrders === false) {
            return getErrorOutput($oClient);
        } else {
            return 'HTTP-Code: '.$oClient->getHttpStatus().'<br><pre>'.print_r($aOrders, true).'</pre>';
        }
    }
    
    function getSupportedPaymentTypes()
    {
        $oClient = getClient();
        $aPaymentTypes = $oClient->getSupportedPaymentTypes();
        
        if($aPaymentTypes === false) {
            return getErrorOutput($oClient);
        } else {
            return 'HTTP-Code: '.$oClient->getHttpStatus().'<br><pre>'.print_r($aPaymentTypes, true).'</pre>';
        }
    }
    
    function sendOrderNr()
    {
        $sIdealoOrderNr = getPostValue('ordernr_idealo', false);
        $sShopOrderNr = getPostValue('ordernr_shop', false);
        if(!$sIdealoOrderNr) {
            return 'Idealo order-number missing!';
        } elseif(!$sShopOrderNr) {
            return 'Shop order-number missing!';
        }
        
        $oClient = getClient();
        $sResponse = $oClient->sendOrderNr($sIdealoOrderNr, $sShopOrderNr);
        
        if($sResponse === false) {
            return getErrorOutput($oClient);
        } else {
            return 'HTTP-Code: '.$oClient->getHttpStatus().'<br>'.$sResponse;
        }
    }
    
    function sendFulfillmentStatus()
    {
        $sOrderNr = getPostValue('fulfillment_ordernr_idealo', false);
        if(!$sOrderNr) {
            return 'Idealo order-number missing!';
        }
        
        $sTrackingCode = getPostValue('fulfillment_trackingcode', false);
        $sCarrier = getPostValue('fulfillment_carrier', false);
        
        $oClient = getClient();
        $sResponse = $oClient->sendFulfillmentStatus($sOrderNr, $sTrackingCode, $sCarrier);
        
        if($sResponse === false) {
            return getErrorOutput($oClient);
        } else {
            return 'HTTP-Code: '.$oClient->getHttpStatus().'<br>'.$sResponse;
        }
    }
    
    function sendRevocationStatus()
    {
        $sOrderNr = getPostValue('revocation_ordernr_idealo', false);
        if(!$sOrderNr) {
            return 'Idealo order-number missing!';
        }
        
        $sReason = getPostValue('revocation_reason', false);
        $sComment = getPostValue('revocation_comment', false);
        
        $oClient = getClient();
        $sResponse = $oClient->sendRevocationStatus($sOrderNr, $sReason, $sComment);
        if($sResponse === false) {
            return getErrorOutput($oClient);
        } else {
            return 'HTTP-Code: '.$oClient->getHttpStatus().'<br>'.$sResponse;
        }
    }
    
    function handleRequest($sRequest)
    {
        if(getPostValue($sRequest) && function_exists($sRequest)) {
            if(!getPostValue('token', false)) {
                return 'Token missing!';
            }
            return call_user_func($sRequest);
        }
    }

?>
<html>
    <head>
         <meta charset="UTF-8"> 
        <title>idealo SDK sandbox</title>
    </head>
    <body>
        <h1>idealo SDK sandbox</h1>
        <form id="sdk" name="sdk" method="post">
            <fieldset>
                <legend>Base configuration</legend>
                <table>
                    <tr>
                        <td width="175">Token</td>
                        <td><input type="text" name="token" value="<?php echo getPostValue('token'); ?>" size="25"></td>
                    </tr>
                    <tr>
                        <td>Mode</td>
                        <td>
                            <select name="mode" style="width:172px;">
                                <option <?php if(getPostValue('mode') == 'Live') echo 'selected'; ?>>Live</option>
                                <option <?php if(getPostValue('mode') == 'Test') echo 'selected'; ?>>Test</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </fieldset>
            <fieldset>
                <legend>getOrders</legend>
                <input type="submit" name="getOrders" value="Send request">
                <?php if($sResult = handleRequest('getOrders')) { ?>
                    <br><br><fieldset>
                        <legend>Result</legend>
                        <?php echo $sResult; ?>
                    </fieldset>
                <?php } ?>
            </fieldset>
            <fieldset>
                <legend>getSupportedPaymentTypes</legend>
                <input type="submit" name="getSupportedPaymentTypes" value="Send request">
                <?php if($sResult = handleRequest('getSupportedPaymentTypes')) { ?>
                    <br><br><fieldset>
                        <legend>Result</legend>
                        <?php echo $sResult; ?>
                    </fieldset>
                <?php } ?>
            </fieldset>
            <fieldset>
                <legend>sendOrderNr</legend>
                <table>
                    <tr>
                        <td width="175">Idealo order-number</td>
                        <td><input type="text" name="ordernr_idealo" value="<?php echo getPostValue('ordernr_idealo'); ?>" size="25"></td>
                    </tr>
                    <tr>
                        <td>Shop order-number</td>
                        <td><input type="text" name="ordernr_shop" value="<?php echo getPostValue('ordernr_shop'); ?>" size="25"></td>
                    </tr>
                </table>
                <input type="submit" name="sendOrderNr" value="Send request">
                <?php if($sResult = handleRequest('sendOrderNr')) { ?>
                    <br><br><fieldset>
                        <legend>Result</legend>
                        <?php echo $sResult; ?>
                    </fieldset>
                <?php } ?>
            </fieldset>
            <fieldset>
                <legend>sendFulfillmentStatus</legend>
                <table>
                    <tr>
                        <td width="175">Idealo order-number</td>
                        <td><input type="text" name="fulfillment_ordernr_idealo" value="<?php echo getPostValue('fulfillment_ordernr_idealo'); ?>" size="25"></td>
                    </tr>
                    <tr>
                        <td>Trackingcode (optional)</td>
                        <td><input type="text" name="fulfillment_trackingcode" value="<?php echo getPostValue('fulfillment_trackingcode'); ?>" size="25"></td>
                    </tr>
                    <tr>
                        <td>Shipping carrier (optional)</td>
                        <td><input type="text" name="fulfillment_carrier" value="<?php echo getPostValue('fulfillment_carrier'); ?>" size="25"></td>
                    </tr>
                </table>
                <input type="submit" name="sendFulfillmentStatus" value="Send request">
                <?php if($sResult = handleRequest('sendFulfillmentStatus')) { ?>
                    <br><br><fieldset>
                        <legend>Result</legend>
                        <?php echo $sResult; ?>
                    </fieldset>
                <?php } ?>
            </fieldset>
            <fieldset>
                <legend>sendRevocationStatus</legend>
                <table>
                    <tr>
                        <td width="175">Idealo order-number</td>
                        <td><input type="text" name="revocation_ordernr_idealo" value="<?php echo getPostValue('revocation_ordernr_idealo'); ?>" size="25"></td>
                    </tr>
                    <tr>
                        <td>Reason</td>
                        <td>
                            <select name="revocation_reason" style="width:172px;">
                                <option value="CUSTOMER_REVOKE" <?php if(getPostValue('revocation_reason') == 'CUSTOMER_REVOKE') echo 'selected'; ?>>Customer-revocation</option>
                                <option value="MERCHANT_DECLINE" <?php if(getPostValue('revocation_reason') == 'MERCHANT_DECLINE') echo 'selected'; ?>>Rejected by merchant</option>
                                <option value="RETOUR" <?php if(getPostValue('revocation_reason') == 'RETOUR') echo 'selected'; ?>>Return</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Comment (optional)</td>
                        <td><input type="text" name="revocation_comment" value="<?php echo getPostValue('revocation_comment'); ?>" size="25"></td>
                    </tr>
                </table>
                <input type="submit" name="sendRevocationStatus" value="Send request">
                <?php if($sResult = handleRequest('sendRevocationStatus')) { ?>
                    <br><br><fieldset>
                        <legend>Result</legend>
                        <?php echo $sResult; ?>
                    </fieldset>
                <?php } ?>
            </fieldset>
        </form>
    </body>
</html>