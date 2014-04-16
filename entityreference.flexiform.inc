<?php
/**
 * @file
 * Contains entity getters/setters for entityreference module.
 */

/**
 * Getter callback for party from a user.
 */
function flexiform_entity_getter_entityreference($flexiform, $base_entity, $entities, $namespace, $config) {
  $settings = $flexiform->entities[$namespace];

  // Get the base entity.
  $base_entity = $entities[$settings['parameters']['base']];

  // Check we have enough information to load the entity.
  if (!$base_entity || empty($base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['target_id'])) {
    return FALSE;
  }

  // Load the entity;
  $target_id = $base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['target_id'];
  $target_entity = entity_load_single($settings['entity_type'], $target_id);

  if (!$target_entity) {
    return FALSE;
  }

  // Check the entity is the right bundle.
  list(,,$bundle) = entity_extract_ids($settings['entity_type'], $target_entity);
  if ($bundle != $settings['bundle']) {
    return FALSE;
  }

  return $target_entity;
}

/**
 * Setter callback for party from user.
 */
function flexiform_entity_setter_entityreference($flexiform, $base_entity, $entity, $entity_type, $entities, $namespace, $config) {
  // If the entity is still false do not save it!
  if ($entity === FALSE) {
    return;
  }

  // Save the entity.
  entity_save($entity_type, $entity);
  list($id,,) = entity_extract_ids($entity_type, $entity);

  // Get Settings for this Getter/Setter.
  $settings = $flexiform->entities[$namespace];

  // Get the base entity and save the reference back.
  $base_entity = $entities[$settings['parameters']['base']];
  if (empty($base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['target_id'])
    || $base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['target_id'] != $id) {
    $base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['target_id'] = $id;
    $base_entity_type = $flexiform->entities[$settings['parameters']['base']]['entity_type'];
    entity_save($base_entity_type, $base_entity);
  }
}
