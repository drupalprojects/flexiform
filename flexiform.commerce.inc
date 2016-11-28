<?php
/**
 * @file
 * Contains Drupal Commerce Hooks.
 */

/**
 * Implements hook_commerce_checkout_pane_info().
 */
function flexiform_commerce_checkout_pane_info() {
  $panes = array();
  $forms = entity_load('flexiform');
  foreach ($forms as $form) {
    if (($display = $form->getDisplay('FlexiformDisplayCheckoutPane')) && $display->isEnabled()) {
      $panes['flexiform__'.$form->form] = array(
        'pane_id' => 'flexiform__'.$form->form,
        'title' => $display->configuration['title'],
        'name' => $display->configuration['title'],
        'review' => FALSE,
        'file' => 'flexiform.checkout.inc',
        'base' => 'flexiform_checkout_pane',
        'flexiform' => $form->form,
      );
    }
  }
  return $panes;
}
