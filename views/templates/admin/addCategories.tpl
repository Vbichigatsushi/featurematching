<form action="" method="POST">
    <div class="panel" id="fieldset_0" style="position: relative;">
        <div class="panel-heading">
            <i class="icon-edit"></i> {l s="Feature groups" d='Modules.Featurematching.Admin'}
        </div>
        <div class="loader-container loaderId_0" style="display:none;">
            <span class="loader"></span>
        </div>
        <div style="display:flex;flex-direction: column;justify-content: center;">
        	<div id="formGroupContainer">
        		{foreach from=$features item=feature}
	        		<p style="display:flex;align-items: center;gap: 10px;">
	        			<strong>- {$feature[0]}</strong>
	        			<button class="btnDeleteFeatureGroup" onclick="deleteFeatureGroup(this.previousElementSibling)" type="button" style="display:flex;align-items: center;justify-content: left;gap: 10px;height: 25px;background: none;border: none;">
							<span class="material-icons text-danger">delete</span>
						</button>
	        		</p>
	        	{/foreach}
			</div>
			<button type="button" id="btnAddFieldGroupSection" class="btn btn-outline-secondary" style="display:flex;align-items: center;justify-content: center;gap: 10px;">
				<span class="material-icons">add_circle_outline</span> {l s="Add feature group" d='Modules.Featurematching.Admin'}
			</button>
			<div style="display:flex;justify-content: right;margin-top: 20px;">
				<button type="button" class="btn btn-info" onclick="addAllFeatureGroup(this.form)">{l s="Add all feature groups" d='Modules.Featurematching.Admin'}</button>
			</div>
		</div>
    </div>
</form>

<form action="" method="POST">
    <div class="panel" id="fieldset_1" style="position: relative;">
        <div class="panel-heading">
            <i class="icon-edit"></i> {l s="Sub-features" d='Modules.Featurematching.Admin'}
        </div>
        <div class="loader-container loaderId_1" style="display:none;">
            <span class="loader"></span>
        </div>
        <div style="display:flex;flex-direction: column;justify-content: center;">
        	{foreach from=$features item=feature}
		        <div class="form-group" data-category="{$feature[0]|escape:'htmlall':'UTF-8'}">
				    <label style="text-wrap: nowrap;">{$feature[0]} :</label>
				    <div style="display:flex;flex-direction: column;">
				    	<ul>
				    		{foreach from=$feature[1] item=featureValue}
					    		{if $featureValue != null }
						    		<li style="height: 25px;display: flex;align-items: center;flex-direction: row;gap: 10px;">
						    			<strong>{$featureValue}</strong>
						    			<button class="btnDeleteFeature" onclick="DeleteSubFeature(this.previousElementSibling)" type="button" style="display:flex;align-items: center;justify-content: left;gap: 10px;height: 25px;background: none;border: none;">
											<span class="material-icons text-danger">delete</span>
										</button>
									</li>
					    		{/if}
					    	{/foreach}
				    	</ul>
				    	<button class="btnAddFieldFeature btn btn-outline-secondary" type="button" style="display:flex;align-items: center;justify-content: left;margin-left: 32.5px;gap: 10px;">
							<span class="material-icons">add_circle_outline</span> {l s="Add sub-feature" d='Modules.Featurematching.Admin'}
						</button>
					</div>
				</div>
			{/foreach}
			<div style="display:flex;justify-content: right;">
				<button type="button" class="btn btn-info" onclick="addAllSubFeatures(this.form)">{l s="Add all sub-features" d='Modules.Featurematching.Admin'}</button>
			</div>
		</div>
    </div>
</form>