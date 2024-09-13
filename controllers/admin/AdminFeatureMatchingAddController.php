<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminFeatureMatchingAddController extends ModuleAdminController
{
    public function __construct()
    {
        $this->ajax = true;
        parent::__construct();
    }

    public function displayAjaxAddFeatureGroup(){
    	$featureGroup = Tools::getValue("featureGroup");

    	if(Db::getInstance()->insert('fm_feature_group', [
            'name' => pSQL($featureGroup),
        ])){
    		$this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Feature group successfully added !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding feature group !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxDeleteFeatureGroup(){
        $featureGroup = Tools::getValue("featureGroup");

        if(Db::getInstance()->delete('fm_feature_group', 'name = "' . $featureGroup . '"')){
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Feature group successfully deleted !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure deleting feature group !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxAddAllFeatureGroup(){
    	$allFeatureGroup = Tools::getValue("allFeatureGroup");
    	try {
    		foreach ($allFeatureGroup as $feature) {
	    		Db::getInstance()->insert('fm_feature_group', [
		            'name' => pSQL($feature),
		        ]);	
		    }
		    $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("All Feature groups successfully added !", [], 'Modules.Featurematching.Admin')]));
    	} catch (Exception $e) {
    		$this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding all feature groups !", [], 'Modules.Featurematching.Admin')]));
    	}
    }

    public function displayAjaxAddSubFeature(){
        $feature = Tools::getValue("feature");
        $categoryTitle = Tools::getValue("category");

        $idFeatureGroup = (int) Db::getInstance()->getValue(
            "SELECT id_feature_group 
             FROM " . _DB_PREFIX_ . "fm_feature_group 
             WHERE name = '" . pSQL($categoryTitle)."';"
        );

        if(Db::getInstance()->insert('fm_feature', [
            'name' => pSQL($feature),
            'id_feature_group' => $idFeatureGroup
        ])){
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Sub-feature successfully added !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding sub-feature !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxDeleteSubFeature(){
        $feature = Tools::getValue("feature");

        if (Db::getInstance()->delete('fm_feature', 'name = "' . $feature . '"')) {
           $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Sub-feature successfully deleted !", [], 'Modules.Featurematching.Admin')]));
        } else {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure deleting sub-feature !", [], 'Modules.Featurematching.Admin')]));
        }
    }

    public function displayAjaxAddAllSubFeatures(){
        $allSubFeatures = Tools::getValue("allSubFeatures");

        try {
            // Parcourir chaque catégorie et ses sous-features
            foreach ($allSubFeatures as $categoryTitle => $subFeatures) {
                // Récupérer l'ID du groupe de features (category) via le titre de la catégorie
                $idFeatureGroup = (int) Db::getInstance()->getValue(
                    "SELECT id_feature_group 
                     FROM " . _DB_PREFIX_ . "fm_feature_group 
                     WHERE name = '" . pSQL($categoryTitle) . "';"
                );
                // Parcourir les sous-features et les insérer dans la table fm_feature
                foreach ($subFeatures as $feature) {
                    // Insérer chaque sous-feature dans la table fm_feature
                    Db::getInstance()->insert('fm_feature', [
                        'name' => pSQL($feature),
                        'id_feature_group' => $idFeatureGroup
                    ]);
                }
            }
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("All sub-features added successfully.", [], 'Modules.Featurematching.Admin')]));
        } catch (Exception $e) {
             $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding all sub-features !", [], 'Modules.Featurematching.Admin')]));
        }
    }
}
