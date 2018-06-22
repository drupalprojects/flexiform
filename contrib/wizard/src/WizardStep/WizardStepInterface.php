<?php

namespace Drupal\flexiform_wizard\WizardStep;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for wizard steps plugins.
 */
interface WizardStepInterface {

  /**
   * Get the step information.
   *
   * @param string $name
   *   The step name.
   * @param array $cached_values
   *   The cached values for the wizard.
   *
   * @return array
   *   Step information including:
   *   - title: The page title.
   *   - form: The fully qualified class name of the form object for this page.
   */
  public function stepInfo($name = '', array $cached_values = []);

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state);

}
