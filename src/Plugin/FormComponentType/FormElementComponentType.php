<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Component\Utitility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeCreateableBase;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "form_element",
 *   label = @Translation("Form Element"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\FormElementComponent",
 * )
 */
class FormElementComponentType extends FormComponentTypeCreateableBase {

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

  /**
   * {@inheritdoc}
   */
  public function addComponentForm(array $form, FormStateInterface $form_state) {
    // Build the parents for the form element selector.
    $parents = $form['#parents'];
    $parents[] = 'form_element';

    if ($plugin_id = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $plugin = $this->pluginManager->createInstance($plugin_id);
      $form = $plugin->settingsForm($form, $form_state);
    }
    else {
      $available_plugins = $this->pluginManager->getDefinitionsForContexts($this->getFormEntityManager()->getContexts());

      $form['#prefix'] = '<div id="flexiform-form-element-add-wrapper">';
      $form['#suffix'] = '</div>';

      $plugin_options = [];
      foreach ($available_plugins as $plugin_id => $plugin_definition) {
        if (empty($plugin_definition['no_ui'])) {
          $plugin_options[$plugin_id] = $plugin_definition['label'];
        }
      }
      $form['form_element'] = [
        '#type' => 'select',
        '#required'=> TRUE,
        '#options' => $plugin_optiosn,
        '#title' => $this->t('Form Element'),
        '#ajax' => [
          'callback' => [$this, 'ajaxFormElementSelect'],
          'wrapper' => 'flexiform-form-element-add-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentFormSubmit(array $form, FormStateInterface $form_state) {
  }

}
