<?php
include(dirname(__FILE__) . "/JSON.php");

class PigdataApiClient
{
	var $clientId;
	var $key = "";	//	Fill your api key

	var $apiHost = "api.pigdata.net";

	var $useCache = false;
	var $cachePath = null;
	var $responseFormat = "json";

	function PigdataApiClient($clientId)
	{
		$this->clientId = $clientId;

		if ($this->useCache)
		{
			$this->cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

			if (!file_exists($this->cachePath))
				@mkdir($this->cachePath, 777);
		}
	}

	function getRecommendations($params)
	{
		if ($this->useCache && rand(0, 100) < 90)
		{
			$response = $this->getCacheResponse($params);

			if ($response)
				return $response;
		}

		$recommendationType = "similar";

		if (isset($params["recommendation"]["type"]))
			$recommendationType = $params["recommendation"]["type"];

		$apiServerUrl = "/v1/" . $this->clientId . "/reco/" . $recommendationType;

		$itemId = 0;

		if ($recommendationType == "similar" && isset($params["recommendation"]["item"]["id"]))
			$itemId = $params["recommendation"]["item"]["id"];
		else
			$itemId = $_COOKIE["pigdata_user_cart_lastQuotedItem"];

		$limit = 5;

		if (isset($params["recommendation"]["limit"]))
			$limit = $params["recommendation"]["limit"];

		$requestParams = array("query"=>"pub_products_id:" . $itemId, "defaultReco" => "1", "filterResponse" => "*", "max" => $limit, "pub_status" => 1);

		$requestParams["output"] = array("format" => $this->responseFormat);

		$response = $this->httpRequest("GET", $this->apiHost, 80, $apiServerUrl, $requestParams, null);

		$responseArray = $this->decode($response);

		if ($this->useCache)
		{
			if ($response && strlen($response) > 0 && $responseArray && isset($responseArray["itemsCount"]) && $responseArray["itemsCount"] > 0)
			{
				$this->putCacheResponse($params, $response);
			}
			else
			{
				$response = $this->getCacheResponse($params);
			}
		}

		return $responseArray;
	}

	function renderWidget($response)
	{

        $items = array();
		if (isset($response["response"]["docs"]))
			$items = $response["response"]["docs"];

		//$html = '<link rel="stylesheet" href="http://' . $this->apiHost . "/" . $this->clientId . "/widget1/" . $recommendationType . '.css"/>';
		$html = '<div id="pigdata-widget-similar">';
		$html .= '<div class="pigdata-similar">';
      	$html .= '    <div class="pigdata-paging">';
		$html .= '		  <div class="pigdata-paging-prev"></div>';
		$html .= '  	  <div class="pigdata-paging-next"></div>';
		$html .= '    </div>';
		$html .=    '  <div class="pigdata-message">';
		$html .=    '<table width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td height="14" class="infoBoxHeading"><img width="16" height="32" border="0" alt="" src="corner_right_left.gif"></td><td width="100%" height="14" class="infoBoxHeading">A découvrir également</td><td height="14" class="infoBoxHeading"><img width="11" height="14" border="0" alt="" src="pixel_trans.gif"></td>
  </tr></tbody></table>';
		$html .=    '  </div>';
		$html .=    '  <div class="pigdata-items">';
		$html .= '      <div class="pigdata-items-support">';

		if ($items && count($items) > 0)
		{
		  for($index = 0; $index < count($items); $index++)
		  {
		    $item = $items[$index];
		    $html .= '<div class="pigdata-item" products_id="' . $item["pub_products_id"] . '">';
		    $html .= '  <div class="pigdata-item-image-support">';
		    $html .= '	<a href="' . $item["pub_url"] . '">';
		    $html .= '  <img src="' . $item["pub_url_image"] . '"height="150"/>';
		    $html .= '  </a>';
		    $html .= '	</div>';
		    $html .= '	<div class="pigdata-item-summary">';
		    $html .= '   <div class="pigdata-item-name"><a href="' . $item["pub_url"] . '">' . $item["pub_name"] . '</a></div>';
		    $html .= '	</div>';
		    $html .= '	<div class="pigdata-clear"></div>';

		    $html .= '	<div class="pigdata-item-brand">';

		    if (isset($item["pub_manu"]))
		      $html .= strtoupper($item["pub_manu"]);

		    $html .= '	</div>';

		    $html .= '	<div class="pigdata-item-prices">';
		    $html .= '   <div class="pigdata-item-sale-price">';

		    if (isset($item["pub_price_promo"]))
		    {
		      if (!is_nan($item["pub_price_promo"]))
		        $html .= $this->formatPrice($item["pub_price_promo"]) . " &euro;";
		    }
		    else
		    {
		      if (!is_nan($item["pub_price"]))
		        $html .= $this->formatPrice($item["pub_price"]) . " &euro;";
		    }

		    $html .= '   </div>';

		    if (isset($item["pub_price_promo"]) && isset($item["pub_price"]) && $item["pub_price"] > $item["pub_price_promo"])
		    {
		      $html .= '<div class="pigdata-item-price-discount">';

		      if (!is_nan($item["pub_price"]))
			  	$html .= $this->formatPrice($item["pub_price"]) . " &euro;";

			  $html .= '</div>';
		    }

		    $html .= '	</div>';
		    $html .= '</div>';
		  }
		}
		else
		{
			$html .= '	<div class="pigdata-no-items">';
			$html .= 'Pas d\'article pour cette sélection';
			$html .= '	</div>';
		}

		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
        $html .= '</div>';

		return $html;
	}

