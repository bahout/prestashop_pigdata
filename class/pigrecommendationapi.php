<?php

if (!defined('_PS_VERSION_'))
    exit;

class PigRecommendationAPI
{

    /**
     * Visitor ID length
     *
     * @ignore
     */
    const LENGTH_VISITOR_ID = 16;

    private static $api_pigdata_product_url;
    private static $api_pigdata_reco_url;
    private static $api_pigdata;
    private static $webSiteId;

    public static function getInitial()
    {
        if (self::$api_pigdata_product_url == null or self::$api_pigdata_reco_url == null) {
            //
            $apiUrl = 'api.pigdata.net';

            $webSiteId = $_SERVER['HTTP_HOST'];
            $webSiteId = preg_replace('/www\./i', '', $webSiteId);
            $webSiteId = preg_replace('/[\s\W]+/i', '', $webSiteId);

            if ($webSiteId == 'localhost') {
                $webSiteId = 'testprestacom';
                //     $apiUrl = 'localhost:1234';
            }

            self::$api_pigdata_product_url = "http://" . $apiUrl . "/v1/" . $webSiteId . "/product";
            self::$api_pigdata_reco_url = "http://" . $apiUrl . "/v1/" . $webSiteId . "/";
            self::$api_pigdata = $apiUrl;
            self::$webSiteId = $webSiteId;
        }
    }

    static public function getRecommendationsIds($query, $apiKey, $options, $argu)
    { // $query = array("item"=>array("id"=>2), "filter"=>array("docType"=>"page"));

        $responsePig = self::getRecommendationsIdsBrut($query, $apiKey, $options, $argu);

        if ($responsePig) {
            $idProduct = $query['item']['id'];
            if (isset($responsePig['response']["docs"])) {
                $docs = $responsePig['response']["docs"];
                foreach ($docs as $key => $value) {
                    $itemIdsPig[] = $value['pub_products_id'];
                }
            }
        }
        return $itemIdsPig;
    }


    static public function getRecommendationsIdsBrut($query, $apiKey, $options, $argu)
    { // $query = array("item"=>array("id"=>2), "filter"=>array("docType"=>"page"));

        $response = null;
        $params = array("source" => array("host" => $_SERVER['HTTP_HOST'], "app" => array("name" => "prestashop", "version" => _PS_VERSION_)), "security" => array("key" => $apiKey));
        $params['filterResponse'] = '*';
        $params['filterQuery'] = 'pub_status:1';
        //$params['algoClient'] = $options['algoClient'];
        //$params['algoClient'] = 'default';
        $params['query'] = 'pub_products_id:' . $query['item']['id'];
        $uid = self::getVisitorId();

        //$params = array_merge($params,$filterResponse);
        //$rootUrl = null;
        $rootUrlPig = self::$api_pigdata_reco_url;
        $url = $rootUrlPig . $options['algoClient'] . '?' . http_build_query($params) . "&$argu&uid=$uid";
        $responsePig = PigREST::doRequest("GET", $url, null, "json");
        return $responsePig;
    }


    static public function putItems($chunkProducts, $apiKey)
    { // $chunkProducts[0] -> fields header
        $chunkProductsCSV = PigRecommendationAPI::getCSV($chunkProducts);
        return PigRecommendationAPI::putCSVItems($chunkProductsCSV, $apiKey);
    }

    static public function putCSVItems($chunkProductsCSV, $apiKey)
    {
        $datas = array("source" => array("app" => array("name" => "prestashop", "version" => _PS_VERSION_)), "datas" => array("items" => array("format" => "csv", "docs" => $chunkProductsCSV)));
        $params = array("source" => array("host" => $_SERVER['HTTP_HOST']), "security" => array("key" => ($apiKey)));

        $urlPig = self::$api_pigdata_product_url . '/stream' . '?' . http_build_query($params);

        //PigRecommendationAPI::log($apiKey, "Install", "Export Products", PigRecommendationAPI::LOGGING_MESSAGE_TYPE_INFO, "Send Datas Parameters", array("url" => $url, "params" => $params, "products" => array("count" => count($chunkProductsCSV))));
        $response2 = PigREST::doRequest("POST", $urlPig, $datas, "json");

        return $response2;
    }

    static public function deleteItems($itemsIds, $apiKey)
    {
        $params = array("source" => array("host" => $_SERVER['HTTP_HOST'], "app" => array("name" => "prestashop", "version" => _PS_VERSION_)), "security" => array("key" => ($apiKey)));
        $params["items"] = array();

        foreach ($itemsIds as $itemId) {
            $params["items"][] = array("id" => $itemId);
        }

        $url = self::$api_pigdata_product_url . "/delete/" . $itemId;

        $response = PigREST::doRequest("GET", $url, null, "json");

        return $response;
    }

    static public function getCSV($items)
    {
        $handle = fopen('php://temp/maxmemory:' . (intval(1 + (count($items) / 16)) * 1024 * 1024), 'r+'); // 1MB of memory allocated 

        foreach ($items as $item) {
            fputcsv($handle, $item);
        }
        rewind($handle);

        $output = stream_get_contents($handle);
        fclose($handle);

        return $output;
    }

