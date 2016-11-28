<?php
/**
 * @file
 * Flexiform Checkout integration.
 */

/**
 * Flexiform Checkout Pane Callback.
 */
function flexiform_checkout_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
dpm(func_get_args(), 'Args');
  $pane_form = array();
  $pane_form['#flexiform']
    = $flexiform
    = flexiform_load($checkout_pane['flexiform']);
  $pane_form['#flexiform_builder'] = $builder = $flexiform->getBuilder($order);
  $pane_form['#parents'] = array($checkout_pane['pane_id']);

  $pane_form += $builder->form($pane_form, $form_state, $order);
  return $pane_form;  
}

/**
 * Flexiform Checkout Pane Validate Callback.
 */
function flexiform_checkout_pane_checkout_form_validate(&$form, &$form_state, $checkout_pane, $order) {
  $builder = $form[$checkout_pane['pane_id']]['#flexiform_builder'];
  $builder->formValidate($form[$checkout_pane['pane_id']], $form_state);

  if (form_get_errors()) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Flexiform Checkout Pane Submit Callback.
 */
function flexiform_checkout_pane_checkout_form_submit(&$form, &$form_state, $checkout_pane, $order) {
  $builder = $form[$checkout_pane['pane_id']]['#flexiform_builder'];
  $builder->formSubmit($form[$checkout_pane['pane_id']], $form_state);
}
