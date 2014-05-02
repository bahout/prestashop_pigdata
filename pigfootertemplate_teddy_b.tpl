{$clickTracking}
{if $version < 1.4}
    <link type="text/css" rel="stylesheet" href="{$content_dir}modules/pigrecob/pigrecob.css" />
    <script type="text/javascript" src="{$content_dir}modules/pigrecob/js/pig.js"></script>
{/if}
{$jsToAdd}
<div id="pigreco{$templatenum}-widget-similar">
    <div class="pigreco{$templatenum}-similar">
        <div class="pigreco{$templatenum}-message">
            {$foomsg}
        </div>
        <div class="pigreco{$templatenum}-items">
            <div class="pigreco{$templatenum}-items-support" id="mcts{$templatenum}">

                {foreach from=$foopdb item=cp name=dispLoop}
                    <div class="pigreco{$templatenum}-item item" products_id="{$cp.pricewithout}">

                        <!-- infos produit-->
                        <div class="pigreco{$templatenum}-item-summary">
                            <div class="pigreco{$templatenum}-item-name"><a href="{$cp.link}">{$cp.name}</a></div>
                            <span class="pigreco{$templatenum}-item-sale-price">
                              {if isset($cp.price)}
                                  {$cp.price}
                              {/if}
                            </span>
                             <span class="pigreco{$templatenum}-item-price-discount">
                                {$cp.priceWithout}
                            </span>
                        </div>
                        <!-- fin infos produit -->
                        <div class="pigreco{$templatenum}-item-image-support">
                            <a href="{$cp.link}">
                                <img src="{$cp.imagepopuplarge}"/>
                            </a>
                        </div>
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
</div>


