<?php

/**
 * @file
 * Contains \Drupal\flexiform\Annotation\FormElement.
 */

namespace Drupal\flexiform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform form entity plugin annotation object.
 *
 * Plugin Namespace: Plugin\FormElement
 *
 * @see \Drupal\flexiform\FormElementInterface
 * @see \Drupal\flexiform\FormElementBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FormElement extends Plugin {

  /**
   * The form element plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the form element.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The name of the module providing the element.
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
  public $context = array();

}
