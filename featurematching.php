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

if (!defined('_PS_VERSION_')) {
    exit;
}

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

        $this->displayName = $this->l('Feature Matching');
        $this->description = $this->l('Feature Matching');

        $this->confirmUninstall = $this->l('Are you sure ?');

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
            ]);
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('controller') == 'AdminFeatureMatching') {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookActionCategoryFormBuilderModifier($params)
    {
        // get existing form
        $formBuilder = $params['form_builder'];
        $categoryId = $params['id'];
        // Retrieve the previously saved selections for this category
        $savedFeatures = $this->getCategoryFeatures($categoryId);

        $featureGroups = $this->getAllFeatureGroup();
        foreach ($featureGroups as $group) {
            $features = $this->getFeatureByGroup($group['id_feature_group']);
            $choices = [];
            $selected = [];

            foreach ($features as $feature) {
                // Correctly adding each feature to the choices array
                $choices[$feature['name']] = (int) $feature['id_feature'];
                // Check if this feature was selected before
                if (in_array((int) $feature['id_feature'], $savedFeatures)) {
                    $selected[] = (int) $feature['id_feature'];
                }
            }

            // Add a new custom field with the correct choices
            $formBuilder->add(strtolower($group['name']), \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => $choices,
                'label' => $group['name'],
                'multiple' => true,  // Allow multiple selections
                'expanded' => true,  // Use checkboxes
                'data' => $selected, // Set default values
                'attr' => [
                    'class' => 'form-control', // Optional: add CSS classes
                ],
            ]);
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
                if (in_array($subgroupKey, $featureGroupKeys)) {
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

            $featuresToRemove = array_diff($oldFeatures, $newFeatures);

            foreach ($featuresToRemove as $featureId) {
                $this->removeFeatureCategory($categoryId, $featureId);
            }
        }
    }


    protected function saveFeatureCategory($categoryId, $featureId): bool
    {
        return Db::getInstance()->insert('fm_feature_category', [
            'id_feature' => (int) $featureId,
            'id_category' => (int) $categoryId,
        ]);
    }

    protected function removeFeatureCategory($categoryId, $featureId): bool
    {
        return Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'fm_feature_category WHERE id_category = ' . (int) $categoryId . ' AND id_feature = ' . (int) $featureId);
    }

    protected function getAllFeatureGroup(): array
    {
        return Db::getInstance()->executeS("SELECT * FROM ps_fm_feature_group");
    }

    protected function getFeatureByGroup($featureGroupId): array
    {
        return Db::getInstance()->executeS("SELECT * FROM ps_fm_feature WHERE id_feature_group = $featureGroupId");
    }

    protected function getCategoryFeatures($categoryId): array
    {
        // array of id_feature
        return array_map(function ($feature) {
            return $feature['id_feature'];
        }, Db::getInstance()->executeS("SELECT id_feature FROM ps_fm_feature_category WHERE id_category = $categoryId"));
    }
}