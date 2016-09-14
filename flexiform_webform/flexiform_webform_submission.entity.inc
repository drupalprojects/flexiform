<?php
/**
 * @file
 * Contains Entity Class for Flexiform Webform Submissions
 */

/**
 * The class used for flexiform_webform_submission entities
 */
class FlexiformWebformSubmission extends Entity {

  public $id;

  public $webform;

  public $language;

  public $created;

  public $changed;

  public $user;

  public function __construct($values = array()) {
    parent::__construct($values, 'flexiform_webform_submission');
  }

  protected function defaultLabel() {
    $flexiform_webform = flexiform_webform_load($this->webform);
    return $flexiform_webform->label . ' ' . $this->id;
  }

  protected function defaultUri() {
    return array('path' => 'flexiform_submission/' . $this->id);
  }
}

class FlexiformWebformSubmissionController extends EntityAPIController {

  /**
   * {@inheritdoc}
   */
  public function invoke($hook, $entity) {
    global $user;

    if ($hook == 'presave') {
      if (empty($entity->created)) {
        $entity->created = REQUEST_TIME;
      }
      if (empty($entity->user)) {
        $entity->user = $user->uid;
      }
      $entity->changed = REQUEST_TIME;
    }

    parent::invoke($hook, $entity);
  }
}

