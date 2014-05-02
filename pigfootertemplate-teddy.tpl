{$clickTracking}
{if $version < 1.4}
    <link type="text/css" rel="stylesheet" href="{$content_dir}modules/pigreco{$refP}/pigreco{$refP}.css" />
    <script type="text/javascript" src="{$content_dir}modules/pigreco{$refP}/js/pig.js"></script>
    {$jsToAdd}
{/if}
<div id="pigreco{$templatenum}-widget-similar">
    <div class="pigreco{$templatenum}-similar">
        <div class="pigreco{$templatenum}-message" id="track">
            {$foomsg}
        </div>
        <div class="pigreco{$templatenum}-items">
            <div class="pigreco{$templatenum}-items-support" id="mcts{$templatenum}">

                {foreach from=$foopdb item=cp name=dispLoop}
                    <div class="pigreco{$templatenum}-item item" products_id="{$cp.pricewithout}">
                        <div class="pigreco{$templatenum}-item-image-support">
                            <a href="{$cp.link}">
                                <img src="{$cp.imagehome}"/>
                            </a>
                        </div>
                        <!-- infos produit-->
                        <div class="pigreco{$templatenum}-item-summary">
                            <h3 class="pigreco{$templatenum}-item-name"><a href="{$cp.link}">{$cp.name}</a></h3>
                            <span class="pigreco{$templatenum}-item-sale-price">
                              {if isset($cp.price)}
                                  &nbsp;-&nbsp;
                                  {$cp.price}
                              {/if}
                            </span>
                        </div>
                        <!-- fin infos produit -->
                    </div>
                {/foreach}

            </div>
        </div>
    </div>
</div>


