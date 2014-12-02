<?php
/**
 * @file
 * Page callbacks for Flexiform Multistep.
 */

/**
 * Page callback for CTools Multistep Forms.
 *
 * @param $flexiform
 * @param string $display
 * @param mixed $base_entity_id
 * @param string $step
 *
 * @return array
 *   The page array.
 */
function flexiform_multistep_wrapper($flexiform, $base_entity, $current_step = NULL) {
  // Start setting up our ctools wizard multistep form info.
  ctools_include('wizard');

  $menu_item = menu_get_item();
  $settings = flexiform_multistep_settings_get($flexiform->form);

  $form_info = array(
    'id' => 'flexiform_multistep_' . $flexiform->form,
    'path' => $menu_item['href'] . '/%step',
    'next callback' => 'flexiform_multistep_step_next',
    'finish callback' => 'flexiform_multistep_step_finish',
  );
  if (isset($settings['form_info'])) {
    $form_info += $settings['form_info'];
  }

  // Add in our pages from the existing field groups.
  $groups = field_group_info_groups('flexiform', $flexiform->form, 'form');
  foreach ($groups as $group) {
    if ($group->format_type == 'flexiform_step') {
      // Skip groups with no children.
      if (!count($group->children)) {
        continue;
      }

      $step = substr($group->group_name, 6);
      $form_info['forms'][$step] = array(
        'form id' => 'flexiform_multistep_step_form',
        'title' => $group->label,
        'weight' => $group->weight,
        'group' => $group,
      );
    }
  }
  uasort($form_info['forms'], 'drupal_sort_weight');

  // Build our form state with our arguments.
  $form_state = array(
    'build_info' => array(
      'args' => array(
        $flexiform,
        $base_entity,
      ),
    ),
  );
  form_load_include($form_state, 'inc', 'flexiform_multistep', 'flexiform_multistep.pages');

  return ctools_wizard_multistep_form($form_info, $current_step, $form_state);
}

/**
 * Form constructor for a specific step of a multistep flexiform.
 */
function flexiform_multistep_step_form($form, &$form_state, $flexiform, $base_entity) {
  // Get hold of the full form.
  $form = drupal_retrieve_form('flexiform__' . $flexiform->form, $form_state);

  // Clear out the standard submission buttons.
  unset($form['actions']);

  // Get our current group.
  $form_state['step_group'] = $form_state['form_info']['forms'][$form_state['step']]['group'];

  // Clear out the multi step field groups.
  foreach ($form['#groups'] as $name => $group) {
    if ($group->format_type == 'flexiform_step') {
      unset($form['#groups'][$name]);
      unset($form['#fieldgroups'][$name]);
    }
  }

  // Hide any elements that aren't in this step.
  foreach (element_children($form) as $key) {
    // Preserve special items.
    if (in_array($key, array('ctools_trail', 'buttons'))) {
      continue;
    }

    if (!in_array($key, $form_state['step_group']->children)) {
      unset($form[$key]);
    }
  }

  return $form;
}

/**
 * Form submission handler for flexiform_multistep_step_form().
 */
function flexiform_multistep_step_next(&$form_state) {
  $form = $form_state['complete form'];

  /** @var Flexiform $flexiform */
  $flexiform = $form['#flexiform'];

  /** @var FlexiformBuilderInterface $builder */
  $builder = $form['#flexiform_builder'];
  $form_settings = flexiform_multistep_settings_get($flexiform->form);
  $step_settings = isset($form_state['step_group']->format_settings['instance_settings']['flexiform_multistep']) ? $form_state['step_group']->format_settings['instance_settings']['flexiform_multistep'] : array();

  // Inherit settings from the form if we're supposed to.
  if (!isset($step_settings['save_entities'])) {
    $step_settings['save_entities'] = !empty($form_settings['save_entities']) ? 'y' : 'n';
  }

  // Tweak our flexiform state so we can skip saving particular items.
  $flexiform_state = &$builder->getFlexiformState($form, $form_state);
  $entity_manager = $builder->getEntityManager($flexiform_state);
  $entities = array_keys($flexiform->entities);
  $save_entities = array();

  // Save the custom step of entities.
  if ($step_settings['save_entities'] == 'c') {
    $save_entities = array_filter($step_settings['save_entities_custom']);
  }
  // If this is the last step, switch to 'y' mode.
  elseif ($step_settings['save_entities'] == 'n' && !empty($form_state['last_step'])) {
    $step_settings['save_entities'] = 'y';
  }

  // If we're not on the last step, only save entities we've exposed.
  if ($step_settings['save_entities'] == 'y' && empty($form_state['last_step'])) {
    foreach (element_children($form) as $element) {
      if (!empty($form[$element]['#flexiform_element'])) {
        $namespace = $form[$element]['#flexiform_element']->getEntityNamespace();
        $save_entities[$namespace] = $namespace;
      }
    }
  }

  // If not saving everything, remove items we don't want.
  foreach (array_diff($entities, $save_entities) as $namespace) {
    $entity_manager->skipOnSave($namespace, TRUE);
  }

  $builder->formSubmit($form, $form_state);
}

/**
 * Form submission handler for flexiform_multistep_step_form().
 */
function flexiform_multistep_step_finish(&$form_state) {
  // Pass onto the normal step submission.
  $form_state['last_step'] = TRUE;
  flexiform_multistep_step_next($form_state);

  // Deal with the completion redirect.
  $form_settings = flexiform_multistep_settings_get($form_state['complete form']['#flexiform']->form);
  if (!empty($form_settings['redirect'])) {
    $builder = $form_state['complete form']['#flexiform_builder'];
    $flexiform_state = &$builder->getFlexiformState($form_state['complete form'], $form_state);
    $entity_manager = $builder->getEntityManager($flexiform_state);
    $replacements = array();
    foreach ($entity_manager->getEntities() AS $namespace => $entity) {
      $wrapper = entity_metadata_wrapper($entity_manager->getEntityType($namespace), $entity);
      $replacements['%' . $namespace] = $wrapper->getIdentifier();
    }
    $form_state['redirect'] = strtr($form_settings['redirect'], $replacements);
  }
}
