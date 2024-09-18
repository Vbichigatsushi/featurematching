<section>
	<p class="h6">Compatibilitées</p>
	{foreach from=$affiliatedCategories key=categoryName item=categorie}
	    <p class="h6"> - {$categoryName} :</p>
	            
		<table class="table table-striped table-bordered table-sm">
		    <tbody>
		    	{foreach from=$categorie item=categoryDetails}
		            <tr>
		                <td>{$categoryDetails['parent_name']}</td>
		                <td style="display: flex;gap: 10px;flex-wrap: wrap;">
		                	{foreach from=$categoryDetails['category_names'] key=index item=categoryName}
		                		<a style="text-wrap: nowrap;" target="_blank" href="{$categoryDetails['category_links'][$index]}">{$categoryName}</a><br />
		                	{/foreach}
		                </td>
		            </tr>
	            {/foreach}
		    </tbody>
		</table>
	{/foreach}
</section>
