<?php
/*
   Copyright 2015 idealo internet GmbH

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/


namespace idealo\Direktkauf\REST;

class Client
{

    protected $sAPILiveUrl = 'https://checkout-api.idealo.com/v1/';
    protected $sAPITestUrl = 'https://checkout-api.sandbox.idealo.com/v1/';
    
    /**
     * You can enter a URL to a test file with order-data here
     * This will be used to bypass the idealo API for testing purposes
     * Handle with caution!
     * Testfile needs to be utf8 encoded
     * Will not be used if set to false
     * 
     * @var string
     */
    protected $sDebugDirectUrl = false;
    
    protected $sToken = null;
    protected $iHttpStatus = null;
    protected $blIsLiveMode = null;
    protected $sCurlError = false;
    protected $iCurlErrno = false;
    
    const URL_TYPE_GET_ORDERS = 'getOrders';
    const URL_TYPE_GET_SUPPORTED_PAYMENT_TYPES = 'getSupportedPaymentTypes';
    const URL_TYPE_SEND_ORDER_NR = 'sendOrderNr';
    const URL_TYPE_SEND_FULFILLMENT = 'sendFulfillmentStatus';
    const URL_TYPE_SEND_REVOCATION = 'sendRevocationStatus';
 
    public function __construct($sToken = false, $blLive = false)
    {
        if($sToken !== false) {
            $this->setToken($sToken);
        }
        $this->setIsLiveMode($blLive);
    }
    
    public function setToken($sToken) 
    {
        $this->sToken = $sToken;
    }
    
    public function getToken() 
    {
        return $this->sToken;
    }
    
    protected function setHttpStatus($iHttpStatus) 
    {
        $this->iHttpStatus = $iHttpStatus;
    }
    
    public function getHttpStatus() 
    {
        return $this->iHttpStatus;
    }
    
    public function setIsLiveMode($blLive) 
    {
        $this->blIsLiveMode = $blLive;
    }
    
    public function getIsLiveMode() 
    {
        return $this->blIsLiveMode;
    }
    
    protected function setCurlError($sCurlError)
    {
        $this->sCurlError = $sCurlError;
    }
    
    public function getCurlError() 
    {
        return $this->sCurlError;
    }
    
    protected function setCurlErrno($sCurlErrno)
    {
        $this->sCurlErrno = $sCurlErrno;
    }
    
    public function getCurlErrno() 
    {
        return $this->sCurlErrno;
    }
    
    protected function getRequestUrl($sType, $sOrderNr = false)
    {
        if($this->sDebugDirectUrl !== false && $sType != self::URL_TYPE_GET_SUPPORTED_PAYMENT_TYPES) {
            return $this->sDebugDirectUrl;
        }
        
        if($this->getIsLiveMode() === true) {
            $sBaseUrl = $this->sAPILiveUrl;
        } else {
            $sBaseUrl = $this->sAPITestUrl;
        }
        
        $sUrl = false;
        switch ($sType) {
            case self::URL_TYPE_GET_ORDERS:
                $sUrl = $sBaseUrl.'orders?key='.$this->getToken();
                break;
            case self::URL_TYPE_GET_SUPPORTED_PAYMENT_TYPES:
                $sUrl = $sBaseUrl.'payment/supported?key='.$this->getToken();
                break;
            case self::URL_TYPE_SEND_ORDER_NR:
                $sUrl = $sBaseUrl.'order/'.$sOrderNr.'?key='.$this->getToken();
                break;
            case self::URL_TYPE_SEND_FULFILLMENT:
                $sUrl = $sBaseUrl.'order/'.$sOrderNr.'/fulfillment?key='.$this->getToken();
                break;
            case self::URL_TYPE_SEND_REVOCATION:
                $sUrl = $sBaseUrl.'order/'.$sOrderNr.'/revocation?key='.$this->getToken();
                break;
        }
        return $sUrl;
    }
    
    public function getOrders() 
    {        
        $sUrl = $this->getRequestUrl(self::URL_TYPE_GET_ORDERS);        
        $aOrders = $this->getJsonArrayFromRequest($sUrl);
        return $aOrders;
    }
    
    public function getSupportedPaymentTypes()
    {   
        $sUrl = $this->getRequestUrl(self::URL_TYPE_GET_SUPPORTED_PAYMENT_TYPES);
        $aPaymentsTypes = $this->getJsonArrayFromRequest($sUrl);
        return $aPaymentsTypes;
    }
    
    public function sendOrderNr($sIdealoOrderNr, $sShopOrderNr)
    {
        $sUrl = $this->getRequestUrl(self::URL_TYPE_SEND_ORDER_NR, $sIdealoOrderNr);
        $aParams = array(
            'merchant_order_no' => $sShopOrderNr,
        );
        return $this->sendCurlRequest($sUrl, $aParams);
    }
    
    public function sendFulfillmentStatus($sIdealoOrderNr, $sTrackingCode = '', $sCarrier = '')
    {
        $sUrl = $this->getRequestUrl(self::URL_TYPE_SEND_FULFILLMENT, $sIdealoOrderNr);
        $aParams = array();
        if(!empty($sTrackingCode)) {
            $aParams['tracking_number'] = $sTrackingCode;
            $aParams['carrier'] = $sCarrier;
        }
        return $this->sendCurlRequest($sUrl, $aParams);
    }
    
    public function sendRevocationStatus($sIdealoOrderNr, $sReason, $sComment = false)
    {
        $sUrl = $this->getRequestUrl(self::URL_TYPE_SEND_REVOCATION, $sIdealoOrderNr);
        $aParams = array();
        $aParams['reason'] = $sReason;
        if($sComment !== false) {
            $aParams['comment'] = $sComment;
        }
        return $this->sendCurlRequest($sUrl, $aParams);
    }
    
    protected function getJsonArrayFromRequest($sUrl)
    {
        $aArray = array();
        
        $sResponse = $this->sendCurlRequest($sUrl);
        if($sResponse === false) { // curl-error
            return false;
        } elseif($sResponse) {
            $aArray = json_decode($sResponse, true);
        }
        return $aArray;
    }
    
    protected function resetStatusProperties()
    {
        $this->setHttpStatus(null);
        $this->setCurlErrno(false);
        $this->setCurlError(false);
    }
    
    protected function sendCurlRequest($sUrl, $aParams = false, $blIsRetry = false) 
    {
        $this->resetStatusProperties();

        $oCurl = curl_init($sUrl);

        if($aParams !== false) {
            curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, json_encode($aParams));
        } else {
            curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, "GET");
        }
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 60); //timeout in seconds
        curl_setopt($oCurl, CURLOPT_HEADER, false);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        
        $sResponse = curl_exec($oCurl);

        $this->setHttpStatus(curl_getinfo($oCurl, CURLINFO_HTTP_CODE));     
        
        if(curl_error($oCurl) != '') {
            $this->setCurlError(curl_error($oCurl));
            $this->setCurlErrno(curl_errno($oCurl));
        }

        curl_close($oCurl);
        
        if($sResponse === false && $blIsRetry === false && $this->getCurlError() != '') {
            $sResponse = $this->sendCurlRequest($sUrl, $aParams, true);
        }
        
        if ( $this->getHttpStatus() != '200' ) {
            // API is down
			if ($this->getHttpStatus() == '502') {
                $this->setCurlError('API down');
            } else {
                $this->setCurlError('');
            }
            $sResponse = false;
        }
        return $sResponse;
    }

}