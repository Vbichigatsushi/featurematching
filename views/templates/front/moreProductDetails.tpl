<section>
	<p class="h6">catégories compatibles ...</p>
	<table border="1">
	    <thead>
	        <tr>
	            {foreach $grandparents as $grandparent}
	                <th>{$grandparent}</th>
	            {/foreach}
	        </tr>
	    </thead>
	    <tbody>
	        {foreach $categories as $category}
	            <tr>
	                {foreach $grandparents as $grandparent}
	                    {if $category.grandparent_name == $grandparent}
	                        <td>
	                            <strong>{$category.parent_name}</strong><br>
	                            {foreach explode(";", $category.category_names) as $catName}
	                                {$catName}<br>
	                            {/foreach}
	                        </td>
	                    {else}
	                        <td></td>
	                    {/if}
	                {/foreach}
	            </tr>
	        {/foreach}
	    </tbody>
	</table>

</section>