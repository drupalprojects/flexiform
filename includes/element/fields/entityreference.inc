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

    if ($this->getWidgetType() != 'entityreference_autocomplete') {
      return $form;
    }

    foreach (element_children($form[$this->element_namespace][$language]) as $delta) {
      $element = &$form[$this->element_namespace][$language][$delta]['target_id'];
      $element['#autocomplete_path'] = 'flexiform/entityreference/autocomplete/single/'.$this->flexiform->form.'/'.$this->element_namespace;

      if ($entity_id = entity_id($this->entity_type, $entity)) {
        $element['#autocomplete_path'] .= "/{$entity_id}";
      }
      else {
        $element['#autocomplete_path'] .= "/NULL";
      }
    }

    return $form;
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

}