	function getCurrentApiUrl()
	{
		return $this->apiUrl . $this->clientId . '/' . $this->apiId . '/';
	}

	function addKey(&$params)
	{
		$params["security"] = array("key"=>$this->key);
	}

	function addCookies(&$params)
	{
		foreach ($_COOKIE as $key => $value)
		{
			if (strpos($key, "pigdata_") === 0)
			{
				$parts = explode("_", $key);

				$arr = &$params;

				for($index = 1; $index < count($parts); $index++)
				{
					$part = $parts[$index];

					if ($index < count($parts) - 1)
					{
						if (!isset($arr[$part]))
							$arr[$part] = array();

						$arr = &$arr[$part];
					}
					else
						$arr[$part] = $value;
				}
			}
		}
	}

	function getCacheResponse($params)
	{
		if (isset($params["suggestion"]["query"]["item"]["id"]))
		{
			$itemId = $params["suggestion"]["query"]["item"]["id"];

			$cacheFilePath = $this->cachePath . "suggestion_" . $itemId . ".dat";

			if (file_exists($cacheFilePath))
			{
				$handle = fopen($cacheFilePath, "rb");
				$response = fread($handle, filesize($cacheFilePath));
				fclose($handle);

				if ($response)
				{
					$response = $this->decode($response);

					return $response;
				}
			}
		}

		return null;
	}

	function putCacheResponse($params, $response)
	{
		if (isset($params["suggestion"]["query"]["item"]["id"]))
		{
			$itemId = $params["suggestion"]["query"]["item"]["id"];

			$cacheFilePath = $this->cachePath . "suggestion_" . $itemId . ".dat";

			$handle = fopen($cacheFilePath, "w+");
			fwrite($handle, $response);

			fclose($handle);
		}
	}

	function decode($value)
	{
		if ($this->responseFormat == "json")
		{
			if (!function_exists('json_decode') )
			{
				$json = new Services_JSON();
				$value = (array)$json->decode($value);
				//$value = Zend_Json::decode($value);
			}
			else
				$value = json_decode($value, true);
		}
		else if ($this->responseFormat == "object")
		{
			$value = unserialize($value);
		}

		return $value;
	}

