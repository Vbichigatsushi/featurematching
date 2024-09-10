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

        $featureGroups = $this->getAllFeatureGroup();
        foreach ($featureGroups as $group) {
            $features = $this->getFeatureByGroup($group['id_feature_group']);
            $choices = [];

            foreach ($features as $feature) {
                // Correctly adding each feature to the choices array
                $choices[$feature['name']] = $feature['id_feature'];
            }

            // Add a new custom field with the correct choices
            $formBuilder->add("feature_" . strtolower($group['name']), \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => $choices,
                'label' => $group['name'],
                'multiple' => true,  // Allow multiple selections
                'expanded' => true,  // Use checkboxes
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
        $customFields = Tools::getAllValues();

        if (isset($customFields['category']) && is_array($customFields['category'])) {
            PrestaShopLogger::addLog(json_encode($customFields['category']));
            foreach ($customFields['category'] as $subgroupKey => $subgroupValue) {
                // check if subgroup start by "feature"
                if (strpos($subgroupKey, 'feature') === 0) {
                    if (isset($customFields['category'][$subgroupKey])) {
                        // foreach feature selected, save it
                        foreach ($customFields['category'][$subgroupKey] as $featureId) {
                            $this->saveFeatureCategory($categoryId, $featureId);
                        }
                    }
                }
            }
        }
    }


    protected function saveFeatureCategory($categoryId, $featureId)
    {
        return Db::getInstance()->insert('fm_feature_category', [
            'id_feature' => (int) $featureId,
            'id_category' => (int) $categoryId,
        ]);
    }

    protected function getAllFeatureGroup(): array
    {
        return Db::getInstance()->executeS("SELECT * FROM ps_fm_feature_group");
    }

    protected function getFeatureByGroup($featureGroupId): array
    {
        return Db::getInstance()->executeS("SELECT * FROM ps_fm_feature WHERE id_feature_group = $featureGroupId");
    }

    protected function setFeatureCategory($id_category)
    {
        return; /* Db::getInstance()->executeS("SELECT * FROM ps_fm_feature_category WHERE id_category = $id_category"); */
    }

}