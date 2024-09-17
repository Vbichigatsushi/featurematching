<?php
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
 */
declare(strict_types=1);

use Pimentbleu\Featurematching\Form\Modifier\ProductFormModifier;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Featurematching extends Module
{
    public function __construct()
    {
        $this->name = 'featurematching';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Piment Bleu';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans("Feature Matching", [], 'Modules.Featurematching.Admin');
        $this->description = $this->trans("Allows you to associate products with categories based on their characteristics", [], 'Modules.Featurematching.Admin');


        $this->confirmUninstall = $this->trans("Are you sure ?", [], 'Modules.Featurematching.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook([
                'displayBackOfficeHeader',
                'actionCategoryFormBuilderModifier',
                'actionCategoryUpdate',
                'actionCategoryAdd',
                'actionProductFormBuilderModifier',
                'actionProductUpdate',
                'actionProductAdd',
                'displayMoreProductDetails',
            ]) && $this->installTab();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminFeatureMatching';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->displayName;
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall() && $this->uninstallTab();
    }

    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminFeatureMatching');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        $tokenAdminFeatureMatchingAdd = Tools::getAdminTokenLite('AdminFeatureMatchingAdd');

        // define js value to use in ajax url
        Media::addJsDef(
            [
                'tokenAdminFeatureMatchingAdd' => $tokenAdminFeatureMatchingAdd,
            ]
        );
    }

    public function hookActionCategoryFormBuilderModifier($params)
    {
        // get existing form
        $formBuilder = $params['form_builder'];
        $categoryId = $params['id'];

        if (isset($categoryId)) {
            // Retrieve the previously saved selections for this category
            $savedFeatures = $this->getCategoryFeatures($categoryId);

            $featureGroups = $this->getAllFeatureGroup();
            foreach ($featureGroups as $group) {
                $features = $this->getFeatureByGroup($group['id_feature_group']);
                $choices = [];
                $selected = [];

                foreach ($features as $feature) {
                    // Correctly adding each feature to the choices array
                    $choices[ucfirst($feature['name'])] = (int) $feature['id_feature'];
                    // Check if this feature was selected before
                    if (in_array((int) $feature['id_feature'], $savedFeatures)) {
                        $selected[] = (int) $feature['id_feature'];
                    }
                }

                // Add a new custom field with the correct choices
                $formBuilder->add(
                    str_replace(' ', '-', $group['name']),
                    ChoiceType::class,
                    [
                        'choices' => $choices,
                        'label' => ucfirst($group['name']), // Use HTML for the label
                        'multiple' => true,  // Allow multiple selections
                        'expanded' => true,  // Use checkboxes
                        'data' => $selected, // Set default values
                        'attr' => [
                            'class' => 'form-control d-flex',
                            'style' => 'gap: 20px',
                        ],
                        'row_attr' => [
                            'class' => 'form-group text-widget ' // Classe pour le conteneur parent
                        ],
                    ]
                );
            }
        }
    }

    /**
     * Modify product form builder
     *
     * @param array $params
     */
    public function hookActionProductFormBuilderModifier(array $params): void
    {
        /** @var ProductFormModifier $productFormModifier */
        $productFormModifier = $this->get(ProductFormModifier::class);
        $productId = (int) $params['id'];
        $savedFeatures = $this->getProductFeatures($productId);
        $featureGroups = $this->getAllFeatureGroup();

        $productFormModifier->modify(
            $productId,
            $params['form_builder'],
            $featureGroups,
            $savedFeatures,
        );
    }

    /* 
    EXECUTED ON category PAGE SAVING
    */
    protected function processCategoryFormData($params)
    {
        $categoryId = (int) $params['category']->id;
        $featureGroups = $this->getAllFeatureGroup();
        $customFields = Tools::getAllValues();
        $featureGroupKeys = [];

        // Prepare an array of feature group keys
        foreach ($featureGroups as $group) {
            $featureGroupKeys[] = strtolower($group['name']);
        }

        $oldFeatures = $this->getCategoryFeatures($categoryId);
        $newFeatures = [];

        if (isset($customFields['category']) && is_array($customFields['category'])) {
            foreach ($customFields['category'] as $subgroupKey => $subgroupValue) {
                // Check if subgroupKey is a valid feature group
                if (in_array(str_replace('-', ' ', $subgroupKey), $featureGroupKeys)) {
                    if (isset($customFields['category'][$subgroupKey])) {
                        // For each selected feature, save it
                        foreach ($customFields['category'][$subgroupKey] as $featureId) {
                            $newFeatures[] = $featureId;
                            if (!in_array($featureId, $oldFeatures)) {
                                $this->saveFeatureCategory($categoryId, $featureId);
                            }
                        }
                    }
                }
            }

            $categoryFeatures = $this->getCategoryFeatures($categoryId);

            foreach ($categoryFeatures as $featureId) {
                $productsToMatch = $this->getAllProductByFeature($featureId);

                foreach ($productsToMatch as $productId) {
                    $this->matchCategoryAndProduct($categoryId, $productId);
                    PrestaShopLogger::addLog("ASSOC CAT : $categoryId, $productId");
                }
            }

            PrestaShopLogger::addLog("features to remove before");
            $featuresToRemove = array_diff($oldFeatures, $newFeatures);
            PrestaShopLogger::addLog("feature id : ".json_encode($featuresToRemove));
            foreach ($featuresToRemove as $featureId) {
                $this->removeFeatureCategory($categoryId, $featureId);
                $productsToRemove = $this->getAllProductByFeature($featureId);
                PrestaShopLogger::addLog("feature id : $featureId");
                foreach ($productsToRemove as $productId) {
                    if (!$this->removeMatchCategoryAndProduct($categoryId, $productId)) {

                        PrestaShopLogger::addLog("failed to delete assoc : $categoryId, $productId");
                    }
                }
            }
        }
    }

    /* 
    EXECUTED ON PRODUCT PAGE SAVING
    */
    protected function processProductFormData($params)
    {
        $productId = (int) $params['product']->id;
        $featureGroups = $this->getAllFeatureGroup();
        $customFields = Tools::getAllValues();

        $featureGroupKeys = [];

        // Prepare an array of feature group keys
        foreach ($featureGroups as $group) {
            $featureGroupKeys[] = strtolower($group['name']);
        }

        $oldFeatures = $this->getProductFeatures($productId);
        $newFeatures = [];

        if (isset($customFields['product']['details']) && is_array($customFields['product']['details'])) {
            foreach ($customFields['product']['details'] as $subgroupKey => $subgroupValue) {

                // Check if subgroupKey is a valid feature group
                if (in_array(str_replace('-', ' ', $subgroupKey), $featureGroupKeys)) {
                    if (isset($customFields['product']['details'][$subgroupKey])) {

                        // For each selected feature, save it
                        foreach ($customFields['product']['details'][$subgroupKey] as $featureId) {
                            $newFeatures[] = $featureId;
                            if (!in_array($featureId, $oldFeatures)) {
                                PrestaShopLogger::addLog("save product ");
                                $this->saveFeatureProduct($productId, $featureId);
                            }
                        }
                    }
                }
            }

            $productFeatures = $this->getProductFeatures($productId);

            foreach ($productFeatures as $featureId) {
                $categoriesToMatch = $this->getAllCategoryByFeature($featureId);

                foreach ($categoriesToMatch as $categoryId) {
                    PrestaShopLogger::addLog("ASSOC CAT : $categoryId, $productId");
                    $this->matchCategoryAndProduct($categoryId, $productId);
                }
            }
        }
        $featuresToRemove = array_diff($oldFeatures, $newFeatures);
        PrestaShopLogger::addLog("feature id : ".json_encode($featuresToRemove));

        foreach ($featuresToRemove as $featureId) {
            $this->removeFeatureProduct($productId, $featureId);
            $categoriesToRemove = $this->getAllCategoryByFeature($featureId);
            PrestaShopLogger::addLog("catgoriestoremove : ".json_encode($categoriesToRemove));

            foreach ($categoriesToRemove as $categoryId) {
                PrestaShopLogger::addLog("catid : ".$categoryId." , productid : ".$productId);
                if (!$this->removeMatchCategoryAndProduct((int) $categoryId, (int) $productId)) {

                    PrestaShopLogger::addLog("failed to delete assoc : $categoryId, $productId");
                }
            }
        }
    }

    public function hookActionCategoryAdd($params)
    {
        $this->processCategoryFormData($params);
    }

    public function hookActionCategoryUpdate($params)
    {
        $this->processCategoryFormData($params);
    }

    public function hookActionProductAdd($params)
    {
        $this->processProductFormData($params);
    }

    public function hookActionProductUpdate($params)
    {
        $this->processProductFormData($params);
    }

    // TODO : list all categories of last level with same feature 
    public function hookDisplayMoreProductDetails($params)
    {
        $productId = $params['product']->id;

        $affiliatedCategories = Db::getInstance()->executeS("SELECT DISTINCT
          c.id_parent,
          GROUP_CONCAT(DISTINCT c.id_category ORDER BY c.id_category SEPARATOR ';') AS category_ids,
          GROUP_CONCAT(DISTINCT c_lang.name ORDER BY c_lang.name SEPARATOR ';') AS category_names,
          MAX(parent_lang.name) AS parent_name,  -- Le nom du parent
          MAX(grandparent_lang.name) AS grandparent_name  -- Le nom du parent du parent (grandparent)
        FROM " . _DB_PREFIX_ . "product p
        JOIN " . _DB_PREFIX_ . "category_product cp ON p.id_product = cp.id_product
        JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
        JOIN " . _DB_PREFIX_ . "category_lang c_lang ON c.id_category = c_lang.id_category
        LEFT JOIN " . _DB_PREFIX_ . "category c_parent ON c.id_parent = c_parent.id_category
        LEFT JOIN " . _DB_PREFIX_ . "category_lang parent_lang ON c_parent.id_category = parent_lang.id_category
        LEFT JOIN " . _DB_PREFIX_ . "category c_grandparent ON c_parent.id_parent = c_grandparent.id_category  -- Join pour le parent du parent
        LEFT JOIN " . _DB_PREFIX_ . "category_lang grandparent_lang ON c_grandparent.id_category = grandparent_lang.id_category  -- Langue du grandparent
        WHERE p.id_product = " . $productId . "
          AND c_lang.id_lang = 1  -- Langue à utiliser pour les catégories
          AND (parent_lang.id_lang = 1 OR parent_lang.id_lang IS NULL) -- Langue pour le parent
          AND (grandparent_lang.id_lang = 1 OR grandparent_lang.id_lang IS NULL) -- Langue pour le grandparent
          AND (c.id_parent NOT IN (1, 2) OR c.id_parent IS NULL) -- Exclure les catégories avec id_parent = 1 ou 2
        GROUP BY c.id_parent;");

        $groupedArray = [];

        // Parcourir le tableau d'origine
        foreach ($affiliatedCategories as $item) {
            $grandparentName = $item['grandparent_name'];
            
            // Si le nom du grandparent n'existe pas encore dans le tableau regroupé, on l'initialise
            if (!isset($groupedArray[$grandparentName])) {
                $groupedArray[$grandparentName] = [];
            }
            
            // Ajouter l'élément dans le groupe correspondant
            $groupedArray[$grandparentName][] = $item;
        }

        $this->context->smarty->assign('affiliatedCategories', $groupedArray);
        return "<p>test...</p>".$groupedArray;
        //return $this->display(__FILE__, 'views/templates/front/moreProductDetails.tpl');
    }

    protected function saveFeatureCategory($categoryId, $featureId): bool
    {
        return Db::getInstance()->insert('fm_feature_category', [
            'id_feature' => (int) $featureId,
            'id_category' => (int) $categoryId,
        ]);
    }

    protected function saveFeatureProduct($productId, $featureId): bool
    {
        return Db::getInstance()->insert('fm_feature_product', [
            'id_feature' => (int) $featureId,
            'id_product' => (int) $productId,
        ]);
    }

    protected function removeFeatureCategory($categoryId, $featureId): bool
    {
        return Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'fm_feature_category WHERE id_category = ' . (int) $categoryId . ' AND id_feature = ' . (int) $featureId);
    }

    protected function removeFeatureProduct($productId, $featureId): bool
    {
        return Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'fm_feature_product WHERE id_product = ' . (int) $productId . ' AND id_feature = ' . (int) $featureId);
    }

    protected function getCategoryFeatures(int $categoryId): array
    {

        return array_map(function ($feature): int {
            return (int) $feature['id_feature']; // return int id_feature
        }, Db::getInstance()->executeS("SELECT id_feature FROM " . _DB_PREFIX_ . "fm_feature_category WHERE id_category = $categoryId"));
    }

    protected function getProductFeatures(int $productId): array
    {
        return array_map(function ($feature): int {
            return (int) $feature['id_feature']; // return int id_feature
        }, Db::getInstance()->executeS("SELECT id_feature FROM " . _DB_PREFIX_ . "fm_feature_product WHERE id_product = $productId"));
    }

    protected function getAllCategoryByFeature($featureId): array
    {

        return array_map(function ($feature): int {
            return (int) $feature['id_category']; // return int id_category
        }, Db::getInstance()->executeS("SELECT id_category FROM " . _DB_PREFIX_ . "fm_feature_category WHERE id_feature = $featureId"));
    }

    protected function getAllProductByFeature(int $featureId): array
    {
        return array_map(function ($feature): int {
            return (int) $feature['id_product']; // return int id_product
        }, Db::getInstance()->executeS("SELECT id_product FROM " . _DB_PREFIX_ . "fm_feature_product WHERE id_feature = $featureId"));
    }

    protected function getAllFeatureGroup(): array
    {
        return Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "fm_feature_group ORDER BY name ASC");
    }

    public function getFeatureByGroup(int $featureGroupId): array
    {
        return Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "fm_feature WHERE id_feature_group = $featureGroupId");
    }

    public function getNewPositionInCategory(int $categoryId): int
    {
        $newPosition = Db::getInstance()->getValue("SELECT MAX(position) + 1 FROM " . _DB_PREFIX_ . "category_product WHERE id_category = $categoryId");
        return $newPosition ? (int) $newPosition : 0;
    }

    public function matchCategoryAndProduct(int $categoryId, int $productId): bool
    {
        return Db::getInstance()->insert('category_product', [
            'id_category' => (int) $categoryId,
            'id_product' => (int) $productId,
            'position' => (int) $this->getNewPositionInCategory($categoryId),
        ], false, true, Db::INSERT_IGNORE);
    }

    public function removeMatchCategoryAndProduct(int $categoryId, int $productId): bool
    {
        PrestaShopLogger::addLog("appel de removematch avec categoryId: $categoryId, productId: $productId");

        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'category_product WHERE id_category = ' . (int) $categoryId . ' AND id_product = ' . (int) $productId;
        
        PrestaShopLogger::addLog("SQL to execute: " . $sql);

        $result = Db::getInstance()->execute($sql);

        if (!$result) {
            // Loguer l'erreur SQL
            $error = Db::getInstance()->getMsgError();
            PrestaShopLogger::addLog("Erreur SQL lors de la suppression: " . $error);
        }

        return (bool) $result;
    }


    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
