<?php
if (!defined('_PS_VERSION_'))
	exit;
	
class PigREST
{	
	static public function doRequest($method, $url, $datas, $format="json", $compress=null)
    {
    	$response = false;
    	
		$options = array('http'=>array('method' => $method, 'header' => "Content-Type: text\r\n", 'timeout' => 5));
		
		if ($datas !== null) 
		{
			if ($method == "POST" || $method == "PUT")
			{
				if ($compress)
				{
					$options["http"]["docsCompress"] = $compress;
				}
				
				if (is_array($datas))
					$datas = json_encode($datas);
					
				$options["http"]["content"] = $datas;
			}
			else
    			$url .= "?" . http_build_query($params);
		}
    	
		$context = stream_context_create($options);
		
		$fp = fopen($url, 'rb', false, $context);
		
		if ($fp)
		{
		    $response = stream_get_contents($fp);
		}
			
		if ($response)
		{
			switch($format)
			{
				case "json" : 
					$response = json_decode($response, true);
					break;
					
				case "xml":
      				$response = simplexml_load_string($response);
      				break;
			}
		}
		
		fclose($fp);
		
		return $response;
    }
}
?>
