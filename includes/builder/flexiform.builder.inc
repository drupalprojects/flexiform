<?php
/**
 * @file
 * Contains the FlexiformBuilderFlexiform Class
 */

/**
 * Flexiform Builder Class for the complex flexiforms.
 */
class FlexiformBuilderFlexiform extends FlexiformBuilder {

  /**
   * The Entity Manager for this form.
   * @var FlexiformFormEntityManagerInterface
   */
  protected $entityManager;

  /**
   * Get the entity manager.
   *
   * @return FlexiformFormEntityManagerInterface
   *   The entity manager for this form builder.
   */
  public function getEntityManager() {
    if (!empty($this->entityManager)) {
      return $this->entityManager;
    }

    $this->entityManager = new FlexiformFormEntityManagerDefault($this->flexiform->entities, $this);
    $this->entityManager->setBaseEntity($this->base_entity);
    return $this->entityManager;
  }

  /**
   * Get a form entity.
   *
   * @param $namespace
   *  The namespace of the entity required.
   *
   * @return
   *  The entity with that namespace.
   */
  public function getFormEntity($namespace) {
    return $this->getEntityManager()->getEntity($namespace);
  }

  /**
   * Build the form for this flexiform.
   */
  public function form($form, &$form_state) {
    $form = parent::form($form, $form_state);

    $form['#flexiform_entities'] = $this->getEntityManager()->getEntities();

    // Set up our form bits
    if (!isset($form['#parents'])) {
      $form['#parents'] = array();
    }

    // Iterate over all elements.
    foreach ($this->flexiform->elements as $element_namespace => $settings) {
      $element = FlexiformElement::getElement($this->flexiform, $element_namespace);

      // Don't show form elements for entities that don't exist.
      if (!$this->getFormEntity($element->getEntityNamespace())) {
        continue;
      }

      // Make sure this element has the correct parents.
      array_push($form['#parents'], $element->getEntityNamespace());
      $form += $element->form($form, $form_state, $this->getFormEntity($element->getEntityNamespace()));
      array_pop($form['#parents']);
    }

    $this->invoke($form, $form_state);

    return $form;
  }

  /**
   * Validate the input for the form.
   */
  public function formValidate($form, &$form_state) {
    foreach (element_children($form) as $element_namespace) {
      if (empty($form[$element_namespace]['#flexiform_element'])) {
        continue;
      }

      $element = $form[$element_namespace]['#flexiform_element'];
      $entity = $this->getFormEntity($element->getEntityNamespace());
      $element->formValidate($form, $form_state, $entity);
    }

    $this->invoke($form, $form_state, 'validate');
  }

  /**
   * Submit the form.
   */
  public function formSubmit($form, &$form_state) {
    $flexiform = $form['#flexiform'];

    foreach (element_children($form) as $element_namespace) {
      if (empty($form[$element_namespace]['#flexiform_element'])) {
        continue;
      }

      $element = $form[$element_namespace]['#flexiform_element'];
      $entity = $this->getFormEntity($element->getEntityNamespace());
      $element->formSubmit($form, $form_state, $entity);
    }

    $this->invoke($form, $form_state, 'submit');

    $this->getEntityManager()->saveEntities();

    if (module_exists('rules')) {
      $base_entity_wrapper = entity_metadata_wrapper($flexiform->base_entity, $form['#flexiform_base_entity']);
      $args = array(
        'base_entity' => $base_entity_wrapper,
      );

      // Fire the most specific rules first and track back to the less specific.
      rules_invoke_event_by_args('flexiform_submit_'.$this->flexiform->form, $form['#flexiform_entities']);
      rules_invoke_event_by_args('flexiform_group_submit_'.$this->flexiform->form_group, $args);
      rules_invoke_event_by_args('flexiform_any_submit', $args);
    }
  }
}
