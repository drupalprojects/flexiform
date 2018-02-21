<?php

/**
 * @file
 * All the flexiform hooks.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityInterface;

/**
 * Implements hook_flexiform_form_entity_entity_create().
 */
function flexiform_flexiform_form_entity_entity_create(EntityInterface $entity, FlexiformFormEntityInterface $plugin) {
  // Add the owner property to created profiles.
  if ($entity->getEntityTypeId() == 'profile' && $plugin->getBaseId() == 'referenced_entity') {
    $base = $plugin->getContextValue('base');
    if ($base->getEntityTypeId() == 'user') {
      $entity->setOwner($base);
    }
  }
}
