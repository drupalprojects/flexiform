<?php
/**
 * @file
 *  Contains class for a basic entity getter.
 */

/**
 * Form Entity Class for getting users from commerce order.
 */
class FlexiformFormEntityCommerceOrderUser extends FlexiformFormEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    parent::getEntity();

    // Get the base order
    $order = $this->getParam('base');

    // Check we have enough information to load the entity.
    if (!$order) {
      return FALSE;
    }

    return entity_load_single('user', $order->uid);
  }
}
