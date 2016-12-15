<?php
/**
 * @file
 * Entityreference integration with flexiform.
 */

/**
 * Autocomplete callback for entityreference fields.
 */
function flexiform_entityreference_autocomplete_callback($type, $flexiform, $element_namespace, $entity_id = '', $string = '') {
  $args = func_get_args();
  // Shift off the type, flexiform, element_namespace and entity_id.
  array_shift($args);
  array_shift($args);
  array_shift($args);
  array_shift($args);
  $string = implode('/', $args);

  $element = FlexiformElement::getElement($flexiform, $element_namespace);
  return entityreference_autocomplete_callback_get_matches(
    $type,
    $element->getField(),
    $element->getInstance(),
    $element->getEntityType(),
    $entity_id,
    $string
  );
}
