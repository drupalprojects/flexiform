<?php

namespace Drupal\flexiform_wizard\Plugin\WizardStep;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform_wizard\WizardStep\WizardStepBase;

/**
 * Entity Form Mode plugin.
 *
 * @WizardStep(
 *   id = "entity_form_mode",
 *   deriver = "\Drupal\flexiform\Plugin\Deriver\EntityFormBlockDeriver",
 * )
 */
class EntityFormMode extends WizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getContextDefinitions() {
    // @todo Fix broken deriver context.
    $definitions = parent::getContextDefinitions();
    unset($definitions['']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_entity = $this->getContextValue('entity');
    $definition = $this->getPluginDefinition();
    /* @var \Drupal\Flexiform\FlexiformEntityFormDisplay $form_display */
    $form_display = EntityFormDisplay::collectRenderDisplay($base_entity, $definition['form_mode']);

    $provided = $this->getContextValues();
    $provided[''] = $provided['entity'];
    unset($provided['entity']);

    $form['#process'][] = [$this, 'processForm'];
    $form_display->buildAdvancedForm($provided, $form, $form_state);

    return $form;
  }

  /**
   * Process the form.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    return $element;
  }
}
