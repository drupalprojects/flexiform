<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Drupal\flexiform\FormEnhancer\SubmitButtonFormEnhancerTrait;

/**
 * FormEnhancer for altering the redirects of submit buttons.
 *
 * @FormEnhancer(
 *   id = "submit_button_redirect",
 *   label = @Translation("Button Redirects"),
 * );
 */
class SubmitButtonRedirect extends ConfigurableFormEnhancerBase {
  use SubmitButtonFormEnhancerTrait;
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
        '#title' => $this->t('@label Button Redirect Path', ['@label' => $label]),
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
    foreach (array_filter($this->configuration) as $key => $redirect) {
      $array_parents = explode('::', $key);
      $button = &NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        if (empty($button['#submit'])) {
          $buttons['#submit'] = !empty($form['#submit']) ? $form['#submit'] : [];
        }
        $button['#submit'][] = [$this, 'formSubmitRedirect'];
        $button['#submit_redirect'] = $redirect;
      }
    }
    return $element;
  }

  /**
   * Redirection submit handler.
   */
  public function formSubmitRedirect($form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    if (!empty($element['#submit_redirect'])) {
      // @todo: Support tokens.
      // @todo: Support all the different schemes.
      $path = $element['#submit_redirect'];
      if (!in_array($path[0], ['/', '?', '#'])) {
        $path = '/'.$path;
      }

      $form_state->setRedirectUrl(Url::fromUserInput($path));
    }
  }

}
