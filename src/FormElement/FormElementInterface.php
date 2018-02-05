<?php

namespace Drupal\flexiform\FormElement;

use Drupal\Core\Form\FormStateInterface;

interface FormElementInterface {

  /**
   * Build the form element.
   */
  public function form(array $form, FormStateInterface $form_state);

}
