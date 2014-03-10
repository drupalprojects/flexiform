<?php
/**
 * @file
 * Contains entity getters/setters for field_collection module.
 */

/**
 * Getter callback for field collection items.
 */
function flexiform_entity_getter_field_collection($flexiform, $base_entity, $entities, $namespace, $config) {
  $settings = $flexiform->entities[$namespace];
  $base_settings = $flexiform->entities[$settings['parameters']['base']];

  // Get the base entity.
  $base_entity = $entities[$settings['parameters']['base']];

  // Check we have enough information to load the entity.
  if (!$base_entity) {
    return FALSE;
  }

  if (empty($base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['value'])) {
    $target_entity = entity_create('field_collection_item', array('field_name' => $settings['bundle']));
    $target_entity->setHostEntity($base_settings['entity_type'], $base_entity);
  }
  else {
    // Load the entity;
    $target_id = $base_entity->{$config['field_name']}[LANGUAGE_NONE][0]['value'];
    $target_entity = entity_load_single('field_collection_item', $target_id);
  }

  if (!$target_entity) {
    return FALSE;
  }

  // Check the entity is the right bundle.
  list(,,$bundle) = entity_extract_ids('field_collection_item', $target_entity);
  if ($bundle != $settings['bundle']) {
    return FALSE;
  }

  return $target_entity;
}

/**
 * Setter callback for party from user.
 */
function flexiform_entity_setter_field_collection($flexiform, $base_entity, $entity, $entity_type, $entities, $namespace, $config) {
  // Save the entity.
  entity_save($entity_type, $entity);
}
