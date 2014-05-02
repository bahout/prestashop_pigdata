{$clickTracking}
{if $version < 1.4}
    <link type="text/css" rel="stylesheet" href="{$content_dir}modules/pigreco{$templatenum}/css/pigreco{$templatenum}.css" />
    <script type="text/javascript" src="{$content_dir}modules/pigreco{$templatenum}/js/pig.js"></script>
{/if}
{$jsToAdd}

<div id="pigreco{$templatenum}-widget-similar">
    <div class="pigreco{$templatenum}-similar">
        <div class="pigreco{$templatenum}-message">
            {$foomsg}
        </div>
        <!--
        <div class="pigrecob-paging" id="mcts1-nav">
            <div class="pigrecob-paging-prev navPrev" onselectstart="return false"></div>
            <div class="pigrecob-paging-next navNext" onselectstart="return false"></div>
        </div>-->
        <div class="pigreco{$templatenum}-items">
            <div class="pigreco{$templatenum}-items-support" id="mcts{$templatenum}">

                {foreach from=$foopdb item=cp name=dispLoop}
                    <div class="pigreco{$templatenum}-item item" id="{$cp.productid}|{$algo}">
                        <div class="pigreco{$templatenum}-item-image-support">
                            <a href="{$cp.link}">
                                <img src="{$cp.imagehome}"/>
                            </a>
                        </div>
                        <div class="pigreco{$templatenum}-item-summary">
                            <div class="pigreco{$templatenum}-item-name"><a href="{$cp.link}">{$cp.name}</a></div>
                        </div>
                        <div class="pigreco{$templatenum}-clear"></div>
                        <div class="pigreco{$templatenum}-item-brand">
                            <!-- if (isset($item["pub_manu"]))
                             $html .= strtoupper($item["pub_manu"]);-->
                        </div>
                        <div class="pigreco{$templatenum}-item-prices">
                            <div class="pigreco{$templatenum}-item-sale-price">
                              {if isset($cp.price)}
                                    {$cp.price}
                                {/if}
                            </div>
                            <div class="pigreco{$templatenum}-item-price-discount">
                                {$cp.priceWithout}
                            </div>
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
</div>


