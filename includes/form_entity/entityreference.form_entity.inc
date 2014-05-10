<?php
/**
 * @file
 *  Contains class for a basic entity getter.
 */

/**
 * Form Entity Class for Managing the Entity Reference fields
 */
class FlexiformFormEntityEntityReference extends FlexiformFormEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $settings = $this->settings;

    // Get the base entity.
    $base_entity = $this->getParam('base');

    // Check we have enough information to load the entity.
    if (!$base_entity || empty($base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][0]['target_id'])) {
      return FALSE;
    }

    // Load the entity;
    $target_id = $base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][0]['target_id'];
    $target_entity = entity_load_single($this->entity_type, $target_id);

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
   * {@inheritdoc}
   */
  public function saveEntity($entity) {
    // If the entity is still false do not save it!
    if ($entity === FALSE) {
      return;
    }

    // Save the entity.
    entity_save($this->entity_type, $entity);
    list($id,,) = entity_extract_ids($this->entity_type, $entity);

    // Get Settings for this Getter/Setter.
    $settings = $this->settings;

    // Get the base entity and save the reference back.
    $base_entity = $this->getParam('base');
    if (empty($base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][0]['target_id'])
      || $base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][0]['target_id'] != $id) {
      $base_entity->{$this->getter['field_name']}[LANGUAGE_NONE][0]['target_id'] = $id;
      $base_entity_type = $this->getParamType('base');
      entity_save($base_entity_type, $base_entity);
    }
  }
}
