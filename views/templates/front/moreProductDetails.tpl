<section>
    <p class="h6">Compatibilitées :</p>
    {foreach from=$affiliatedCategories key=categoryName item=categorie}
        <p class="h6"> - {$categoryName}</p>
            
        <table class="table table-striped table-bordered table-sm">
            <tbody>
                {foreach from=$categorie item=categoryDetails}
                    <tr>
                        <td>{$categoryDetails['parent_name']}</td>
                        <td>
                            {assign var="categoryIds" value=$categoryDetails['category_ids']}
                            {assign var="categoryLinks" value=$categoryDetails['category_links']}
                            {foreach from=$categoryLinks item=link key=index}
                                <a href="{$link}" target="_blank">{$categoryDetails['category_names'][index]}</a>
                                {if $index < count($categoryLinks) - 1}
                                    , 
                                {/if}
                            {/foreach}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {/foreach}
</section>
