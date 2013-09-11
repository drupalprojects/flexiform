<?php
/**
 * @file
 * Define Display plugin for the add page.
 */

/**
 * Class for add page displays.
 */
class FlexiformDisplayEditPage extends FlexiformDisplayBase {

  /**
   * Define the menu links
   */
  public function hook_menu() {
    $items = array();

    $path_bits = explode('/', $this->configuration['path']);
    $wildcard_key = array_search('%', $path_bits);
    if ($wildcard_key === FALSE) {
      continue;
    }

    $items[$this->configuration['path']] = array(
      'title callback' => 'flexiform_page_title',
      'title arguments' => array($this->flexiform->form, 'flexiform_edit_entity_page', $wildcard_key),
      'page callback' => 'flexiform_page_wrapper',
      'page arguments' => array($this->flexiform->form, 'flexiform_edit_entity_page', $wildcard_key),
      'access callback' => TRUE,
      'file' => 'flexiform.pages.inc',
    );

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntity($context = array()) {
    $base_entity = entity_load_single($this->flexiform->base_entity, $context['base_entity_id']);

    //@todo: Check the entity is the right bundle.
    return $base_entity;
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
      '#description' => t('The path to this form. Must include a single \'%\' where the id of the base entity will go.'),
    );

    return $form;
  }
}
