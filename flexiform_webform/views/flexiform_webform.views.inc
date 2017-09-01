<?php

/**
 * @file
 * Providing extra functionality for the Model UI via views.
 */


/**
 * Implements hook_views_data()
 */
function flexiform_webform_views_data_alter(&$data) {
  $data['flexiform_webform_submission']['link_flexiform_webform_submission'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a link to the flexiform_webform_submission.'),
      'handler' => 'flexiform_webform_submission_handler_link_field',
    ),
  );
  $data['flexiform_webform_submission']['edit_flexiform_webform_submission'] = array(
    'field' => array(
      'title' => t('Edit Link'),
      'help' => t('Provide a link to the edit form for the flexiform_webform_submission.'),
      'handler' => 'flexiform_webform_submission_handler_edit_link_field',
    ),
  );
  $data['flexiform_webform_submission']['delete_flexiform_webform_submission'] = array(
    'field' => array(
      'title' => t('Delete Link'),
      'help' => t('Provide a link to delete the flexiform_webform_submission.'),
      'handler' => 'flexiform_webform_submission_handler_delete_link_field',
    ),
  );
  // This content of this field are decided based on the menu structure that
  // follows flexiform submissions/flexiform_webform_submission/%flexiform_webform_submission_id/op
  $data['flexiform_webform_submission']['operations'] = array(
    'field' => array(
      'title' => t('Operations links'),
      'help' => t('Display all operations available for this flexiform_webform_submission.'),
      'handler' => 'flexiform_webform_submission_handler_flexiform_webform_submission_operations_field',
    ),
  );
}


/**
 * Implements hook_views_default_views().
 */
function flexiform_webform_views_default_views() {
  $views = array();
  $view = new view();
  $view->name = 'flexiform_webform_submissions';
  $view->description = 'A list of all flexiform submissions';
  $view->tag = 'flexiform submissions';
  $view->base_table = 'flexiform_webform_submission';
  $view->human_name = 'Flexiform Submissions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Flexiform Submission';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'create any flexiform_webform_submission type';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'name' => 'name',
    'flexiform_webform_submission_id' => 'flexiform_webform_submission_id',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'name' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'flexiform_webform_submission_id' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
  );
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'Empty ';
  $handler->display->display_options['empty']['area']['content'] = 'No flexiform submissions have been created yet';
  /* Field: Flexiform Webform Submission: Flexiform webform submission ID */
  $handler->display->display_options['fields']['id']['id'] = 'id';
  $handler->display->display_options['fields']['id']['table'] = 'flexiform_webform_submission';
  $handler->display->display_options['fields']['id']['field'] = 'id';
  $handler->display->display_options['fields']['id']['label'] = 'ID';
  $handler->display->display_options['fields']['id']['separator'] = '';
  /* Field: Flexiform Webform Submission: Webform */
  $handler->display->display_options['fields']['webform']['id'] = 'webform';
  $handler->display->display_options['fields']['webform']['table'] = 'flexiform_webform_submission';
  $handler->display->display_options['fields']['webform']['field'] = 'webform';
  $handler->display->display_options['fields']['webform']['label'] = 'Type';
  /* Field: Flexiform Webform Submission: Link */
  $handler->display->display_options['fields']['link_flexiform_webform_submission']['id'] = 'link_flexiform_webform_submission';
  $handler->display->display_options['fields']['link_flexiform_webform_submission']['table'] = 'flexiform_webform_submission';
  $handler->display->display_options['fields']['link_flexiform_webform_submission']['field'] = 'link_flexiform_webform_submission';
  $handler->display->display_options['fields']['link_flexiform_webform_submission']['label'] = 'View';
  /* Field: Flexiform Webform Submission: Operations links */
  $handler->display->display_options['fields']['operations']['id'] = 'operations';
  $handler->display->display_options['fields']['operations']['table'] = 'flexiform_webform_submission';
  $handler->display->display_options['fields']['operations']['field'] = 'operations';

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'flexiform_webform_submissions_admin_page');
  $handler->display->display_options['path'] = 'admin/content/flexiform_webform_submissions/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'List';
  $handler->display->display_options['menu']['weight'] = '-10';
  $handler->display->display_options['tab_options']['type'] = 'tab';
  $handler->display->display_options['tab_options']['title'] = 'Flexiform Submissions';
  $handler->display->display_options['tab_options']['description'] = 'Manage flexiform submissions';
  $handler->display->display_options['tab_options']['weight'] = '0';
  $handler->display->display_options['tab_options']['name'] = 'management';
  $translatables['flexiform_webform_submissions'] = array(
    t('Master'),
    t('Flexiform Submission'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('« first'),
    t('‹ previous'),
    t('next ›'),
    t('last »'),
    t('Empty '),
    t('No flexiform submissions have been created yet'),
    t('ID'),
    t('.'),
    t('Type'),
    t('View'),
    t('Operations links'),
    t('Page'),
  );
  $views['flexiform_webform_submissions'] = $view;
  return $views;
}
