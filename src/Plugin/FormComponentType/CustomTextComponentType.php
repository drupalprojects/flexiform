<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeBase;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "custom_text",
 *   label = @Translation("Custom Text"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\CustomTextComponent",
 * )
 */
class CustomTextComponentType extends FormComponentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($this->getFormDisplay()->getComponents() as $component_name => $options) {
      if (isset($options['component_type']) && $options['component_type'] == $this->getPluginId()) {
        $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
      }
    }

    return $rows;
  }

}
