<?php
/**
 * @file
 * Define Display plugin for the add page.
 */

/**
 * Class for add page displays.
 */
class FlexiformDisplayCreatePage extends FlexiformDisplayBase {

  /**
   * Define the menu links
   */
  public function hook_menu() {
    $items = array();

    $items[$this->configuration['path']] = array(
      'title callback' => 'flexiform_page_title',
      'title arguments' => array($this->flexiform->form),
      'page callback' => 'flexiform_page_wrapper',
      'page arguments' => array($this->flexiform->form),
      'access callback' => TRUE,
      'file' => 'flexiform.pages.inc',
    );

    return $items;
  }

  /**
   * Get the configuration form.
   */
  public function configForm($form, &$form_state) {
    $form = parent::configForm($form, $form_state);

    $form['path'] = array(
      '#title' => t('Path'),
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['path']) ? $this->configuration['path'] : NULL,
      '#description' => t('The path to a new submission of this form.'),
    );

    return $form;
  }
}
