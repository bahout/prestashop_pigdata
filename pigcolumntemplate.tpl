{if isset($colpdb) && count($colpdb)}
<div id="pig_block_columna" class="block products_blocka">
  <h4>{$colmsg}</h4>
  <div class="block_content">
  	<ul class="products clearfix">
		{foreach from=$colpdb item=cp name=dispLoop}
			<li class="clearfix{if $smarty.foreach.dispLoop.last} last_item{elseif $smarty.foreach.dispLoop.first} first_item{else} item{/if}">
				<div class="pigdata-item-image-support-cola">
                <a href="{$cp.link}" title="{$cp.name|htmlspecialchars}">
					<img src="{$cp.imagehome}" alt="{$cp.name|htmlspecialchars}" height="{$mediumSize.height}" width="{$mediumSize.width}"/>
				</a>
                 </div>
                <div class="pigdata-item-prices-cola">
				<h5 class="pigdata-item-name-cola"><a href="{$cp.link}" title="{$cp.name|htmlspecialchars}">
					{$cp.name|truncate:24:'...'|escape:'htmlall':'UTF-8'}
				</a></h5>
				<span class="price">{$cp['price']}</span>
                </div>
			</li>
		{/foreach}
	</ul>
  </div>
</div>
{/if}