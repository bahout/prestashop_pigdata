<?php

/* Prestashop Pig Recommendation module main file
 * Contains configuration interface, and actions
 */
 
error_reporting (E_ALL ^ E_NOTICE); 

if (!defined('_PS_VERSION_'))
    exit;

global $inclusion;
//global $debug;
// API and REST interfaces for communication with Pig servers
// API and REST interfaces for communication with Pig servers
if ($inclusion == false) {
    include_once('class/pigrecommendationapi.php');
    include_once('class/pigrest.php');
    //include_once("PigdataApiClient.php");
    $inclusion = true;
}

// Types to log
if (($_GET["debug"])) {
    include_once(dirname(__FILE__) . "/lib/logbase.php");
    include_once(dirname(__FILE__) . "/lib/Console.php");

    // Example of the different log types
    Console::show($debug, Console::INFO);


    $LogBase = new LogBase();

    $LogBase->enable_error(true, E_ALL ^ E_NOTICE);
    $LogBase->enable_fatal();
    $LogBase->enable_exception();

// Log methods
    $LogBase->enable_method_file(true, array('path' => dirname(__FILE__) . '/log/'));
    $LogBase->enable_method_print();

    $debug = true;
}





class PigRecoa extends Module
{
    var $refP = "a";
    static $template = "pigfootertemplate_etoffe_a.tpl";
    static $cssperso = "-etoffe.css";

    //1 FV -- Called when module is loaded to Prestashop
    function __construct()
    {
        $this->refP = "a";
        $this->name = 'pigreco' . $this->refP;
        $this->version = '0.2';
        $this->author = 'Nicolas Bahout';
        $this->tab = 'front_office_features';
        global $statDone;

        $this->_postErrors = array();

        parent::__construct();

        $this->displayName = $this->l('Pig Recommendation Engine Position ' . $this->refP);
        $this->description = $this->l('Pig Recommendations - CrossSelling - UpSelling');
    }

    // Called when the module is installed
    function install()
    {
        if ($this->refP == 'a') {
            $hookToInstall = 'productfooter';
        } elseif ($this->refP == 'b') {
            $hookToInstall = 'shoppingCart';
        }

        if (!parent::install()
            OR !$this->registerHook('header')
            OR !$this->registerHook('footer')
            OR !$this->registerHook('addproduct')
            OR !$this->registerHook('updateproduct')
            OR !$this->registerHook('deleteproduct')
            //OR !$this->registerHook('shoppingCartExtra')
            //OR !$this->registerHook('orderConfirmation')
            //OR !$this->registerHook('shoppingCart')
            OR !$this->registerHook($hookToInstall)
            OR !$this->registerHook('cart')



            // The module isn't hooked by default ; Uncomment to set default locations
            //OR !$this->registerHook('leftColumn')
            //OR !$this->registerHook('rightColumn')
            //OR !$this->registerHook('productFooter')
            OR !$this->setConfig('apikey', '')
            OR !$this->setConfig('techno', '1')
            OR !$this->setConfig('nb_recomm_col', '5')
            OR !$this->setConfig('nb_recomm_foo', '5')
            OR !$this->setConfig('box_col_title', $this->l('Vous pourriez également être intéressé par le(s) produit(s) suivant(s)'))
            OR !$this->setConfig('box_foo_title', $this->l('Vous pourriez également être intéressé par le(s) produit(s) suivant(s)'))
            OR !$this->setConfig('algo', 'reco/similar')
            OR !$this->setConfig('argu', 'filterResponse=*')
            OR !$this->setConfig('title_color', '#000000')
            OR !$this->setConfig('title_size', '15px')
            OR !$this->setConfig('autoAdvance', 'true')
            OR !$this->setConfig('Interval', '1400')
            OR !$this->setConfig('Duration', '800')
            OR !$this->setConfig('ByEachThumb', 'true')
            OR !$this->setConfig('image_size', '120px')
            OR !$this->setConfig('desc_size', '12px')
            OR !$this->setConfig('desc_width', '133px')
            OR !$this->setConfig('price_size', '18px')
            OR !$this->setConfig('price_color', '#36B5CC')
            OR !$this->setConfig('price_size_reg', '13px')
            OR !$this->setConfig('price_color_reg', '#AFAFAF')

        )
            return false;
        return true;
    }

