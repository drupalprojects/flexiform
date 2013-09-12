<?php
/**
 * @file
 * Model type editing UI.
 */

/**
 * Generates the model type editing form.
 */
function flexiform_webform_form($form, &$form_state, $webform, $op = 'edit') {
  if ($op == 'clone') {
    $webform->label .= ' (cloned)';
    $webform->webform = '';
  }

  $form['label'] = array(
    '#title' => t('Label'),
    '#type' => 'textfield',
    '#default_value' => $webform->label,
    '#description' => t('The human-readable name of this webform.'),
    '#required' => TRUE,
    '#size' => 30,
  );

  // Machine-readable type name.
  $form['webform'] = array(
    '#type' => 'machine_name',
    '#default_value' => isset($webform->webform) ? $webform->webform : '',
    '#maxlength' => 32,
    '#machine_name' => array(
      'exists' => 'flexiform_webform_load',
      'source' => array('label'),
    ),
    '#description' => t('A unique machine-readable name for this webform. It must only contain lowercase letters, numbers, and underscores.'),
    '#disabled' => empty($webform->is_new),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Form API submit callback for the type form.
 */
function flexiform_webform_form_submit(&$form, &$form_state) {
  $webform = entity_ui_form_submit_build_entity($form, $form_state);
  $webform->save();
  $form_state['redirect'] = 'admin/structure/flexiform_webforms';
}

/**
 * Form API submit callback for the delete button.
 */
function flexiform_webform_form_submit_delete(&$form, &$form_state) {
  $form_state['redirect'] = 'admin/structure/flexiform_webforms/manage/' . $form_state['webform']->webform . '/delete';
}