    static public function pigdataTracking($data)
    {

        $addEcommerceItem = '';
        $addUiData = '';

        if ($data['page']['page_type'] == 'category') {
            $cat = $data['page']['page_name'];
            $pageType = $data['page']['page_type'];
            $setEcommerceView = "pigdataTracker.setEcommerceView(false,\"$pageType\",\" $cat \");";
        } elseif ($data['page']['page_type'] == 'product') {
            $product_id = $data['page']['product_id'];
            $pageType = $data['page']['page_type'];
            $setEcommerceView = "pigdataTracker.setEcommerceView(\"$product_id\",\"$pageType\",false)";
        } else {
            $pageType = $data['page']['page_type'];
            $setEcommerceView = "pigdataTracker.setEcommerceView(false,\"$pageType\",false)";
        }


        if (isset($data['cart'])) {
            $envoie = false;
            if(!isset($_COOKIE['productInCart'])){
            $_COOKIE['productInCart'] = "|";
            setcookie('productInCart',$_COOKIE['productInCart'], (time() + 3600));
            }
            if ($_COOKIE['productInCart']){

                foreach($data['cart'] AS $idProd){
                    if(is_numeric(strpos($_COOKIE['productInCart'],"|".$idProd."|"))==false){
                        $_COOKIE['productInCart'].=$idProd."|";
                        setcookie('productInCart',$_COOKIE['productInCart'], (time() + 3600));
                        $envoie = true;
                    }
                    else{
                        $envoie = false;
                    }
                }
            }
            if($envoie == true){
            foreach ($data['cart'] AS $product_id)
                $addEcommerceItem .= "pigdataTracker.addEcommerceItem(\"$product_id\",false,false,false,false); ";
                $addEcommerceItem .= "pigdataTracker.trackEcommerceCartUpdate('');";
            }
        }

        if (isset($data['objOrder'])) {
            foreach ($data['objOrder'] AS $product_id)
                $trackEcommerceOrder .= "pigdataTracker.trackEcommerceOrder(\"$product_id\",false,false,false,false); ";
            $trackEcommerceOrder .= "pigdataTracker.trackEcommerceCartUpdate('');";
        }

        if (isset($data['user'])) {
            $ud = $data['user'];
            $data = $ud['email'] . '|' . $ud['firstname'] . '|' . $ud['lastname'] . '|' . $ud['language'] . '|' . $ud['uid'];
            $uDt = self::_iEncrypt($data, 'abcdefghijklmnop', '0123456789123456');
            $addUiData = "pigdataTracker.setCustomVariable(1,'userData',\"$uDt\" ,'visit');";
        }

        $output = '
    <script type="text/javascript">
      var pkBaseURL = (("https:" == document.location.protocol) ? "https://' . self::$api_pigdata . '/" : "http://' . self::$api_pigdata . '/");
      document.write(unescape("%3Cscript src=\'" + pkBaseURL + "pigdata.js\' type=\'text/javascript\'%3E%3C/script%3E"));
    </script>
    <script type="text/javascript">
    try {
      var pigdataTracker = pigdata.getTracker(pkBaseURL + "usertracking/' . self::$webSiteId . '/", 2);
      ' . $setEcommerceView . ' 
      ' . $addEcommerceItem . '
      ' . $trackEcommerceOrder . ' 
      ' . $addUiData . ' 
      pigdataTracker.trackPageView();
      pigdataTracker.enableLinkTracking();
    } catch( err ) {}
    </script>
    <noscript><p><img src="http://' . self::$api_pigdata . '/usertracking/' . self::$webSiteId . '/?idsite=2' . '" style="border:0" alt=""/></p></noscript>';
        return $output;
    }

    /*
      public function getClientScriptUrl($itemId, $itemsIds, $apiKey, $views, $params = null) {
      $urlParams = array("source" => array("host" => $_SERVER['HTTP_HOST'], "app" => array("name" => "prestashop", "version" => _PS_VERSION_)), "security" => array("key" => $apiKey), "jsoncallback" => "_");

      $items = array();

      foreach ($itemsIds as $iterItemId) {
      $items[] = array("id" => $iterItemId);
      }

      $urlParams["suggestion"]["datas"] = array("item" => array("id" => $itemId), "items" => $items);

      $urlParams["suggestion"]["views"] = $views;

      if ($params)
      $urlParams = array_merge($urlParams, $params);

      $clientScriptUrl = PigRecommendationAPI::API_PLUGIN_URL . 'clientScript?' . http_build_query($urlParams);

      return $clientScriptUrl;
      }

      public static function log($apiKey, $category, $subCategory, $messageType, $message, $datas = null) {
      $params = array("source" => array("host" => $_SERVER['HTTP_HOST']), "security" => array("key" => $apiKey));

      $body = array("source" => array("app" => array("name" => "prestashop", "version" => _PS_VERSION_)));
      $body["log"] = array("date" => date("Y-m-d H:i:s"), "category0" => "Extension", "category1" => $category, "category2" => $subCategory, "message_type" => $messageType, "message" => $message, "datas" => $datas);

      $url = PigRecommendationAPI::API_LOGGER_URL . '?' . http_build_query($params);

      $response = PigREST::doRequest("PUT", $url, $body, "json");
      }
     * 
     */