    // Called when the module is uninstalled
    public function uninstall()
    {
        $this->deleteConfig('apikey');
        $this->deleteConfig('techno');
        $this->deleteConfig('nb_recomm_col');
        $this->deleteConfig('nb_recomm_foo');
        $this->deleteConfig('box_col_title');
        $this->deleteConfig('box_foo_title');
        $this->deleteConfig('algo');
        $this->deleteConfig('argu');
        $this->deleteConfig('title_color');
        $this->deleteConfig('title_size');
        $this->deleteConfig('autoAdvance');
        $this->deleteConfig('Interval');
        $this->deleteConfig('Duration');
        $this->deleteConfig('ByEachThumb');
        $this->deleteConfig('image_size');
        $this->deleteConfig('desc_size');
        $this->deleteConfig('desc_width');
        $this->deleteConfig('price_size');
        $this->deleteConfig('price_color');
        $this->deleteConfig('price_size_reg');
        $this->deleteConfig('price_color_reg');

        parent::uninstall();

        return true;
    }

    // Generate recommendations for the current product
    public function generateRecommendations($params)
    {
        global $smarty, $link, $cookie;
        $pdb = null;

        $pid = (int)(Tools::getValue('id_product'));

        if (empty($pid) & !empty($params)) {
            $pid = $params;
        }


        // Triggered only on product pages
        if (!empty($pid)) {

            $frlang = Language::getIdByIso('fr');
            $apikey = $this->getConfig('apikey');
            $argu = $this->getConfig('argu');
            $nbcol = intval($this->getConfig('nb_recomm_col'));
            $nbfoo = intval($this->getConfig('nb_recomm_foo'));
            $algo = $this->getConfig('algo');
            $nbr = max($nbcol, $nbfoo);
            $techno = $this->getConfig('techno');


            // Build query, send it to API, retrieve results
            $query = array("item" => array("id" => $pid),
                "filter" => array("docType" => 'item'),
                "select" => array("offset" => 0, "limit" => $nbr, "fields" => array('id')));
            $option['algoClient'] = $algo;
            $res = PigRecommendationAPI::getRecommendationsIds($query, $apikey, $option, $argu);

            // Get data associated to retrieved ids
            $pdb = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
				SELECT i.id_image, p.id_product,p.*, pl.link_rewrite, pl.name, cl.link_rewrite AS category_rewrite
				FROM ' . _DB_PREFIX_ . 'product p
				LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (pl.id_product = p.id_product)
				LEFT JOIN ' . _DB_PREFIX_ . 'image i ON (i.id_product = p.id_product AND i.cover = 1)
				LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category = p.id_category_default)
				WHERE p.id_product IN (' . implode(',', $res) . ')
				AND pl.id_lang = ' . $frlang . '
				AND cl.id_lang = ' . $frlang
            );

