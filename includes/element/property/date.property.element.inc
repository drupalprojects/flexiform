<?php
/**
 * @file
 * Contains FlexiformElementEntityProperty_date.
 */

class FlexiformElementEntityProperty_date extends FlexiformElementEntityProperty {

  public function form($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $form = parent::form($form, $form_state, $entity, $language);

    if (module_exists('date_popup')) {
      $form[$this->element_namespace]['#type'] = 'date_popup';
      $form[$this->element_namespace]['#date_format'] = 'M d Y H:i';

      if ($default = $form[$this->element_namespace]['#default_value']) {
        $form[$this->element_namespace]['#default_value'] = date('Y-m-d H:i', $form[$this->element_namespace]['#default_value']);
      }
    }

    return $form;
  }

  public function formExtractValues($form, &$form_state, $entity) {
    $value = parent::formExtractValues($form, $form_state, $entity);

    return strtotime($value);
  }
}
