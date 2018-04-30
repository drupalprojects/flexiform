<?php

namespace Drupal\flexiform_wizard\Plugin\WizardStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\flexiform_wizard\Entity\WizardInterface;
use Drupal\flexiform_wizard\WizardStep\WizardStepBase;

/**
 * Entity Form Mode plugin.
 *
 * @WizardStep(
 *   id = "entity_form_mode",
 *   deriver = "\Drupal\flexiform\Plugin\Deriver\EntityFormBlockDeriver",
 * )
 */
class EntityFormMode extends WizardStepBase implements ContextProvidingWizardStepInterface {

  /**
   * The form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * Get entity form display.
   */
  protected function getFormDisplay() {
    if (!$this->formDisplay) {
      $base_entity = $this->getContextValue('entity');
      $definition = $this->getPluginDefinition();
      $this->formDisplay = EntityFormDisplay::collectRenderDisplay($base_entity, $definition['form_mode']);
    }

    return $this->formDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvidedContexts() {
    $form_display = $this->getFormDisplay();
    $context_mapping = $this->getContextMapping();
    $provided_contexts = [];
    foreach ($form_display->getFormEntityManager()->getContexts() as $namespace => $form_entity_context) {
      if ($namespace == '') {
        $namespace = 'entity';
      }

      if (!empty($context_mapping[$namespace])) {
        $provided_contexts[$context_mapping[$namespace]] = $form_entity_context;
      }
      else {
        $provided_contexts[$this->configuration['step'].'.'.$namespace] = $form_entity_context;
      }
    }

    return $provided_context;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $this->configuration['step'];
    /* @var WizardInterface $config */
    $config = $this->configuration['wizard_config'];

    /* @var \Drupal\flexiform\FlexiformEntityFormDisplay $form_display */
    $form_display = $this->getFormDisplay();
    $form_state->set('form_display', $form_display);

    $provided = $this->getContextValues();
    $provided[''] = $provided['entity'];
    unset($provided['entity']);

    // Other cached values may have been set so we provide those too.
    $cached_values = $form_state->getTemporaryValue('wizard');
    foreach ($cached_values['entities'] as $name => $cached_entity) {
      if (strpos($name, '.') === FALSE) {
        continue;
      }

      list($entity_step, $namespace) = explode('.', $name, 2);
      if ($entity_step == $step) {
        $provided[$namespace] = $cached_entity;
      }
    }

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
    /* @var WizardInterface $config */
    $config = $this->configuration['wizard_config'];
    $entity = $this->getContextValue('entity');
    $step = $this->configuration['step'];

    $form_display = $form_state->get('form_display');
    $form_display->extractFormValues($entity, $form, $form_state);

    $form_display->formSubmitComponents($form, $form_state);

    if (!$config->shouldSaveOnFinish()) {
      $entity->save();
      $form_display->saveFormEntities($form, $form_state);
    }
  }

  /**
   * Process the form.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    return $element;
  }

}
