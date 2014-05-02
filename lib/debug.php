<?php
include(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
require_once('../pigrecoa.php');

ini_set('memory_limit','512M');

class Pigdata_Recommendation_DataController
{
    private $productHelper = null;
    private $apiKey = null;

    function __construct()
    {
        //error_reporting(-1);
    }

    public function testAccessAction()
    {
        //error_reporting(E_ALL);
        //ini_set('display_errors', 1);

        echo "allow_url_fopen :";
        var_dump(ini_get("allow_url_fopen"));

        $apiUrl = "http://api.pigdata.net/admin";

        echo "--------------------------------- Test 0 -------------------------------------<br/>";
        $test0 = file_get_contents($apiUrl);
        var_dump($test0);
        echo "<br/>";

        echo "--------------------------------- Test 1 -------------------------------------<br/>";
        $options = array('http' => array('method' => "GET", 'header' => "Content-Type: text\r\n", 'timeout' => 5));
        $context = stream_context_create($options);
        $fp = fopen($apiUrl, 'rb', false, $context);

        if ($fp) {
            $response = stream_get_contents($fp);
            var_dump($response);
            echo "<br/>";
        }

        echo "--------------------------------- Test 2 -------------------------------------<br/>";
        $apiHelper = Mage::helper('recommendation/api');

        OVNI9_Recommendation_Helper_Api::log($this->apiKey, "Test", "Test de log", OVNI9_Recommendation_Helper_Api::LOGGING_MESSAGE_TYPE_INFO, "Test logging");
    }

    public function phpinfoAction()
    {
        phpinfo();
    }

    public function exportAll()
    {
        $pig = new PigRecoa();
        $pig = $pig->sendDatabase();

    }

    public function process()
    {
        $updateUrl = "http://www.pigdata.net/plugins/prestashop/update/";

        $updatePath = dirname(__FILE__);
        $levelSearch = DIRECTORY_SEPARATOR;
        $pos = strrpos($updatePath, $levelSearch);
        $updatePath = substr($updatePath, 0, $pos + strlen($levelSearch));

        $this->processFiles($updateUrl, $updatePath);
    }


    private function processFiles($url, $path)
    {
        $files = $this->getFiles($url);

        foreach ($files as $file) {
            if ($file["isFolder"] == true) {
                if (!file_exists($path . $file["href"] . DIRECTORY_SEPARATOR)) {
                    mkdir($path . $file["href"] . DIRECTORY_SEPARATOR, 0755);
                }

                $this->processFiles($url . $file["href"] . '/', $path . $file["href"] . DIRECTORY_SEPARATOR);
            } else {
                if (strcasecmp($file["href"], "debug.php") != 0) {
                    $getContentsUrl = "http://www.pigdata.net/getUpdateContents.php?path=" . urlencode($file["fullHref"]);
                    $contents = file_get_contents($getContentsUrl);

                    if (substr($file["href"], -4) != ".php") {
                        echo "Update : " . $path . $file["href"] . "<br/>";
                        if (substr($file["href"], -5) == ".phpa") {
                            $file["href"]= substr_replace($file["href"], "", -1);
                        }
                        file_put_contents($path . $file["href"], $contents);
                    }
                }
            }
        }
    }

    private function getFiles($url)
    {
        $htmlContent = file_get_contents($url);

        $rowTag = "<tr>";
        $rowEndTag = "</tr>";
        $tdTag = "<td";
        $tdEndTag = "</td>";

        $hrefAttributeSearch = 'href="';

        $searchAnchorTag = "<a";
        $endTag = ">";
        $searchEndAnchorTag = "<a";

        $htmlParts = explode($rowTag, $htmlContent);

        $rows = array();

        $htmlParts = array_slice($htmlParts, 1);

        foreach ($htmlParts as $htmlPart) {
            $parts = explode($rowEndTag, $htmlPart);
            $rows[] = $parts[0];
        }

        $files = array();

        $rows = array_slice($rows, 3);

        $rows = array_slice($rows, 0, count($rows) - 1);

        foreach ($rows as $row) {
            $file = array("isFolder" => false);

            $pos = strpos($row, $tdTag);

            $left = strpos($row, $endTag, $pos);

            if ($left !== false) {
                $left++;

                $right = strpos($row, $tdEndTag, $left);

                if ($right !== false) {
                    $right--;
                    $imgElement = substr($row, $left, $right - $left + 1);

                    if (strpos($imgElement, "folder") !== false) {
                        $file["isFolder"] = true;
                    }
                }
            }

            $pos = strpos($row, $tdTag, $right);

            $left = strpos($row, $endTag, $pos);

            if ($left !== false) {
                $left++;

                $right = strpos($row, $tdEndTag, $left);

                if ($right !== false) {
                    $right--;
                    $linkElement = substr($row, $left, $right - $left + 1);

                    $pos = strpos($linkElement, $hrefAttributeSearch);

                    if ($pos !== false) {
                        $left = $pos + strlen($hrefAttributeSearch);

                        $right = strpos($linkElement, '"', $left);

                        if ($right !== false) {
                            $right--;
                            $href = substr($linkElement, $left, $right - $left + 1);

                            $file["fullHref"] = $url . $href;

                            if (substr($href, strlen($href) - 1, 1) == "/") {
                                $href = substr($href, 0, strlen($href) - 1);
                            }

                            $file["href"] = $href;
                        }
                    }
                }
            }

            $files[] = $file;
        }

        return $files;
    }
}

$ctrl = new Pigdata_Recommendation_DataController;
$action = $_GET["action"];

if ($action == 'phpinfo') {
    $ctrl->phpinfoAction();
}
if ($action == 'testapi') {
    $ctrl->testAccessAction();
}
//TODO NB ne marche pas si les permissions ne sont pas bonnes.
if ($action == 'exportAll') {
    $ctrl->exportAll();
}
if ($action == 'process') {
    $ctrl->process();
}


