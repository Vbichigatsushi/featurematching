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

    public function displayAjaxAddFeatureGroup()
    {
        $featureGroup = Tools::getValue("featureGroup");

        if (Db::getInstance()->insert('fm_feature_group', [
            'name' => pSQL($featureGroup),
        ])) {
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Feature group successfully added !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding feature group !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxDeleteFeatureGroup()
    {
        $featureGroup = Tools::getValue("featureGroup");

        if (Db::getInstance()->delete('fm_feature_group', 'name = "' . $featureGroup . '"')) {
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Feature group successfully deleted !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure deleting feature group !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxAddAllFeatureGroup()
    {
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

    public function displayAjaxAddSubFeature()
    {
        $feature = Tools::getValue("feature");
        $categoryTitle = Tools::getValue("category");

        $idFeatureGroup = (int) Db::getInstance()->getValue(
            "SELECT id_feature_group 
             FROM " . _DB_PREFIX_ . "fm_feature_group 
             WHERE name = '" . pSQL($categoryTitle)."';"
        );

        if (Db::getInstance()->insert('fm_feature', [
            'name' => pSQL($feature),
            'id_feature_group' => $idFeatureGroup
        ])) {
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Sub-feature successfully added !", [], 'Modules.Featurematching.Admin')]));
        }
        $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure adding sub-feature !", [], 'Modules.Featurematching.Admin')]));
    }

    public function displayAjaxDeleteSubFeature()
    {
        $feature = Tools::getValue("feature");

        if (Db::getInstance()->delete('fm_feature', 'name = "' . $feature . '"')) {
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Sub-feature successfully deleted !", [], 'Modules.Featurematching.Admin')]));
        } else {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Failure deleting sub-feature !", [], 'Modules.Featurematching.Admin')]));
        }
    }

    public function displayAjaxAddAllSubFeatures()
    {
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

    public function displayAjaxSearchProduct()
    {
        $query = trim(Tools::getValue('query'));

        if (!$query) {
            die(json_encode([]));
        }

        // Requête pour rechercher les produits par référence
        $sql = 'SELECT p.id_product, name, reference FROM ' . _DB_PREFIX_ . 'product_lang pl
                INNER JOIN ' . _DB_PREFIX_ . 'product p ON pl.id_product = p.id_product
                INNER JOIN ' . _DB_PREFIX_ . 'fm_feature_product fpf ON fpf.id_product = p.id_product
                WHERE pl.id_lang = ' . (int)$this->context->language->id . '
                AND (p.reference LIKE "%' . pSQL($query) . '%" OR pl.name LIKE "%' . pSQL($query) . '%" OR p.id_product LIKE "%' . pSQL($query) . '%")';

        $products = Db::getInstance()->executeS($sql);

        // Renvoie les résultats en JSON
        die(json_encode($products));
    }

    public function displayAjaxHandleProductAllFeaturesDeletion()
    {
        $productId = Tools::getValue('idProductAllFeaturedeletion');

        if (isset($productId)) {
            Db::getInstance()->execute("DELETE cp FROM " . _DB_PREFIX_ . "category_product cp
                                    INNER JOIN " . _DB_PREFIX_ . "fm_feature_product fp ON fp.id_product = cp.id_product
                                    INNER JOIN " . _DB_PREFIX_ . "fm_feature_category fc ON fc.id_category = cp.id_category
                                    WHERE fp.id_product = " . strval($productId));
            Db::getInstance()->execute("DELETE fp FROM " . _DB_PREFIX_ . "fm_feature_product fp WHERE fp.id_product =".strval($productId));

            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->trans("Success while deleting all feature from product !", [], 'Modules.Featurematching.Admin')]));
        }
        else{
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->trans("Error while deleting all feature from product ! id not set.", [], 'Modules.Featurematching.Admin')]));
        }  
    }
}
