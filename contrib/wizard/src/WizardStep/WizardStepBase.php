<?php

namespace Drupal\flexiform_wizard\WizardStep;

use Drupal\Core\Plugin\ContextAwarePluginBase;

/**
 * Base class for wizard steps.
 */
class WizardStepBase extends ContextAwarePluginBase implements WizardStepInterface {

  /**
   * {@inheritdoc}
   */
  public function stepInfo(array $cached_values = []) {
    return [
      'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
      'title' => $this->configuration['label'],
    ];
  }
}
