<?php
/**
 * @file
 * Contains the Field Comlete handler for the FlexiformWebform Module.
 */

class FlexiformFCHandler extends fcComplete {

  /**
   * Get which form to use to calculate completeness.
   */
  public function getForm() {
    $webform = entity_load_single('flexiform_webform', $this->entity->webform);
    $form = '__none';
    if (!empty($webform->data['fc']['form'])) {
      $form = $webform->data['fc']['form'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function completeness() {
    $form = $this->getForm();

    if ($form == '__none') {
      return parent::completeness();
    }

    $flexiform = entity_load_single('flexiform', $form);
    if (!$flexiform) {
      throw new Exception(t('Completeness Calculation Failed: The flexiform %form does not exist.'));
    }

    // Get the Entities.
    $entities = $flexiform->getBuilder($this->entity)->getEntityManager()->getEntities();

    $this->completeness = array();
    foreach ($flexiform->elements as $element_namespace => $element_settings) {
      $element = FlexiformElement::getElement($flexiform, $element_namespace);
      // Currently only support field api elements.
      if (!($element instanceof FlexiformElementField)) {
        continue;
      }

      $field = $element->getField();
      $instance = $element->getInstance();
      $settings = !empty($instance['settings']['fc']) ? $instance['settings']['fc'] : array();
      if (empty($settings['fc_include'])) {
        continue;
      }

      $this->completeness[$element_namespace] = FALSE;

      // Get Necessary information.
      $entity = $entities[$element_settings['entity_namespace']];
      $entity_type = $flexiform->entities[$element_settings['entity_namespace']]['entity_type'];
      $field_name = $field['field_name'];

      // If the entity is empty and the field isn't allowed to be empty then
      // obviously this form isn't complete.
      if (empty($entity)) {
        if (!empty($settings['fc_allow_empty'])) {
          $this->completeness[$element_namespace] = TRUE;
        }
        continue;
      }

      // Go through the field items if any are "not empty" then
      // we count that as complete (a bit simplistic but okay).
      $field_items = field_get_items($entity_type, $entity, $field_name);
      if (empty($field_items)) {
        if (!empty($settings['fc_allow_empty'])) {
          // Complex fields can be set so that they optionally
          // have content, and if they don't they must be
          // counted as complete
          $this->completeness[$element_namespace] = TRUE;
        }
        continue;
      }

      // Choose the right plugin for the field type.
      $plugin = fc_get_plugin($field['type']);

      if ($function = ctools_plugin_get_function($plugin, 'completeness check')) {
        // Process the field to determine whether it's complete, normally we
        // just compare the number of complete field_items with the cardinality
        // but other fields might be more complex (like matrix fields).
        $cardinality = ctools_plugin_get_function($plugin, 'cardinality check');
        $this->completeness[$element_namespace] = $cardinality($function, $field_items, $instance, $field);
      }
    }

    $count_fields = count($this->completeness);
    $complete_fields = count(array_filter($this->completeness));

    $this->complete = $count_fields == $complete_fields;
    $this->percentage = $count_fields ? (int) ($complete_fields * 100 / $count_fields) : 100;
  }
}