            $defaultCover = 'fr-default';
            foreach ($pdb as $k => $cp) {
                $color=array();
                if (!empty($cp['id_image'])) {
                    $cover = $cp['id_product'] . '-' . $cp['id_image'];
                } else {
                    $cover = $defaultCover;
                }
                if (_PS_VERSION_ >= 1.4) {
                    $allCategories = Product::getProductCategoriesFull($cp['id_product'], $cookie->id_lang);
                    sort($allCategories);
                    $categories = str_replace('"', '', end($allCategories));

                    //get color
                    $product = new Product (intval($cp['id_product']), true, intval($cookie->id_lang));
                    $attributesGroups = $product->getAttributesGroups($cookie->id_lang);
                    foreach ($attributesGroups as $indice => $attribute) {
                        if(isset($attribute['attribute_color']))
                        {
                            $color[]=$attribute['attribute_color'];
                        }
                    }
                }


                if (_PS_VERSION_ < 1.4) {
                    $pdb[$k]['price'] = Product::getPriceStatic($cp['id_product'], true, null, 2, null, false, true) . " €";
                    //(Product::getPriceStatic($cp['id_product']));
                } else {
                    $pdb[$k]['price'] = Product::convertAndFormatPrice(Product::getPriceStatic($cp['id_product']));
                }
                $pdb[$k]['link'] = $link->getProductLink($cp['id_product'], $cp['link_rewrite'], $cp['category_rewrite']);
                $pdb[$k]['productid'] = $cp['id_product'];
                if (_PS_VERSION_ >= 1.5) {
                    $pdb[$k]['image'] = $link->getImageLink($cp['link_rewrite'], $cover, 'medium_default');
                    $pdb[$k]['imagehome'] = $link->getImageLink($cp['link_rewrite'], $cover, 'home_default');
                    $pdb[$k]['imagecategory'] = $link->getImageLink($cp['link_rewrite'], $cover, 'category_default');
                    $pdb[$k]['imagepopuplarge'] = $link->getImageLink($cp['link_rewrite'], $cover, 'popup_large_default');

                } else {
                    $pdb[$k]['image'] = $link->getImageLink($cp['link_rewrite'], $cover, 'medium');
                    $pdb[$k]['imagehome'] = $link->getImageLink($cp['link_rewrite'], $cover, 'home');
                    $pdb[$k]['imagecategory'] = $link->getImageLink($cp['link_rewrite'], $cover, 'category');
                    $pdb[$k]['imagepopuplarge'] = $link->getImageLink($cp['link_rewrite'], $cover, 'popup_large');
                }

                $pdb[$k]['categoryname'] = $categories['name'];
                $pdb[$k]['color'] = $color;


                if (_PS_VERSION_ < 1.4) {
                    $priceWithoutReduction = Product::getPriceStatic($cp['id_product'], true, null, 2, null, false, false) . " €";
                } else {
                    $priceWithoutReduction = Product::convertAndFormatPrice(Product::getPriceStatic($cp['id_product'], true, null, 6, null, false, false));
                }

                if ($priceWithoutReduction != $pdb[$k]['price']) {
                    $pdb[$k]['priceWithout'] = $priceWithoutReduction;
                }
            }


