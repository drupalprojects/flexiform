<?php
/**
 * @file
 * Contains FlexiformElementField_entityreference class.
 */

/**
 * Class for entityreference Field API elements.
 */
class FlexiformElementField_entityreference extends FlexiformElementField {

  /**
   * {@inheritdoc}
   */
  public function form($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $form = parent::form($form, $form_state, $entity, $language);

    if ($this->getWidgetType() == 'entityreference_autocomplete') {
      foreach (element_children($form[$this->element_namespace][$language]) as $delta) {
        $element = &$form[$this->element_namespace][$language][$delta]['target_id'];
        $this->formAlterAutocompleteElement($element, $entity);
      }
    }
    else if ($this->getWidgetType() == 'entityreference_autocomplete_tags') {
      $this->formAlterAutocompleteElement($form[$this->element_namespace][$language], $entity);
    }

    return $form;
  }

  /**
   * Alter the selection element.
   */
  protected function formAlterAutocompleteElement(&$element, $entity) {
    $ac_path = ($this->getWidgetType() == 'entityreference_autocomplete') ? 'flexiform/entityreference/autocomplete/single' : 'flexiform/entityreference/autocomplete/tags';
    $element['#autocomplete_path'] =  $ac_path.'/'.$this->flexiform->form.'/'.$this->element_namespace;

    if ($entity_id = entity_id($this->entity_type, $entity)) {
      $element['#autocomplete_path'] .= "/{$entity_id}";
    }
    else {
      $element['#autocomplete_path'] .= "/NULL";
    }

    foreach ($element['#element_validate'] as &$callback) {
      if ($callback == '_entityreference_autocomplete_validate') {
        $callback = array($this, 'autocompleteValidate');
      }
      if ($callback == '_entityreference_autocomplete_tags_validate') {
        $callback = array($this, 'autocompleteTagsValidate');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formSubmit($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    parent::formSubmit($form, $form_state, $entity, $language);

    // Make us compatible with the inline_entity_form module.
    // @todo: Consider just invoking this by default.
    if (module_exists('inline_entity_form')) {
      inline_entity_form_field_attach_submit($this->getEntityType(), $entity, $this->extractFormElements($form), $form_state);
    }

    return $form;
  }

  /**
   * Validate any autocomplete input.
   *
   * @see _entityreference_autocomplete_validate
   */
  public function autocompleteValidate($element, &$form_state, $form) {
    $value = '';
    if (!empty($element['#value'])) {
      // Take "label (entity id)", match the id from parenthesis
      if (preg_match("/.+\((\d+)\)/", $element['#value'], $matches)) {
        $value = $matches[1];
      }
      else {
        // Try to get a match from the input string when the user didn't use the
        // autocomplete but filled in a value manually.
        $field = $this->getField();
        $instance = $this->getInstance();
        $handler = entityreference_get_selection_handler($field, $instance, $element['#entity_type'], $element['#entity']);
        $value = $handler->validateAutocompleteInput($element['#value'], $element, $form_state, $form);
      }
    }
    form_set_value($element, $value, $form_state);
  }

  /**
   * Validate tags autocomplete input.
   *
   * @see _entityreference_autocomplete_tags_validate
   */
  public function autocompleteTagsValidate($element, &$form_state, $form) {
    $value = array();

    // If a value was entered into the autocomplete...
    if (!empty($element['#value'])) {
      $entities = drupal_explode_tags($element['#value']);
      foreach ($entities as $entity) {
        if (preg_match("/.+\((\d+)\)/", $entity, $matches)) {
           $value[] = array(
             'target_id' => $matches[1],
           );
        }
        else {
          $field = $this->getField();
          $instance = $this->getInstance();
          $handler = entityreference_get_selection_handler($field, $instance, $element['#entity_type'], $element['#entity']);
          $value[] = array(
            'target_id' => $handler->validateAutocompleteInput($entity, $element, $form_state, $form),
          );
        }
      }
    }
    form_set_value($element, $value, $form_state);
  }
}
