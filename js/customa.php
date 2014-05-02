<?php
// not use due to pb compatibility with smarty
header('Content-Type: text/javascript; charset=UTF-8');

$refP="a";

if (file_exists('../../../classes/db/Db.php'))
include_once('../../../classes/db/Db.php');

if (file_exists('../../../classes/Db.php'))
include_once('../../../classes/Db.php');

// include appropriate class file
if (file_exists('../lib/SimplifiedDBMysql.php'))
require_once("../lib/SimplifiedDBMysql.php");

//create object of class
$sdb = new SimplifiedDB();

/** Connect to database **/
$server = _DB_SERVER_;
$name = _DB_NAME_;
$pass = _DB_PASSWD_;
$user = _DB_USER_;
$prefix = _DB_PREFIX_;

//$sdb->dbConnect("localhost","root","","simplifieddb");

$sdb->dbConnect($server, $user, $pass, $name);


$sdb->like_cols = array("name" => "pig%");
$res = $sdb->dbSelect($prefix . "configuration", array("name", "value"));
$row = mysql_fetch_array($res);

while ($row = mysql_fetch_array($res)) {
    $data[$row['name']] = $row['value'];
}


echo ' var mct_Options =
{
    sliderId: "mcts'.$refP.'",
    direction: "horizontal",
    scrollInterval: ' . $data['pigreco'.$refP.'Interval'] . ',
    scrollDuration: ' . $data['pigreco'.$refP.'Duration'] . ',
    hoverPause: true,
    autoAdvance: ' . $data['pigreco'.$refP.'autoAdvance'] . ',
    scrollByEachThumb: ' . $data['pigreco'.$refP.'ByEachThumb'] . ',
    circular: true,
    largeImageSlider: null,
    inSyncWithLargeImageSlider: true,
    license: "mylicense"};';

$css = ".pigreco".$refP."-message {color: " . $data['pigreco'.$refP.'title_color'] . "; } ";
$css .= ".pigreco".$refP."-message {font-size: " . $data['pigreco'.$refP.'title_size'] . "; } ";
$css .= ".pigreco".$refP."-item-image-support a img{width: " . $data['pigreco'.$refP.'image_size'] . ";} ";
$css .= ".pigreco".$refP."-item-name a {font-size: " . $data['pigreco'.$refP.'desc_size'] . ";} ";
$css .= ".pigreco".$refP."-item-name {width: " . $data['pigreco'.$refP.'desc_width'] . ";} ";

$css .= ".pigreco".$refP."-item-sale-price {color: " . $data['pigreco'.$refP.'price_color'] . ";} ";
$css .= ".pigreco".$refP."-item-sale-price {font-size: " . $data['pigreco'.$refP.'price_size'] . ";} ";

$css .= ".pigreco".$refP."-item-price-discount {color: " . $data['pigreco'.$refP.'price_color_reg'] . ";} ";
$css .= ".pigreco".$refP."-item-price-discount {font-size: " . $data['pigreco'.$refP.'price_size_reg'] . ";} ";


echo "var css= \"" . $css . "\"";
