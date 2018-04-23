<?php

namespace Drupal\flexiform_wizard\WizardStep;

/**
 * Interface for wizard step plugins that can provide additional contexts
 * to the wizard.
 */
interface ContextProvidingWizardStepInterface extends WizardStepInterface {

  /**
   * Return a list of Context objects that are provided by this wizard step.
   *
   * The keys of this array should be unique within the wizard. Keying by the
   * step name followed by a unique string is a good idea.
   *
   * @return \Drupal\Core\Plugin\Context[]
   *   Array of contexts provided by this step.
   */
  public function getProvidedContexts();

}
