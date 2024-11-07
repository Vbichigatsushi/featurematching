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

        $productFormModifier->modify2(
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
        // Récupération de l'identifiant du produit en tant qu'entier
        $productId = (int) $params['product']->id;
        
        // Récupération de tous les groupes de caractéristiques
        $featureGroups = $this->getAllFeatureGroup();
        
        // Récupération de toutes les valeurs des champs personnalisés du formulaire
        $customFields = Tools::getAllValues();

        // Initialisation d'un tableau pour stocker les clés des groupes de caractéristiques
        $featureGroupKeys = [];

        // Préparation d'un tableau contenant les noms des groupes de caractéristiques en minuscule
        foreach ($featureGroups as $group) {
            $featureGroupKeys[] = strtolower($group['name']);
        }

        // Récupération des anciennes caractéristiques associées au produit
        $oldFeatures = $this->getProductFeatures($productId);
        
        // Initialisation d'un tableau pour les nouvelles caractéristiques
        $newFeatures = [];

        // Vérification si des détails produits sont fournis et sont sous forme de tableau
        if (isset($customFields['product']['details']) && is_array($customFields['product']['details'])) {
            
            // Parcourir chaque sous-groupe de caractéristiques
            foreach ($customFields['product']['details'] as $subgroupKey => $subgroupValue) {

                // Vérification si la clé du sous-groupe correspond à un groupe de caractéristiques valide
                if (in_array(str_replace('-', ' ', $subgroupKey), $featureGroupKeys)) {
                    
                    // Si le sous-groupe est défini, traiter les caractéristiques sélectionnées
                    if (isset($customFields['product']['details'][$subgroupKey])) {
                        
                        // Pour chaque caractéristique sélectionnée, l'ajouter aux nouvelles caractéristiques
                        foreach ($customFields['product']['details'][$subgroupKey] as $featureId) {
                            $newFeatures[] = (int) $featureId;

                            // Si la nouvelle caractéristique n'existe pas déjà, l'enregistrer pour le produit
                            if (!in_array($featureId, $oldFeatures)) {
                                PrestaShopLogger::addLog("save product ");
                                $this->saveFeatureProduct($productId, $featureId);
                            }
                        }
                    }
                }
            }

            // Récupérer à nouveau les caractéristiques du produit après modification
            $productFeatures = $this->getProductFeatures($productId);

            // Pour chaque caractéristique du produit, associer les catégories correspondantes
            foreach ($productFeatures as $featureId) {
                $categoriesToMatch = $this->getAllCategoryByFeature($featureId);

                foreach ($categoriesToMatch as $categoryId) {
                    PrestaShopLogger::addLog("ASSOC CAT : $categoryId, $productId");
                    $this->matchCategoryAndProduct($categoryId, $productId);
                }
            }
        }

        // Journalisation des anciennes et nouvelles caractéristiques pour comparaison
        PrestaShopLogger::addLog("old feature array for comparison : ".json_encode($oldFeatures));
        PrestaShopLogger::addLog("new feature array for comparison: ".json_encode($newFeatures));

        // Identification des caractéristiques à supprimer (présentes dans l'ancien mais pas dans le nouveau)
        $featuresToRemove = array_diff($oldFeatures, $newFeatures);
        PrestaShopLogger::addLog("deleted feature id : ".json_encode($featuresToRemove));

        // Pour chaque caractéristique à supprimer, procéder à sa suppression
        foreach ($featuresToRemove as $featureId) {
            $this->removeFeatureProduct($productId, $featureId);

            // Récupération des catégories liées à la caractéristique supprimée
            $categoriesToRemove = $this->getAllCategoryByFeature($featureId);
            PrestaShopLogger::addLog("catgoriestoremove : ".json_encode($categoriesToRemove));

            // Pour chaque catégorie à supprimer, dissocier le produit de cette catégorie
            foreach ($categoriesToRemove as $categoryId) {
                PrestaShopLogger::addLog("catid to delete : ".$categoryId." , productid to delete : ".$productId);

                // Si la suppression échoue, journaliser l'erreur
                if (!$this->removeMatchCategoryAndProduct((int) $categoryId, (int) $productId)) {
                    PrestaShopLogger::addLog("failed to delete assoc : $categoryId, $productId");
                }
            }
        }
    }


    public function hookActionCategoryAdd($params)
    {
        PrestaShopLogger::addLog("actioncategoryadd");
        $this->processCategoryFormData($params);
    }

    public function hookActionCategoryUpdate($params)
    {
        PrestaShopLogger::addLog("actioncategoryupdate");
        $this->processCategoryFormData($params);
    }

    public function hookActionProductAdd($params)
    {
        PrestaShopLogger::addLog("actionproductadd");
        $this->processProductFormData($params);
    }

    public function hookActionProductUpdate($params)
    {
        PrestaShopLogger::addLog("actionproductupdate");
        $this->processProductFormData($params);
    }

    // TODO : list all categories of last level with same feature 
    public function hookDisplayMoreProductDetails($params)
    {
        $productId = $params['product']->id;

        $affiliatedCategories = Db::getInstance()->executeS("SELECT DISTINCT
          c.id_parent,
          GROUP_CONCAT(DISTINCT c.id_category ORDER BY c.id_category SEPARATOR ', ') AS category_ids,
          GROUP_CONCAT(DISTINCT c_lang.name ORDER BY c.id_category SEPARATOR ', ') AS category_names,
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

            // Générer les liens pour chaque catégorie
            $categoryIds = explode(', ', $item['category_ids']);
            $categoryLinks = [];
            foreach ($categoryIds as $categoryId) {
                $item['category_links'][] = $this->generateCategoryLink($categoryId);
            }

            $categoryNames = explode(', ', $item['category_names']);
            $item['category_names'] = $categoryNames;

            // Ajouter l'élément dans le groupe correspondant
            $groupedArray[$grandparentName][] = $item;
        }

        $this->context->smarty->assign('affiliatedCategories', $groupedArray);

        return $this->display(__FILE__, 'views/templates/front/moreProductDetails.tpl');
    }


    public function generateCategoryLink($categoryId)
    {
        $link = $this->context->link->getCategoryLink($categoryId);

        return $link;
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
        // Ajout de log pour vérifier les paramètres passés
        PrestaShopLogger::addLog("appel de removeFeatureProduct productId : ".$productId." / featureId : ".$featureId);

        // Requête SQL de suppression
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'fm_feature_product WHERE id_product = ' . (int) $productId . ' AND id_feature = ' . (int) $featureId;
        
        // Loguer la requête SQL avant exécution
        PrestaShopLogger::addLog("SQL to execute: " . $sql);

        // Exécution de la requête
        $result = Db::getInstance()->execute($sql);

        // Vérification du résultat de la requête
        if (!$result) {
            // Loguer l'erreur SQL en cas d'échec
            $error = Db::getInstance()->getMsgError();
            PrestaShopLogger::addLog("Erreur SQL lors de la suppression: " . $error);
            return false; // Retourner false si la suppression échoue
        }

        // Vérification si la suppression a bien eu lieu
        $check = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'fm_feature_product WHERE id_product = ' . (int) $productId . ' AND id_feature = ' . (int) $featureId;
        $count = (int) Db::getInstance()->getValue($check);

        // Si l'association existe toujours, loguer une erreur
        if ($count > 0) {
            PrestaShopLogger::addLog("La suppression a échoué: l'association entre le produit $productId et la caractéristique $featureId existe toujours.");
            return false;
        }

        // Si tout s'est bien passé, retourner true
        PrestaShopLogger::addLog("Suppression réussie pour le produit $productId et la caractéristique $featureId.");
        return true;
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
        // Ajout de log pour vérifier les paramètres passés
        PrestaShopLogger::addLog("appel de removeMatch avec categoryId: $categoryId, productId: $productId");

        // Requête SQL de suppression
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'category_product WHERE id_category = ' . (int) $categoryId . ' AND id_product = ' . (int) $productId;
        
        // Loguer la requête SQL avant exécution
        PrestaShopLogger::addLog("SQL to execute: " . $sql);

        // Exécution de la requête
        $result = Db::getInstance()->execute($sql);

        // Vérification du résultat de la requête
        if (!$result) {
            // Loguer l'erreur SQL en cas d'échec
            $error = Db::getInstance()->getMsgError();
            PrestaShopLogger::addLog("Erreur SQL lors de la suppression: " . $error);
            return false; // Retourner false si la suppression échoue
        }

        // Vérification si la suppression a bien eu lieu
        $check = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'category_product WHERE id_category = ' . (int) $categoryId . ' AND id_product = ' . (int) $productId;
        $count = (int) Db::getInstance()->getValue($check);

        // Si le produit est toujours associé à la catégorie, loguer une erreur
        if ($count > 0) {
            PrestaShopLogger::addLog("La suppression a échoué: l'association entre la catégorie $categoryId et le produit $productId existe toujours.");
            return false;
        }

        // Si tout s'est bien passé, retourner true
        PrestaShopLogger::addLog("Suppression réussie pour la catégorie $categoryId et le produit $productId.");
        return true;
    }



    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
