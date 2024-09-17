<section>
	<p class="h6">catégories compatibles ...</p>
	{foreach from=$affiliatedCategories key=categoryName item=categorie}
	    <p class="h7">{$categoryName}</p>
	            
		<table border="1">
		    <tbody>
		    	{foreach from=$categorie item=categoryDetails}
		            <tr>
		                <td>{$categoryDetails['parent_name']}</td>
		                <td>{$categoryDetails['category_names']}</td>
		            </tr>
	            {/foreach}
		    </tbody>
		</table>
	{/foreach}
</section>