<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;

/**
 * FormEnhancer for altering the labels of submit buttons.
 *
 * @FormEnhancer(
 *   id = "submit_button_label",
 *   label = @Translation("Button Labels"),
 * );
 */
class SubmitButtonLabel extends ConfigurableFormEnhancerBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'process_form',
  ];

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->locateSubmitButtons() as $path => $label) {
      $original_path = $path;
      $path = str_replace('][', '::', $path);
      $form[$path] = [
        '#type' => 'textfield',
        '#title' => $this->t('@label Button Text', ['@label' => $label]),
        '#description' => 'Array Parents: '.$original_path,
        '#default_value' => !empty($this->configuration[$path]) ? $this->configuration[$path] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValue($form['#parents']);
  }

  /**
   * Process Form Enhancer.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    foreach (array_filter($this->configuration) as $key => $label) {
      $array_parents = explode('::', $key);
      $button = &NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        $button['#value'] = $label;
      }
    }
    return $element;
  }

  /**
   * Locate any submit buttons in the form.
   *
   * @return array
   */
  protected function locateSubmitButtons() {
    $form_info = $this->formDisplay->getFormInformation();
    return $this->locateSubmitButtonsR($form_info['form']);
  }

  /**
   * Locate the submit buttons recursively.
   */
  private function locateSubmitButtonsR($elements, $depth = 0) {
    $buttons = [];
    foreach (Element::children($elements) as $key) {
      if (($depth == 0) && $this->formDisplay->getComponent($key)) {
        continue;
      }

      if ($elements[$key]['#type'] == 'submit') {
        $buttons[implode('][', $elements[$key]['#array_parents'])] = $elements[$key]['#value'];
      }

      $buttons += $this->locateSubmitButtonsR($elements[$key], $depth + 1);
    }
    return $buttons;
  }

}
