<?php

declare(strict_types=1);

namespace Pimentbleu\Featurematching\Form\Modifier;

use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Module;
use PrestaShopLogger;

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
}
