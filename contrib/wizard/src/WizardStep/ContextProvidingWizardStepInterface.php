<?php

namespace Drupal\flexiform_wizard\WizardStep;

/**
 * Interface for wizard step plugins that provide additional contexts.
 */
interface ContextProvidingWizardStepInterface extends WizardStepInterface {

  /**
   * Return a list of Context objects that are provided by this wizard step.
   *
   * The keys of this array should be unique within the wizard. Keying by the
   * step name followed by a unique string is a good idea.
   *
   * @return \Drupal\Core\Plugin\Context\Context[]
   *   Array of contexts provided by this step.
   */
  public function getProvidedContexts();

  /**
   * Return a list of context definintions.
   *
   * @return \Drupal\Core\Plugin\Context\Context[]
   *   Array of contexts provided by this step.
   *
   * @see \Drupal\flexiform_wizard\WizardStep\ContextProvidingWizardStepInterface::getProvidedContexts
   */
  public function getProvidedContextDefinitions();

}
