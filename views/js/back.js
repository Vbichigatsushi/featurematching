/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

document.addEventListener("DOMContentLoaded", function () {
	$('#btnAddFieldGroupSection').click(function(e) {
		e.preventDefault();
	    const newFormGroup = `
	        <div class="form-group" style="display: flex;align-items: center;justify-content: center;gap: 30px;">
	            <label for="newFeatureGroupField" style="text-wrap: nowrap;">Add feature :</label>
	            <input type="text" class="form-control" />
	            <button type="button" class="btn btn-info btn-sm" onclick="addFeatureGroup(this.previousElementSibling)">Add</button>
	        </div>`;
	    
	    $('#formGroupContainer').append(newFormGroup);
	});

	$(document).on('click', '.btnAddFieldFeature', function(e) {
	    e.preventDefault(); // Empêche le comportement par défaut du bouton

	    // Création du nouveau groupe de formulaire à ajouter
	    const newFormGroup = `
	        <li class="fieldLi">
	            <div style="display:flex;align-items: center;gap: 10px;width: 100%;">
	                <input type="text" class="form-control" id="addFeatureField">
	                <button type="button" class="btn btn-info btn-sm" onclick="addSubFeature(this.previousElementSibling)">Add</button>
	            </div>
	        </li>
	    `;

	    // Ajout du nouveau groupe de formulaire après le dernier existant
	    $(this).prev('ul').append(newFormGroup);
	});
});

function showLoader(idLoader) {
    const loaderContainer = document.querySelector(".loaderId_" + idLoader);

    if (loaderContainer) {
        loaderContainer.style.display = "flex";
    }
}

function hideLoader(idLoader) {
    const loaderContainer = document.querySelector(".loaderId_" + idLoader);

    if (loaderContainer) {
        loaderContainer.style.display = "none";
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function addFeatureGroup(element) {

    showLoader("0");
    const featureGroup = element.value.trim().toLowerCase();

    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "AddFeatureGroup",
        token: tokenAdminFeatureMatchingAdd,
        featureGroup: featureGroup,
    };
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
        .done(function (response) {
            if (response.success) {
                hideLoader("0");
                $.growl.notice({ title: "", message: response.message });
                location.reload();
            } else {
                $.growl.error({ title: "", message: response.message });
            }
        })
        .fail(function (response) {
            hideLoader("0");
            console.error(response);
        });
}

function deleteFeatureGroup(element) {

    showLoader("0");
    const featureGroup = element.textContent.replace(/-/g, '').trim().toLowerCase();
    console.log(featureGroup);

    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "DeleteFeatureGroup",
        token: tokenAdminFeatureMatchingAdd,
        featureGroup: featureGroup,
    };
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
        .done(function (response) {
            if (response.success) {
                hideLoader("0");
                $.growl.notice({ title: "", message: response.message });
                location.reload();
            } else {
                $.growl.error({ title: "", message: response.message });
            }
        })
        .fail(function (response) {
            hideLoader("0");
            console.error(response);
        });
}

function addAllFeatureGroup(form) {
    showLoader("0");
    const allFeatureGroup = [];

    // Parcourir tous les éléments du formulaire
    for (let i = 0; i < form.elements.length; i++) {
        const element = form.elements[i];

        // Ne capturer que les champs de saisie (ignorer boutons et autres éléments)
        if (element.type !== "submit" && element.type !== "button") {
            allFeatureGroup.push(element.value.trim().toLowerCase());
        }
    }

    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "AddAllFeatureGroup",
        token: tokenAdminFeatureMatchingAdd,
        allFeatureGroup: allFeatureGroup,
    };

    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
        .done(function (response) {
            if (response.success) {
                hideLoader("0");
                $.growl.notice({ title: "", message: response.message });
                location.reload();
            } else {
                $.growl.error({ title: "", message: response.message });
            }
        })
        .fail(function (response) {
            hideLoader("0");
            console.error(response);
        });
}

