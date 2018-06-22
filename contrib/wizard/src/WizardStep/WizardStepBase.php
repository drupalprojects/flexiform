<?php

namespace Drupal\flexiform_wizard\WizardStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Base class for wizard steps.
 */
abstract class WizardStepBase extends ContextAwarePluginBase implements WizardStepInterface {

  /**
   * {@inheritdoc}
   */
  public function stepInfo($name = '', array $cached_values = []) {
    return [
      'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
      'title' => !empty($this->configuration['label']) ? $this->configuration['label'] : '',
      'values' => [
        'step' => $name,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  abstract public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  abstract public function submitForm(array &$form, FormStateInterface $form_state);

}