	function httpRequest(
						    $verb = 'GET',             /* HTTP Request Method (GET and POST supported) */
						    $host,                     /* Target IP/Hostname */
						    $port = 80,                /* Target TCP port */
						    $uri = '/',                /* Target URI */
						    $getdata = array(),        /* HTTP GET Data ie. array('var1' => 'val1', 'var2' => 'val2') */
						    $postdata = array(),       /* HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */
						    $cookie = array(),         /* HTTP Cookie Data ie. array('var1' => 'val1', 'var2' => 'val2') */
						    $custom_headers = array(), /* Custom HTTP headers ie. array('Referer: http://localhost/ */
						    $timeout = 1000,           /* Socket timeout in milliseconds */
						    $req_hdr = false,          /* Include HTTP request headers */
						    $res_hdr = false           /* Include HTTP response headers */
						  )
	{
	    $ret = '';
	    $verb = strtoupper($verb);
	    $cookie_str = '';
	    $getdata_str = count($getdata) ? '?' : '';
	    $postdata_str = '';

	    if ($getdata)
	    	$getdata_str .= $this->httpBuildQuery($getdata);

	   	if ($postdata)
	    	foreach ($postdata as $k => $v)
	        	$postdata_str .= urlencode($k) .'='. urlencode($v) .'&';

	    foreach ($cookie as $k => $v)
	        $cookie_str .= urlencode($k) .'='. urlencode($v) .'; ';

	    $crlf = "\r\n";
	    $req = $verb .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf;
	    $req .= 'Host: '. $host . $crlf;
	    $req .= 'User-Agent: Mozilla/5.0' . $crlf;
	    $req .= 'Accept: text/plain;q=0.9,*/*;q=0.8' . $crlf;
	    $req .= 'Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3' . $crlf;
	    $req .= 'Accept-Encoding: deflate' . $crlf;
	    $req .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' . $crlf;
        $req .= "Connection: Close" . $crlf;

	    foreach ($custom_headers as $k => $v)
	        $req .= $k .': '. $v . $crlf;

	    if (!empty($cookie_str))
	        $req .= 'Cookie: '. substr($cookie_str, 0, -2) . $crlf;

	    if ($verb == 'POST' && !empty($postdata_str))
	    {
	        $postdata_str = substr($postdata_str, 0, -1);
	        $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
	        $req .= 'Content-Length: '. strlen($postdata_str) . $crlf . $crlf;
	        $req .= $postdata_str;
	    }
	    else
	    {
	    	$req .= $crlf;
	    }

	    if ($req_hdr)
	        $ret .= $req;

	    if (($fp = @fsockopen($host, $port, $errno, $errstr)) == false)
	        return false;

	    if ($fp)
	    {
		    fputs($fp, $req);

	        stream_set_blocking($fp, false);
		    stream_set_timeout($fp, 0, $timeout * 1000);
		    $info = stream_get_meta_data($fp);

		    while (!feof($fp) && !$info['timed_out'])
		    {
		        $line = fread($fp, 1024);
		        $ret .= $line;

		        $info = stream_get_meta_data($fp);
		    }

		    $info = stream_get_meta_data($fp);
		    fclose($fp);
	    }

	    $unchunckResponse = false;

	    if (strpos(strtolower($ret), "transfer-encoding: chunked") !== false)
	    {
    		$unchunckResponse = true;
		}

	    if (!$res_hdr)
	        $ret = substr($ret, strpos($ret, "\r\n\r\n") + 4);

	    if($unchunckResponse)
	    	$ret = $this->unchunkHttp11($ret);

	    return $ret;
	}

	function unchunkHttp11($data)
	{
		$fp = 0;

		$outData = "";

    	while ($fp < strlen($data))
    	{
	        $rawnum = substr($data, $fp, strpos(substr($data, $fp), "\r\n") + 2);
	        $num = hexdec(trim($rawnum));
	        $fp += strlen($rawnum);
	        $chunk = substr($data, $fp, $num);
	        $outData .= $chunk;
	        $fp += strlen($chunk);
	    }

	    return $outData;
	}

	function httpBuildQuery($array = NULL, $convention = '%s')
	{
    	if (count($array) > 0)
    	{
	        if( function_exists( 'http_build_query' ) )
	        {
            	$query = http_build_query( $array );
        	}
        	else
        	{
				$query = '';

				foreach( $array as $key => $value )
				{
					if( is_array( $value ) )
					{
						$new_convention = sprintf( $convention, $key ) . '[%s]';
						$query .= $this->httpBuildQuery( $value, $new_convention );
					}
					else
					{
						$key = urlencode( $key );
						$value = urlencode( $value );

						$query .= sprintf( $convention, $key ) . "=$value&";
					}
				}
	        }

        	return $query;
    	}

    	return '';
	}

	function formatPrice($value)
	{
		$formattedValue = $value;

		$match = preg_match("/(\d+)(\.|,)?(\d+)?/", $value, $subparts);

		if ($match)
		{
			$decSeparator = ",";

			if (count($subparts) > 1)
				$formattedValue = $subparts[1];

			$formattedValue .= $decSeparator;

			$decPart = "";

			if (count($subparts) > 3)
			{
				$decPart = $subparts[3];
			}

			$formattedValue .= $this->padRight($decPart, "0", 2);
		}

		return $formattedValue;
	}

	public  function padRight($value, $padChar, $length)
	{
		for($index = strlen($value); $index < $length; $index++)
		{
			$value .= $padChar;
		}

		return $value;
	}

}