function addSubFeature(element) {

    showLoader("1");
    const feature = element.value.trim().toLowerCase();
    const categoryTitle = $(element).closest('.form-group').data('category').trim();

    const categoryList = $(`.form-group[data-category='${categoryTitle}']`).find('ul');

    const newli = `<li style="height: 25px;display: flex;align-items: center;flex-direction: row;gap: 10px;">
		    			<strong>` + capitalizeFirstLetter(feature) + `</strong>
		    			<button class="btnDeleteFeature" onclick="DeleteSubFeature(this.previousElementSibling)" type="button" style="display:flex;align-items: center;justify-content: left;gap: 10px;height: 25px;background: none;border: none;">
							<span class="material-icons text-danger">delete</span>
						</button>
					</li>`;

    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "AddSubFeature",
        token: tokenAdminFeatureMatchingAdd,
        feature: feature,
        category: categoryTitle,
    };

    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
        .done(function (response) {
            if (response.success) {
                hideLoader("1");
                $.growl.notice({ title: "", message: response.message });
                categoryList.append(newli);
                $(element).closest('.fieldLi').remove();
            } else {
                $.growl.error({ title: "", message: response.message });
            }
        })
        .fail(function (response) {
            hideLoader("1");
            console.error(response);
        });
}

function DeleteSubFeature(element) {
    
    showLoader("1");
    const feature = element.textContent.trim().toLowerCase();


    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "DeleteSubFeature",
        token: tokenAdminFeatureMatchingAdd,
        feature: feature,
    };

    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
        .done(function (response) {
            if (response.success) {
                hideLoader("1");
                $.growl.notice({ title: "", message: response.message });
                $(element).closest('li').remove();
            } else {
                $.growl.error({ title: "", message: response.message });
            }
        })
        .fail(function (response) {
            hideLoader("1");
            console.error(response);
        });
}

function addAllSubFeatures(form) {
    showLoader("1");

    // Object to hold categories and their sub-feature values
    const allSubFeatures = {};

    // Traverse form elements
    $(form).find('.form-group').each(function() {
        // Retrieve the category name from the data-category attribute
        const categoryTitle = $(this).data('category').trim();

        // Retrieve the sub-feature values
        const subFeatures = [];
        $(this).find('input[type="text"]').each(function() {
            const subFeatureValue = $(this).val().trim().toLowerCase();

            // Add value if it's not empty
            if (subFeatureValue !== "") {
                subFeatures.push(subFeatureValue);
            }
        });

        // Add category with its sub-features only if the array is not empty
        if (subFeatures.length > 0) {
            allSubFeatures[categoryTitle] = subFeatures;
        }
    });

    const postdata = {
        controller: "AdminFeatureMatchingAdd",
        ajax: true,
        action: "AddAllSubFeatures",
        token: tokenAdminFeatureMatchingAdd,
        allSubFeatures: allSubFeatures
    };

    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "index.php",
        data: postdata,
    })
    .done(function(response) {
        if (response.success) {
            hideLoader("1");
            $.growl.notice({ title: "", message: response.message });

            // Update DOM with new sub-features
            $.each(allSubFeatures, function(category, subFeatures) {
                // Use the data-category attribute to find the correct category
                const categoryList = $(`.form-group[data-category='${category}']`).find('ul');

                // Check if the correct UL element is found for the category
                if (categoryList.length) {
                    // Add the sub-features to the right category list
                    $.each(subFeatures, function(index, subFeature) {
                        const newli = `<li style="height: 25px;display: flex;align-items: center;flex-direction: row;gap: 10px;">
                            <strong>${capitalizeFirstLetter(subFeature)}</strong>
                            <button class="btnDeleteFeature" type="button" style="display:flex;align-items: center;justify-content: left;gap: 10px;height: 25px;background: none;border: none;">
                                <span class="material-icons text-danger">delete</span>
                            </button>
                        </li>`;
                        categoryList.append(newli);
                    });
                } else {
                    console.error("No matching category found in DOM for: " + category);
                }
            });

            // Clear the form inputs and remove form elements after submission
            $(form).find('.form-group').each(function() {
                $(this).find('input[type="text"]').val(''); // Clear the input fields
                $(this).find('.fieldLi').remove(); // Remove the form elements
            });
        } else {
            $.growl.error({ title: "", message: response.message });
        }
    })
    .fail(function(response) {
        hideLoader("1");
        console.error(response);
    });
}
