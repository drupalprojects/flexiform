<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

interface FormComponentTypeInterface {

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   *   The form entity manager.
   */
  public function getFormEntityManager();

  /**
   * Set the form display.
   *
   * @param \Drupal\flexiform\FlexiformEntityFormDisplay $form_display
   *
   * @return \Drupal\Flexiform\FormComponent\FormComponentTypeInterface
   *   The form component type plugin with the form display set.
   */
  public function setFormDisplay(FlexiformEntityFormDisplay $form_display);

  /**
   * Get the form display.
   *
   * @return \Drupal\flexiform\FlexiformEntityFormDisplay
   *   The form display
   */
  public function getFormDisplay();

  /**
   * Get a component object.
   *
   * @param string $name
   * @param array $options
   *
   * @return \Drupal\flexiform\FormComponent\FormComponentInterface
   */
  public function getComponent($name, $options);

}
