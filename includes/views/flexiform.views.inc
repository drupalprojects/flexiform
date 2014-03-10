<?php
/**
 * @file
 * Contains views integration for tags.
 */

/**
 * Implements hook_views_data_alter().
 */
function flexiform_views_data_alter(&$data) {
  $data['flexiform']['tags'] = array(
    'title' => t('Tags'),
    'help' => t('Flexible form tags.'),
    'field' => array(
      'real field' => 'id',
      'handler' => 'flexiform_handler_field_tags',
      'click sortable' => FALSE,
    ),
  );

  $data['flexiform_tags'] = array(
    'table' => array(
      'join' => array(
        'flexiform' => array(
          'left_field' => 'id',
          'field' => 'id',
        ),
      ),
    ),
    'tag' => array(
      'group' => 'Flexiform',
      'title' => 'Tags',
      'title short' => 'Tags',
      'help' => 'Flexiform Tags',
      'filter' => array(
        'field' => 'tag',
        'table' => 'flexiform_tags',
        'handler' => 'flexiform_handler_filter_tags',
        'allow empty' => TRUE,
      ),
    ),
  );
}
