<?php

namespace Drupal\flexiform_wizard\Plugin\WizardStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\flexiform_wizard\Entity\WizardInterface;
use Drupal\flexiform_wizard\WizardStep\WizardStepBase;
use Drupal\flexiform_wizard\WizardStep\WizardStepInterface;

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
   * Get entity form display.
   */
  protected function getFormDisplay() {
    $base_entity = $this->getContextValue('entity');
    $definition = $this->getPluginDefinition();
    return EntityFormDisplay::collectRenderDisplay($base_entity, $definition['form_mode']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_display = $this->getFormDisplay();
    $form_state->set('form_display', $form_display);

    $provided = $this->getContextValues();
    $provided[''] = $provided['entity'];
    unset($provided['entity']);

    $form['#process'][] = [$this, 'processForm'];
    $form_display->buildAdvancedForm($provided, $form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /* @var WizardInterface $config */
    $config = $cached_values['flexiform_wizard'];
    $entity = $this->getContextValue('entity');

    $form_display = $form_state->get('form_display');
    $form_display->extractFormValues($entity, $form, $form_state);

    $form_display->formSubmitComponents($form, $form_state);
    if (!$config->shouldSaveOnFinish()) {
      $entity->save();
    }
    $form_display->saveFormEntities($form, $form_state);
  }

  /**
   * Process the form.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    return $element;
  }

}
