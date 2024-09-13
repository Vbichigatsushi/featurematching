<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminFeatureMatchingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $features = Db::getInstance()->executeS("SELECT fg.name, GROUP_CONCAT( f.name SEPARATOR ';' ) AS features 
        FROM " . _DB_PREFIX_ . "fm_feature_group fg LEFT JOIN " . _DB_PREFIX_ . "fm_feature f ON f.id_feature_group=fg.id_feature_group 
        GROUP BY fg.name;");

        $formattedFeatures = array();
        foreach ($features as $feature) {
            $formattedFeatures[] = [ucfirst($feature['name']), array_map('ucfirst', explode(";", $feature['features']))];

        }

        $this->context->smarty->assign([
            'features' => $formattedFeatures
        ]);

        $this->setTemplate('addCategories.tpl');
    }
}