    /**
     * If the user initiating the request has the  first party cookie,
     * this function will try and return the ID parsed from this first party cookie (found in $_COOKIE).
     *
     * If you call this function from a server, where the call is triggered by a cron or script
     * not initiated by the actual visitor being tracked, then it will return
     * the random Visitor ID that was assigned to this visit object.
     *
     * This can be used if you wish to record more visits, actions or goals for this visitor ID later on.
     *
     * @return string 16 hex chars visitor ID string
     */
    public static function getVisitorId()
    {
        /* if (!empty($this->forcedVisitorId)) {
             return $this->forcedVisitorId;
         }*/

        $idCookieName = 'id.' . '2' . '.';
        $idCookie = self::_getCookieMatchingName($idCookieName);
        if ($idCookie !== false) {
            $visitorId = substr($idCookie, 0, strpos($idCookie, '.'));
            if (strlen($visitorId) == self::LENGTH_VISITOR_ID) {
                return $visitorId;
            }
        }
        return $visitorId;
    }

    /**
     * Returns a first party cookie which name contains $name
     *
     * @param string $name
     * @return string String value of cookie, or false if not found
     * @ignore
     */
    protected static function _getCookieMatchingName($name)
    {
        // Piwik cookie names use dots separators in piwik.js,
        // but PHP Replaces . with _ http://www.php.net/manual/en/language.variables.predefined.php#72571
        $name = str_replace('.', '_', $name);
        foreach ($_COOKIE as $cookieName => $cookieValue) {
            if (strpos($cookieName, $name) !== false) {
                return $cookieValue;
            }
        }
        return false;
    }


    protected static function _iEncrypt($data, $key, $iv)
    {
        $blocksize = 16;
        $pad = $blocksize - (strlen($data) % $blocksize);
        $data = $data . str_repeat(chr($pad), $pad);
        return bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
    }

    protected static function _hex2bin($hex_string)
    {
        return pack('H*', $hex_string);
    }

    static public function clickTracking($argument)
    {

        /*$js = '
            <script type="text/javascript">
                var pkBaseURL = (("https:" == document.location.protocol) ? "https://' . self::$api_pigdata . '/" : "http://' . self::$api_pigdata . '/");
                document.write(unescape("%3Cscript src=\'" + pkBaseURL + "pigdata.js\' type=\'text/javascript\'%3E%3C/script%3E"));
            </script>
            <script type="text/javascript">

            $(document).ready(function() {
                try {
                    var pigdataTracker = pigdata.getTracker(pkBaseURL + "usertracking/' . self::$webSiteId . '/", 2);

                    var me = this;

                    $(".item").click(function()
                    {
                        me.onClickItem(this);
                    });

                    $(Unit.similar).bind("itemClicked", function(target, data)
                                                      {
                                                        pigdataTracker.setRequestMethod("POST");

                                                        pigdataTracker.setCustomVariable(1,data.item.pub_products_id, data.item.index,"page");
                                                        '.$fonction.'

                                                        var url = data.item.pub_url;

                                                        window.location.href = url;
                                                      });

                    onClickItem : function(element)
                    {
                    var itemElements = $(".item");

                    var itemIndex = -1;

                    for(var index = 0; index < itemElements.length; index++)
                    {
                        if (itemElements[index] == element)
                        {
                            itemIndex = index;
                            break;
                        }
                    }

                     if (itemIndex != -1)
                     {
                        var item = this.datas["items"][itemIndex];
                        item.index = itemIndex;

                        var me = this;

                        $(this).trigger("itemClicked", {item:item});
                     }
                    }
            })*/

        $js = '
           <script type="text/javascript">
           $(document).ready(function() {
               var me=this;
               $(".pigrecoa-item").click(function(){
               onClickItem(this);
            });
        });

        function onClickItem(element) {
            var itemElements = $(".pigrecoa-item");
            var itemIndex = -1;
            for(var index = 0; index < itemElements.length; index++)
               {
               if (itemElements[index] == element) {
                    itemIndex = index;
                       break;
                    }
               }
               if (itemIndex != -1)
                  {
                  var positionItem= itemIndex;
                  var id=element.id;
                  var arrId = id.split("|");
                  var productId=arrId[0];
                  var algoClient=arrId[1];

                  pigdataTracker.setRequestMethod("POST");
                  pigdataTracker.setCustomVariable(1,productId,positionItem,"page");
                  pigdataTracker.trackLink(algoClient,"pigReco");
                  //var url = data.item.pub_url;
                  //window.location.href = url;


                  }
               }
         </script>';

        return $js;


    }

}

//initialise this class
PigRecommendationAPI::getInitial();
?>
