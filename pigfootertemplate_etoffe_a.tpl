{$clickTracking}
{if $version < 1.4}
    <link type="text/css" rel="stylesheet" href="{$content_dir}modules/pigreco{$refP}/pigreco{$refP}.css" />
    <script type="text/javascript" src="{$content_dir}modules/pigreco{$refP}/js/pig.js"></script>
{/if}
{$jsToAdd}
<div id="pigreco{$templatenum}-widget-similar">
    <div class="pigreco{$templatenum}-similar">
        <div class="pigreco{$templatenum}-message" id="track">
            {$foomsg}
        </div>
        <div class="pigreco{$templatenum}-items">
            <div class="pigreco{$templatenum}-items-support" id="mcts{$templatenum}">

                {foreach from=$foopdb item=cp name=dispLoop}
                    <div class="pigreco{$templatenum}-item item" products_id="{$cp.pricewithout}">

                        <!-- infos produit-->
                        <div class="pigreco{$templatenum}-item-summary">
                            <h3 class="pigreco{$templatenum}-item-name"><a href="{$cp.link}">{$cp.name}</a></h3>
                            <div class="pigreco{$templatenum}-item-sale-price-container">
                                <a class="pigreco{$templatenum}-item-sale-price" href="{$cp.link}">
                                    {if isset($cp.price)}
                                        {$cp.price}
                                    {/if}
                                </a>
                            </div>
                        </div>
                        <div class="pigreco{$templatenum}-item-image-support">
                            <a href="{$cp.link}">
                                <img src="{$cp.imagehome}"/>
                            </a>
                        </div>
                        <!-- fin infos produit -->
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
</div>