            // Send information to template if recommandation is find
            if ($pdb != false) {
                $smarty->assign(array(
                        'colmsg' => $this->getConfig('box_col_title'),
                        'foomsg' => $this->getConfig('box_foo_title'),
                        'colpdb' => array_slice($pdb, 0, $nbcol),
                        'foopdb' => array_slice($pdb, 0, $nbfoo),
                        'mediumSize' => Image::getSize('medium'),
                        'templatenum' => $this->refP,
                        'jsToAdd' => $this->creatCustomCssAndJs(),
                        'algo' => $this->_getAlgoClient(),
                        'clickTracking' => $this->clickTracking(),
                        'version' => _PS_VERSION_,
                        'middle_pos' => round(sizeof($pdb) / 2, 0)
                    )
                );
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //2 in FV
    public function hookHeader()
    {
        global $headerJsDone;
        $output = null;
        $techno = $this->getConfig('techno');

        if(($techno == 1)&&($headerJsDone != true))
        {
            $webSiteId = $_SERVER['HTTP_HOST'];
            $webSiteId = preg_replace('/www\./i', '', $webSiteId);
            $webSiteId = preg_replace('/[\s\W]+/i', '', $webSiteId);
            if ($webSiteId == 'localhost') {
                $webSiteId = 'testprestacom';
            }
            $headerJsDone = true;
            $output = '<script async="async" src="http://api.pigdata.net/'.$webSiteId.'/widget1/app.js" type="text/javascript"></script>';
        }
        else
        {
//css js
            if (_PS_VERSION_ >= 1.5) {
                Tools::addCSS(($this->_path) . 'css/pigreco' . $this->refP . self::$cssperso, 'all');
            } elseif (_PS_VERSION_ >= 1.4) {
                Tools::addCSS(($this->_path) . 'css/pigreco' . $this->refP . self::$cssperso, 'all');
            } elseif (_PS_VERSION_ >= 1.3) {
                //return $this->display(__FILE__, $this->name . self::$template);
            }

    //add JS
            if ($headerJsDone != true) {
                if (_PS_VERSION_ >= 1.5) {
                    $this->context->controller->addJS(($this->_path) . 'js/pig.js');
                } elseif (_PS_VERSION_ >= 1.4) {
                    Tools::addJS(($this->_path) . 'js/pig.js');
                } elseif (_PS_VERSION_ >= 1.3) {
                }
                $headerJsDone = true;
            }
        }

        return $output;

    }


    public function hookRightColumn($params)
    {
        $techno = $this->getConfig('techno');
		if ($_GET["debug"]) {
    		Console::show($techno, Console::INFO);
		}
		
		
        //if ($this->generateRecommendations($params) && $techno != 1 ) {
        //    return $this->display(__FILE__, self::$template);
        //}
    }

    public function hookLeftColumn($params)
    {
        return $this->hookRightColumn($params);
    }

    /**
     * Template des recommandations du bas
     * @param type $params
     * @return type
     */
    public function hookProductFooter($params)
    {
        //@todo Afficher une div pour le referencement de pigdata
        $techno = $this->getConfig('techno');
		if ($_GET["debug"]) {
    		Console::show($techno, Console::INFO);
		}
        //if ($this->generateRecommendations($params) && $techno != 1 ) {
        //    return $this->display(__FILE__, self::$template);
        //}
    }

    public function hookshoppingCart($params)
    {

        //in cart
        $cart = $params['cart'];
        $products = $cart->getProducts();
        //array of in cart product
        $productId = $this->_getQueryProduct($products);


        return $this->hookProductFooter($productId[0]);
    }

    public function hookAddproduct($params)
    {
        $frlang = Language::getIdByIso('fr');
        $apikey = $this->getConfig('apikey');
        $p = new Product($params['product']->id, true, $frlang);
        if (!empty($frlang)) {
            $db = array();
            $db[] = self::genDataRow(null);
            $db[] = self::genDataRow($p);
            PigRecommendationAPI::putItems($db, $apikey);
            return true;
        } else {
            return false;
        }
    }

    public function hookUpdateproduct($params)
    {
	return true;
        //return $this->hookAddproduct($params);
    }

    public function hookDeleteproduct($params)
    {
        $apikey = $this->getConfig('apikey');
        $p = $params['product'];
        return PigRecommendationAPI::deleteItems(array($p->id), $apikey);
    }

    private function _postProcess()
    {
        if (Validate::isUnsignedInt(Tools::getValue("nb_recomm_col"))) {
            $this->setConfig("nb_recomm_col", Tools::getValue("nb_recomm_col"));
        }

        if (Validate::isUnsignedInt(Tools::getValue("nb_recomm_foo"))) {
            $this->setConfig("nb_recomm_foo", Tools::getValue("nb_recomm_foo"));
        }

        $this->setConfig("apikey", Tools::getValue("apikey"));
        $this->setConfig("techno", (int)Tools::getValue("techno"));
        $this->setConfig("title_color", Tools::getValue("title_color"));
        $this->setConfig("title_size", Tools::getValue("title_size"));
        $this->setConfig("autoAdvance", Tools::getValue("autoAdvance"));

        $this->setConfig("Interval", Tools::getValue("Interval"));
        $this->setConfig("Duration", Tools::getValue("Duration"));
        $this->setConfig("ByEachThumb", Tools::getValue("ByEachThumb"));

        $this->setConfig("image_size", Tools::getValue("image_size"));
        $this->setConfig("desc_size", Tools::getValue("desc_size"));
        $this->setConfig("desc_width", Tools::getValue("desc_width"));

        $this->setConfig("price_size", Tools::getValue("price_size"));
        $this->setConfig("price_color", Tools::getValue("price_color"));

        $this->setConfig("price_size_reg", Tools::getValue("price_size_reg"));
        $this->setConfig("price_color_reg", Tools::getValue("price_color_reg"));

        if (Validate::isMessage(Tools::getValue("box_col_title"))) {
            $this->setConfig("box_col_title", Tools::getValue("box_col_title"));
        }

        if (Validate::isMessage(Tools::getValue("box_foo_title"))) {
            $this->setConfig("box_foo_title", Tools::getValue("box_foo_title"));
        }


        if (Validate::isMessage(Tools::getValue("algo"))) {
            $this->setConfig("algo", Tools::getValue("algo"));
        }

        if (Validate::isMessage(Tools::getValue("argu"))) {
            $this->setConfig("argu", Tools::getValue("argu"));
        }

        $this->_html .= '<div class="conf confirm">' . $this->l('Settings updated') . '</div>';
    }

    /**
     * process data after submit in modul area
     * @return type html form for admin
     */
    public function getContent()
    {
        $this->_html .= "<h2>" . $this->displayName . "</h2>";

        if (Tools::isSubmit("saveConfig")) {
            $this->_postProcess();
        } elseif (Tools::isSubmit("sendAll")) {
            $this->_postProcess();
            $this->sendDatabase();
        }

        $this->_displayForm();

        return $this->_html;
    }

    private function _displayForm()
    {
        //$test = Tools::getValue('always_display', Configuration::get('debugMode'));
        $this->_html .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">

			<fieldset>
				<legend><img src="' . $this->_path . 'logo.gif" alt="" class="middle" />' . $this->l('API Settings') . '</legend>
				<label>' . $this->l('User security key') . '</label>
				<div class="margin-form">
					<input type="text" name="apikey" value="' . $this->getConfig("apikey") . '" size="50" />
				</div>
                                <label>' . $this->l('Algo') . '</label>
				<div class="margin-form">
					<input type="text" name="algo" value="' . $this->getConfig("algo") . '" size="20" />
				</div>
                                <label>' . $this->l('Argument') . '</label>
				<div class="margin-form">
					<input type="text" name="argu" value="' . $this->getConfig("argu") . '" size="100%" />
				</div>
				<label>Mode Javascript :</label>
				    <div class="margin-form">
				        <input type="checkbox" name="techno" id="techno" style="vertical-align: middle;" value="1" '.($this->getConfig('techno') ? 'checked="checked"' : '').' />
				    </div>
				<label>' . $this->l('Send database to API') . '</label>
				<div class="margin-form">
					<input type="submit" name="sendAll" value="' . $this->l('Run') . '" class="button" />
				</div>
			</fieldset>
			<br/>
			<fieldset>
				<legend><img src="' . $this->_path . 'logo.gif" alt="" class="middle" />' . $this->l('Column Block Settings') . '</legend>
				<label>' . $this->l('Block title') . '</label>
				<div class="margin-form">
					<input type="text" name="box_col_title" value="' . $this->getConfig("box_col_title") . '" size="50" />
				</div>		
				<br />
				<label>' . $this->l('Number of recommendations') . '</label>
				<div class="margin-form">
					<input type="text" name="nb_recomm_col" value="' . $this->getConfig("nb_recomm_col") . '" size="3" />
				</div>
			</fieldset>
			<br/>
            <fieldset>
				<legend><img src="' . $this->_path . 'logo.gif" alt="" class="middle" />' . $this->l('Product Footer Block Settings') . '</legend>
				<label>' . $this->l('Block title') . '</label>
				<div class="margin-form">
					<input type="text" name="box_foo_title" value="' . $this->getConfig("box_foo_title") . '" size="50" />
				</div>		
				<br />
				<label>' . $this->l('Number of recommendations') . '</label>
				<div class="margin-form">
					<input type="text" name="nb_recomm_foo" value="' . $this->getConfig("nb_recomm_foo") . '" size="3" />
				</div>
			</fieldset>
			<br/>
			<fieldset>
				<legend><img src="' . $this->_path . 'logo.gif" alt="" class="middle" />' . $this->l('Advance Style Settings') . '</legend>

				<label>' . $this->l('Auto Rotate') . '</label>
			    <div class="margin-form">
					<input type="text" name="autoAdvance" value="' . $this->getConfig("autoAdvance") . '" size="20" />
				</div>
				<label>' . $this->l('scroll Interval') . '</label>
			    <div class="margin-form">
					<input type="text" name="Interval" value="' . $this->getConfig("Interval") . '" size="20" />
				</div>
				<label>' . $this->l('scroll Duration') . '</label>
			    <div class="margin-form">
					<input type="text" name="Duration" value="' . $this->getConfig("Duration") . '" size="20" />
				</div>
				<hr/>
				<label>' . $this->l('scroll By Each Thumb') . '</label>
			    <div class="margin-form">
					<input type="text" name="ByEachThumb" value="' . $this->getConfig("ByEachThumb") . '" size="20" />
				</div>
				<label>' . $this->l('Color Title') . '</label>
				<div class="margin-form">
					<input type="text" name="title_color" value="' . $this->getConfig("title_color") . '" size="20" />
				</div>
				<label>' . $this->l('Size Title') . '</label>
				<div class="margin-form">
					<input type="text" name="title_size" value="' . $this->getConfig("title_size") . '" size="20" />
				</div>
				<label>' . $this->l('Image Size ') . '</label>
				<div class="margin-form">
					<input type="text" name="image_size" value="' . $this->getConfig("image_size") . '" size="20" />
				</div>
				<label>' . $this->l('Description Size') . '</label>
				<div class="margin-form">
					<input type="text" name="desc_size" value="' . $this->getConfig("desc_size") . '" size="20" />
				</div>
			    <label>' . $this->l('Description Width') . '</label>
				<div class="margin-form">
					<input type="text" name="desc_width" value="' . $this->getConfig("desc_width") . '" size="20" />
				</div>
			    <label>' . $this->l('Price Size') . '</label>
				<div class="margin-form">
					<input type="text" name="price_size" value="' . $this->getConfig("price_size") . '" size="20" />
				</div>
				<label>' . $this->l('Price Color') . '</label>
				<div class="margin-form">
					<input type="text" name="price_color" value="' . $this->getConfig("price_color") . '" size="20" />
				</div>
			    <label>' . $this->l('Price Size With Reduction') . '</label>
				<div class="margin-form">
					<input type="text" name="price_size_reg" value="' . $this->getConfig("price_size_reg") . '" size="20" />
				</div>
				<br/>
				<label>' . $this->l('Price Color With Reduction') . '</label>
				<div class="margin-form">
					<input type="text" name="price_color_reg" value="' . $this->getConfig("price_color_reg") . '" size="20" />
				</div>
			</fieldset>
             <br/>
             <center><input type="submit" name="saveConfig" value="' . $this->l('Save settings') . '" class="button" /></center>
		</form>';
    }

    /**
     * Send all articles to the API
     * @return API response
     */
    public function sendDatabase()
    {
        $frlang = (int) (Language::getIdByIso('fr') ? Language::getIdByIso('fr') : 2);
        $apikey = $this->getConfig('apikey');
        if (!empty($frlang)) {
            $list = Product::getSimpleProducts($frlang);
            $db = array();
            $db[] = self::genDataRow(null);
            foreach ($list as $elt) {
                $prod = new Product($elt['id_product'], true, $frlang);
                $db[] = self::genDataRow($prod);
            }
            $status = PigRecommendationAPI::putItems($db, $apikey);
            //if ($status['status'] == 'ok') {
            $this->_html .= '<div class="conf confirm">' . $this->l('Database sent successfully') . '</div>';
            ///} else {
            // $this->_html .= '<div class="alert error">' . $this->l('Database not sent') . '</div>';
            //}
            return true;
        } else {
            return false;
        }
    }

    /**
     * Generate a row to send to the API with a product
     * @param $p Product used as source
     * @return The row to send to the API with the good fields
     */
    private static function genDataRow($p)
    {
        if (_PS_VERSION_ < 1.5) {
            global $link;
        }
        $frlang = Language::getIdByIso('fr');
        if (empty($p)) {
            // If empty, return the key row
            return array('pub_products_id',
                'pub_type',
                'pub_name',
                'pub_url_image',
                'pub_reference',
                'pub_ean',
                'pub_description',
                'pub_descriptionShort',
                'pub_price_promo',
                'pub_price',
                'pub_url',
                'meta_title',
                'pub_metaKeywords',
                'meta_description',
                'is_in_stock',
                'pub_stockQuantity',
                'date_add',
                'date_upd',
                'pub_category0',
                'pub_category1',
                'pub_category2',
                'pub_category',
                'pub_tags',
                'pub_manu',
                'pub_status'
            );
        } else {

            $catTree = array_fill(0, 3, array());
            $explored = array();

            if (_PS_VERSION_ < 1.4) {
                $catIds = $p->getDefaultCategoryProducts();
            } else {
                $catIds = $p->getCategories();
            }
            $i = 0;
            foreach ($catIds as $catId) {
                $cat = new Category($catId, $frlang);
                if (_PS_VERSION_ >= 1.5) {

                    $listCat[$cat->level_depth - 1][$i++] = $cat->name;
                } else {
                    $parents = array_reverse($cat->getParentsCategories($frlang));
                    for ($i = 0; $i < min(3, count($parents)); $i++) {
                        $current = $parents[$i];
                        if (!empty($current)) {
                            if (!in_array($current['name'], $explored)) {
                                $catTree[$i][] = $current['name'];
                                $explored[] = $current['name'];
                            }
                        }
                    }

                }

            }

            if (_PS_VERSION_ >= 1.5) {
                if ($listCat[0] != null)
                    $cats[0] = implode('|', $listCat[0]);

                if ($listCat[1] != null)
                    $cats[1] = implode('|', $listCat[1]);

                if ($listCat[2] != null)
                    $cats[2] = implode('|', $listCat[2]);
            } else {

                //pas propre... mais bon
                $cats = array_map('pigReco' . self::$refP . '::toFormat', $catTree);
                foreach ($cats as $key => $value) {
                    if ($value == "") {
                        unset($cats[$key]);
                    }
                }
                $cats = array_values($cats);
                $cats = explode('|', $cats[0]);
                $catsPath = implode('|', $cats);
            }

            $tags = implode('|', explode(', ', $p->getTags($frlang)));

            if (_PS_VERSION_ >= 1.5) {
                $context = Context::getContext();
                $context->link = new Link();
                $img = $context->link->getImageLink($p->link_rewrite, $p->id . '-' . $p->getCoverWs(), 'large');
                //$img = $this->context->link->getImageLink($p->link_rewrite, $p->id . '-' . $p->getCoverWs(), 'large_default');

                $productlink = $context->link->getProductLink($p->id); //get the products url link
            } elseif (_PS_VERSION_ >= 1.4) {
                $img = $link->getImageLink($p->link_rewrite, $p->id . '-' . $p->getCoverWs(), 'large');
                $productlink = $link->getProductLink($p->id); //get the products url link
            } else {
                $img = $link->getImageLink($p->link_rewrite, $p->id . '-' . $p->getCover($p->id), 'large');
                $productlink = $link->getProductLink($p->id); //get the products url link
            }

            $desc = strip_tags($p->description);
            $sdesc = strip_tags($p->description_short);

            return array($p->id, // ID
                'simple', // Type
                $p->name, // Name
                $img, // Image URL
                $p->reference, // SKU (?)
                $p->ean13, // SKU (?)
                preg_replace('/\s\s+/', ' ', strip_tags($desc)), // Description
                preg_replace('/\s\s+/', ' ', strip_tags($sdesc)), // Short description
                $p->price, // Price
                Product::getPriceStatic($p->id, true, null, 6, null, false, false),
                //$p->link_rewrite, // URL
                $productlink, // URL
                $p->meta_title, // Meta Title of product
                $p->meta_keywords, // Meta Keywords
                $p->meta_description, // Meta Description
                $p->quantity == 0 ? 0 : 1, // Is in stock
                $p->quantity, // Quantity
                $p->date_add, // Date of creation
                $p->date_upd, // Date of update
                $cats[0], // First-level categories
                $cats[1], // Second-level categories
                $cats[2], // Third-level categories
                $catsPath,
                $tags, // Tags
                $p->manufacturer_name, // brand (marque)
                $p->active
            );
        }
    }


    public static function toFormat($list)
    {
        return implode('|', $list);
    }

    // Useful function for handling module configuration
    protected function setConfig($key, $value)
    {
        return Configuration::updateValue($this->name . $key, $value, true);
    }

    protected function getConfig($value)
    {
        return Configuration::get($this->name . $value);
    }

    protected function deleteConfig($value)
    {
        return Configuration::deleteByName($this->name . $value);
    }

    function hookFooter($params)
    {
        global $statDone;
        if ($statDone == false) {
            global $smarty;

            //product order
	    if (!isset($params['objOrder'])) return;

            $objOrder = new Cart(intval($params['objOrder']->id_cart));
            $productsOrder = $objOrder->getProducts();

            //array of page info
            $data['page']['page_name'] = $smarty->tpl_vars['name']->value;
            $data['page']['page_type'] = $smarty->tpl_vars['page_name']->value;
            $data['page']['product_id'] = (int)(Tools::getValue('id_product'));

            //in cart
            $cart = $params['cart'];
            $products = $cart->getProducts();
            //array of in cart product
            $data['cart'] = $this->_getQueryProduct($products);

            //product buy
            $cart = new Cart(intval($params['objOrder']->id_cart));
            $products = $cart->getProducts();
            $data['objOrder'] = $this->_getQueryProduct($products);


            //get user info
            $user = $this->_getUserInfo();
            //array of user info
            $data['user'] = $user;
            $statDone = true;
            $output = PigRecommendationAPI::pigdataTracking($data);
        }
        //Console::show($statDone, Console::INFO);

        return $output;
    }

    private function _getQueryProduct($products)
    {
        foreach ($products AS $product) {
            $data[] = $product['id_product'];
        }
        return $data;
    }

    private function _getAlgoClient()
    {
        $ma = array();
        $argu = $this->getConfig('argu');
        preg_match("/algoClient=([a-zA-Z0-9]*)/", $argu, $ma);
        return $ma[1];
    }

    private function _getUserInfo()
    {
        global $cookie;
        $data = null;
        if (!$cookie->isLogged()) {
        } else {
            $data['email'] = $cookie->email;
            $data['firstname'] = $cookie->customer_firstname;
            $data['lastname'] = $cookie->customer_lastname;
            $data['language'] = $cookie->id_lang;
            $data['uid'] = $cookie->id_customer;
        }
        return $data;
    }

    public function creatCustomCssAndJs()
    {
        $data = "
            <script type=\"text/javascript\">
            var mct_Options = {
            sliderId: \"mcts" . $this->refP . "\",
            direction: \"horizontal\",
            scrollInterval: " . $this->getConfig("Interval") . ",
            scrollDuration: " . $this->getConfig("Duration") . ",
            hoverPause: true,
            autoAdvance: " . $this->getConfig("autoAdvance") . ",
            scrollByEachThumb: " . $this->getConfig("ByEachThumb") . ",
            circular: true,
            largeImageSlider: null,
            inSyncWithLargeImageSlider: true,
            license: \"mylicense\"
            };
            var thumbnailSlider = new ThumbnailSlider(mct_Options); ";

        //$data="<script type=\"text/javascript\">alert('toto')</script>";

        //$css = "<script type=\"text/javascript\">";
        $css = ".pigreco" . $this->refP . "-message {color: " . $this->getConfig("title_color") . "; } ";
        $css .= ".pigreco" . $this->refP . "-message {font-size: " . $this->getConfig("title_size") . "; } ";
        $css .= ".pigreco" . $this->refP . "-item-image-support a img{width: " . $this->getConfig("image_size") . ";} ";
        $css .= ".pigreco" . $this->refP . "-item-name a {font-size: " . $this->getConfig("desc_size") . ";} ";
        $css .= ".pigreco" . $this->refP . "-item-name {width: " . $this->getConfig("desc_width") . ";} ";

        $css .= ".pigreco" . $this->refP . "-item-sale-price {color: " . $this->getConfig("price_color") . ";} ";
        $css .= ".pigreco" . $this->refP . "-item-sale-price {font-size: " . $this->getConfig("price_size") . ";} ";

        $css .= ".pigreco" . $this->refP . "-item-price-discount {color: " . $this->getConfig("price_color_reg") . ";} ";
        $css .= ".pigreco" . $this->refP . "-item-price-discount {font-size: " . $this->getConfig("price_size_reg") . ";} ";

        $data .= "var css= \"" . $css . "\";";

        $data .= " var addcss = new addcss(css);";

        $data .= "</script>";

        return $data;
    }

    function clickTracking()
    {
        $js = PigRecommendationAPI::clickTracking($this->getConfig('argu'));

        return $js;
    }
}

?>
