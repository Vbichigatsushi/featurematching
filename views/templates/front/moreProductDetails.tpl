<div>
	<p>catégories compatibles ...</p>
	{* Récupérer la liste des grands-parents uniques *}
	{assign var="grandparents" value=[]}
	{foreach $categories as $category}
	    {if not in_array($category.grandparent_name, $grandparents)}
	        {assign var="grandparents" value=$grandparents|array_merge:[$category.grandparent_name]}
	    {/if}
	{/foreach}

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

</div>