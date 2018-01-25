<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for configurable form enhancers.
 */
interface ConfigurableFormEnhancerInterface extends FormEnhancerInterface {

  /**
   * The configuration form.
   *
   * @param array $form;
   * @param \Drupal\Core\Form\FormStateInterface $form_state;
   *
   * @return array
   */
  public function configurationForm(array $form, FormStateInterface $form_state);

  /**
   * The configuration form validation callback.
   *
   * @param array $form;
   * @param \Drupal\Core\Form\FormStateInterface $form_state;
   */
  public function configurationFormValidate(array $form, FormStateInterface $form_state);

  /**
   * The configuration form submit callback.
   *
   * @param array $form;
   * @param \Drupal\Core\Form\FormStateInterface $form_state;
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state);

  /**
   * Get the configuration.
   *
   * @return array
   */
  public function getConfiguration();

}
