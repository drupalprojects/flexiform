<?php
/**
 * @file
 * Page and page title callbacks for flexiform.
 */

/**
 * Title Callback for Flexiform Submission Pages.
 */
function flexiform_page_title($flexiform, $display = 'flexiform_create_entity_page', $base_entity_id = FALSE) {
  if (!is_object($flexiform)) {
    $flexiform = flexiform_load($flexiform);
  }

  if (!$flexiform) {
    return FALSE;
  }

  $context = array(
    'base_entity_id' => $base_entity_id,
  );
  return $flexiform->getDisplay($display)->title($context);
}

/**
 * Page callback for flexiform submission pages.
 */
function flexiform_page_wrapper($flexiform, $display = 'flexiform_create_entity_page', $base_entity_id = FALSE) {
  if (!is_object($flexiform)) {
    $flexiform = flexiform_load($flexiform);
  }

  if (!$flexiform) {
    return drupal_not_found();
  }

  $context = array(
    'base_entity_id' => $base_entity_id,
  );
  return $flexiform->getDisplay($display)->build($context);
}
