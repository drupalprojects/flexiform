<?php

namespace Drupal\flexiform_wizard\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform wizard step plugin annotation object.
 *
 * Plugin Namespace: Plugin\WizardStep.
 *
 * @see \Drupal\flexiform_wizard\WizardStep\WizardStepInterface
 * @see \Drupal\flexiform_wizard\WizardStep\WizardStepBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class WizardStep extends Plugin {

  /**
   * The wizard step plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the wizard step.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the module providing the wizard step.
   *
   * @var string
   */
  public $module;

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

}
