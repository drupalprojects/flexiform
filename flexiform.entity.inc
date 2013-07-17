<?php
/**
 * @file flexiform.entity.inc
 *
 * Entity class and controller for the flexiform entity
 */

/**
 * The class used for flexiform entities
 */
class Flexiform extends Entity {

  public $form;
  public $label;
  public $elements = array();
  public $entities = array();

  public function __construct($values = array()) {
    parent::__construct($values, 'flexiform');
  }

  public function getBuilder($base_entity) {
    $builder = flexiform_builder_info($this->builder);
    $class_name = $builder['class'];

    if (!class_exists($class_name)) {
      throw new Exception('Builder class does not exist!');
    }

    return new $class_name($this, $base_entity);
  }

  public function addElement($element) {
    $this->elements[$element->getElementNamespace()] = $element->toSettingsArray();
  }

  public function removeElement($element) {
    unset($this->elements[$element->getElementNamespace()]);
  }

  public function updateElement($element) {
    $this->elements[$element->getElementNamespace()] = $element->toSettingsArray();
  }
}

/**
 * The Controller for FlexiformSubmission entities
 */
class FlexiformController extends EntityAPIControllerExportable {
  public function __construct($entityType) {
    parent::__construct($entityType);
  }

   /**
   * Create a flexiform - we first set up the values that are specific
   * to our flexiform schema but then also go through the EntityAPIController
   * function.
   *
   * @param $type
   *   The machine-readable type of the flexiform_submission.
   *
   * @return
   *   A flexiform object with all default fields initialized.
   */
  public function create(array $values = array()) {
    // Add values that are specific to our FlexiformSubmission
    $values += array(
      'id' => '',
      'is_new' => TRUE,
    );
    $flexiform = parent::create($values);

    return $flexiform;
  }

}
