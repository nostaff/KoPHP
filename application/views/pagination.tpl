{if $pages->totalItemCount > 0}
	<div class="pagination"> 
		<em>{$pages->totalItemCount}</em>
		<!-- First page link --> 
		{if $pages->firstPageInRange != $pages->current}<a href="{$url}{$pages->first}">{$pages->first} ...</a>{/if} 
	    <!-- Previous page link --> 
	    {if isset($pages->previous) }<a href="{$url}{$pages->previous}">&lt;&lt;</a>{/if} 
		<!-- Numbered page links --> 
		{foreach from=$pages->pagesInRange key=myId item=i} 
		{if $i != $pages->current }<a href="{$url}{$i}" >{$i}</a>{else}<strong>{$i}</strong>{/if} 
		{/foreach} 
	    <!-- Next page link --> 
	    {if isset($pages->next)}<a href="{$url}{$pages->next}">&gt;&gt;</a>{/if} 
		<!-- Last page link --> 
		{if $pages->lastPageInRange != $pages->current}<a href="{$url}{$pages->last}">... {$pages->last}</a>{/if} 
	</div> 
{/if}