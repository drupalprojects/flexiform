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
  
  public function __construct($values = array()) {
    parent::__construct($values, 'flexiform');
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