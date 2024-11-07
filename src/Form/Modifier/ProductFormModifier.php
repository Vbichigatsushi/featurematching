<?php

declare(strict_types=1);

namespace Pimentbleu\Featurematching\Form\Modifier;

use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Module;
use PrestaShopLogger;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use Tools;
use Context;
use Tab;

final class ProductFormModifier
{
    /**
     * @var FormBuilderModifier
     */
    private $formBuilderModifier;

    /**
     * @param FormBuilderModifier $formBuilderModifier
     */
    public function __construct(
        FormBuilderModifier $formBuilderModifier,
    ) {
        $this->formBuilderModifier = $formBuilderModifier;
    }

    /**
     * @param int|null $productId
     * @param FormBuilderInterface $productFormBuilder
     * @param array $choices
     * @param string $label
     * @param array $data
     */
    public function modify(
        int $productId,
        FormBuilderInterface $productFormBuilder,
        array $featureGroups,
        array $savedFeatures
    ): void {

        $detailsTabFormBuilder = $productFormBuilder->get('details');

        $featureGroups = array_reverse($featureGroups);

        foreach ($featureGroups as $group) {
            $features = Module::getInstanceByName('featurematching')->getFeatureByGroup($group['id_feature_group']);
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
            $this->formBuilderModifier->addAfter(
                $detailsTabFormBuilder, // the tab
                'customizations', // the input/form from which to insert after/before
                str_replace(' ', '-', $group['name']), // your field name
                ChoiceType::class, // your field type
                [
                    'choices' => $choices,
                    'label' => '<h3>' . ucfirst($group['name']) . '</h3>', // Use HTML for the label
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

    public function modify2(
        int $productId,
        FormBuilderInterface $productFormBuilder,
        array $featureGroups,
        array $savedFeatures
    ): void {

        $detailsTabFormBuilder = $productFormBuilder->get('details');

        // Générer le lien d'admin pour AdminFeatureMatching avec le token
        $adminLink = Context::getContext()->link->getAdminLink('AdminFeatureMatching');
        // Générer le token correct pour AdminFeatureMatching
        $adminToken = Tools::getAdminTokenLite('AdminFeatureMatching');

        // Ajouter le token à l'URL du lien
        $adminLinkWithToken = $adminLink . '&token=' . $adminToken;

        // Adding a static title and link above the feature groups
        $this->formBuilderModifier->addAfter(
            $detailsTabFormBuilder,
            'customizations',
            'form_header', // Name of the field
            \Symfony\Component\Form\Extension\Core\Type\TextType::class, // Using TextType for static content
            [
                'label' => '<div style="margin-bottom:-10000px;"><h3>Gérer les caractéristiques du produit</h3>' .
                          '<a href="' . $adminLinkWithToken . '" target="_blank"><i class="material-icons">open_in_new</i><b>Supprimer les caractéristiques</b></a></div>', // No label for this field
                'mapped' => false, // This field is not mapped to an entity
                'data' => '',
                'attr' => [
                    'class' => 'form-text', // Optional class for styling
                    'readonly' => true, // Make sure it's read-only
                    'style' => 'display:none;', // Styling for the title and link
                ],
            ]
        );

        // Reverse feature groups if needed
        $featureGroups = array_reverse($featureGroups);

        // Loop through each feature group
        foreach ($featureGroups as $group) {
            $features = Module::getInstanceByName('featurematching')->getFeatureByGroup($group['id_feature_group']);
            $choices = [];
            $selected = [];

            foreach ($features as $feature) {
                // Add each feature to the choices array
                $choices[ucfirst($feature['name'])] = (int) $feature['id_feature'];
                if (in_array((int) $feature['id_feature'], $savedFeatures)) {
                    $selected[] = (int) $feature['id_feature'];
                }
            }

            // Add the feature group as a ChoiceType field
            $this->formBuilderModifier->addAfter(
                $detailsTabFormBuilder,
                'form_header', // Add after the title and link
                str_replace(' ', '-', $group['name']),
                ChoiceType::class,
                [
                    'choices' => $choices,
                    'label' => ucfirst($group['name']), // Use the group name as label
                    'multiple' => true,  // Allow multiple selections
                    'expanded' => true,  // Display as checkboxes
                    'data' => $selected, // Set the default selected values
                    'attr' => [
                        'class' => 'form-control d-flex',
                        'style' => 'gap: 20px', // Styling for the checkboxes
                    ],
                    'row_attr' => [
                        'class' => 'form-group text-widget', // Class for the row
                    ],
                ]
            );
        }
    }
}
