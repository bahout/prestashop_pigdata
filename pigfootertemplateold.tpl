<div class="related">
    {if isset($foopdb) && count($foopdb)}
        <div id="container">
            <div style="float:left;">
                <h2>{$foomsg}</h2>
            </div>
            <div id="mcts1-nav">
                <div class="navPrev" onselectstart="return false"></div>
                <div class="navNext" onselectstart="return false"></div>
            </div>
        </div>
        <!-- Block Pigdata Recommendations Col -->
        <div>
            <ul id="mcts1">
                {foreach from=$foopdb item=cp name=dispLoop}
                    <li>
                        <div class="img_container2">
                            <a href="{$cp.link}" title="{$cp.name|htmlspecialchars}">
                                <img src="{$cp.imagehome}" alt="{$cp.name|htmlspecialchars}"/>
                            </a>

                        </div>
                        <a href="{$cp.link}" title="{$cp.name|htmlspecialchars}">
                            <strong>{$cp.categoryname}</strong>
                            <span class="desc_lunette">{$cp.name|truncate:25:'...'|htmlspecialchars}</span>
                        </a>

                        <a href="{$cp.link}" title="{$cp.name|htmlspecialchars}">
                            {if isset($cp.pricewithout)}
                                <span class="price_display">
                                            <span class="prix_lunette_reduc test_wr"><span
                                                        class="bg_prix_reduc">[]</span>{$cp.price}</span>
						<span class="prix_barre">{$cp.pricewithout}</span>
					</span>
                            {else}
                                <span class="price_display">
						<span class="price">{$cp.price}</span>
					</span>
                            {/if}                        </a>
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="clear"></div>
        <!-- end Pigdata products module -->
    {/if}
</div>


<div id="mcts1" style="background-image: none;">
    <div style="display: block; position: relative; overflow: hidden;">
        <div style="display: block; width: 1432px; position: relative; left: -405px;">
            <div class="item" style="display: block; float: left;"><img
                        src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-6.jpg"
                        onmouseover="tooltip.pop(this, 'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...')">
            </div>
            <div class="item" style="display: block; float: left;"><img
                        src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-7.jpg" onmouseover="tooltip.pop(this, '#tipC')">
            </div>
            <div class="item" style="display: block; float: left;"><img
                        src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-8.jpg"
                        onmouseover="tooltip.pop(this, 'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur')">
            </div>
            <div class="item" style="display: block; float: left;"><img
                        src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-1.jpg"
                        onmouseover="tooltip.pop(this, 'This is the first slide')"></div>
            <div class="item" style="display: block; float: left;"><img
                        src="thumbnailSlider/images/thumbnail-slider-2.jpg" onmouseover="tooltip.pop(this, '#tip2')">
            </div>
            <div class="item" style="display: block; float: left;"><img
                        src="thumbnailSlider/images/thumbnail-slider-3.jpg" onmouseover="tooltip.pop(this, '#tip3')">
            </div>
            <div class="item" style="display: block; float: left;">
                <div class="class1" onmouseover="tooltip.pop(this, '#tip4')"><p>HTML Content</p>This slide shows that
                    HTML/Text can also be used as thumbnails
                </div>
            </div>
            <div class="item" style="display: block; float: left;"><a href="http://www.menucool.com"
                                                                      onmouseover="tooltip.pop(this, '#tipA')"><img
                            src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-4.jpg"></a></div>
            <div class="item" style="display: block; float: left;"><img
                        src="http://www.menucool.com/slider/thumbnailSlider/images/thumbnail-slider-5.jpg" onmouseover="tooltip.pop(this, '#tipB')">
            </div>
        </div>
    </div>


    <a class="navPrev" onselectstart="return false"></a><a class="navPause" onselectstart="return false"
                                                           title="Pause"></a><a class="navNext"
                                                                                onselectstart="return false"></a></div>