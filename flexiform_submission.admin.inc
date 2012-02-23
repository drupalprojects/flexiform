<?php

/**
 * @file
 * FlexiformSubmission editing UI.
 *
 * We make very little use of the EntityAPI interface for this - preferring instead to use
 * views. That offers more flexibility to change a UI that will, more often than not,
 * be end-user facing.
 */

/**
 * UI controller.
 */
class FlexiformSubmissionUIController extends EntityDefaultUIController {

  /**
   * Overrides hook_menu() defaults. Main reason for doing this is that
   * parent class hook_menu() is optimized for entity type administration.
   */
  public function hook_menu() {
    
    $items = array();
    $id_count = count(explode('/', $this->path));
    $wildcard = isset($this->entityInfo['admin ui']['menu wildcard']) ? $this->entityInfo['admin ui']['menu wildcard'] : '%' . $this->entityType;

    $items[$this->path] = array(
      'title' => 'Flexiform Submissions',
      'description' => 'Add edit and update flexiform_submissions.',
      'page callback' => 'system_admin_menu_block_page',
      'access arguments' => array('access administration pages'),
      'file path' => drupal_get_path('module', 'system'),
      'file' => 'system.admin.inc',
    );
    
    // Change the overview menu type for the list of flexiform_submissions.
    $items[$this->path]['type'] = MENU_LOCAL_TASK;
    
    // Change the add page menu to multiple types of entities
    $items[$this->path . '/add'] = array(
      'title' => 'Add a Flexiform Submission',
      'description' => 'Add a new flexiform_submission',
      'page callback'  => 'flexiform_submission_add_page',
      'access callback'  => 'flexiform_submission_access',
      'access arguments' => array('edit'),
      'type' => MENU_NORMAL_ITEM,
      'weight' => 20,
      'file' => 'flexiform_submission.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module'])

    );
    
    // Add menu items to add each different type of entity.
    foreach (flexiform_get_flexiforms() as $form) {
      $items[$this->path . '/add/' . $form->form] = array(
        'title' => 'Add ' . $form->label,
        'page callback' => 'flexiform_submission_form_wrapper',
        'page arguments' => array(flexiform_submission_create(array('form' => $form->form))),
        'access callback' => 'flexiform_submission_access',
        'access arguments' => array('submit', $form->form),
        'file' => 'flexiform_submission.admin.inc',
        'file path' => drupal_get_path('module', $this->entityInfo['module'])
      );
    }

    // Loading and editing flexiform_submission entities
    $items[$this->path . '/flexiform_submission/' . $wildcard] = array(
      'page callback' => 'flexiform_submission_form_wrapper',
      'page arguments' => array($id_count + 1),
      'access callback' => 'flexiform_submission_access',
      'access arguments' => array('edit', $id_count + 1),
      'weight' => 0,
      'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
      'file' => 'flexiform_submission.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module'])
    );
    $items[$this->path . '/flexiform_submission/' . $wildcard . '/edit'] = array(
      'title' => 'Edit',
      'type' => MENU_DEFAULT_LOCAL_TASK,
      'weight' => -10,
      'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
    );
    
    $items[$this->path . '/flexiform_submission/' . $wildcard . '/delete'] = array(
      'title' => 'Delete',
      'page callback' => 'flexiform_submission_delete_form_wrapper',
      'page arguments' => array($id_count + 1),
      'access callback' => 'flexiform_submission_access',
      'access arguments' => array('edit', $id_count + 1),
      'type' => MENU_LOCAL_TASK,
      'context' => MENU_CONTEXT_INLINE,
      'weight' => 10,
      'file' => 'flexiform_submission.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module'])
    );
    
    // Menu item for viewing flexiform_submissions
    $items['flexiform_submission/' . $wildcard] = array(
      //'title' => 'Title',
      'title callback' => 'flexiform_submission_page_title',
      'title arguments' => array(1),
      'page callback' => 'flexiform_submission_page_view',
      'page arguments' => array(1),
      'access callback' => 'flexiform_submission_access',
      'access arguments' => array('view', 1),
      'type' => MENU_CALLBACK,
    );
    return $items;
  }
  
  
  /**
   * Create the markup for the add FlexiformSubmission Entities page within the class
   * so it can easily be extended/overriden.
   */ 
  public function addPage() {
    $item = menu_get_item();
    $content = system_admin_menu_block($item);

    if (count($content) == 1) {
      $item = array_shift($content);
      drupal_goto($item['href']);
    }    
        
    return theme('flexiform_submission_add_list', array('content' => $content));
  }
  
}


/**
 * Form callback wrapper: create or edit a flexiform_submission.
 *
 * @param $flexiform_submission
 *   The flexiform_submission object being edited by this form.
 *
 * @see flexiform_submission_edit_form()
 */
function flexiform_submission_form_wrapper($flexiform_submission) {
  // Add the breadcrumb for the form's location.
  flexiform_submission_set_breadcrumb();
  return drupal_get_form('flexiform_submission_edit_form', $flexiform_submission);
}


/**
 * Form callback wrapper: delete a flexiform_submission.
 *
 * @param $flexiform_submission
 *   The flexiform_submission object being edited by this form.
 *
 * @see flexiform_submission_edit_form()
 */
function flexiform_submission_delete_form_wrapper($flexiform_submission) {
  // Add the breadcrumb for the form's location.
  //flexiform_submission_set_breadcrumb();
  return drupal_get_form('flexiform_submission_delete_form', $flexiform_submission);
}


/**
 * Form callback: create or edit a flexiform_submission.
 *
 * @param $flexiform_submission
 *   The flexiform_submission object to edit or for a create form an empty flexiform_submission object
 *     with only a flexiform_submission form defined.
 */
function flexiform_submission_edit_form($form, &$form_state, $flexiform_submission) {  
  // Add the field related form elements.
  $form_state['flexiform_submission'] = $flexiform_submission;
  field_attach_form('flexiform_submission', $flexiform_submission, $form, $form_state);

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 400,
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save flexiform_submission'),
    '#submit' => $submit + array('flexiform_submission_edit_form_submit'),
  );
  
  if (!empty($flexiform_submission->name)) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete flexiform_submission'),
      '#suffix' => l(t('Cancel'), 'admin/content/flexiform_submissions'),
      '#submit' => $submit + array('flexiform_submission_form_submit_delete'),
      '#weight' => 45,
    );
  }

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'flexiform_submission_edit_form_validate';
  return $form;
}


/**
 * Form API validate callback for the flexiform_submission form
 */
function flexiform_submission_edit_form_validate(&$form, &$form_state) {
  $flexiform_submission = $form_state['flexiform_submission'];
  
  // Notify field widgets to validate their data.
  field_attach_form_validate('flexiform_submission', $flexiform_submission, $form, $form_state);
}


/**
 * Form API submit callback for the flexiform_submission form.
 * 
 * @todo remove hard-coded link
 */
function flexiform_submission_edit_form_submit(&$form, &$form_state) {
  
  $flexiform_submission = entity_ui_controller('flexiform_submission')->entityFormSubmitBuildEntity($form, $form_state);
  // Save the flexiform_submission and go back to the list of flexiform_submissions
  
  // Add in created and changed times.
  if ($flexiform_submission->is_new = isset($flexiform_submission->is_new) ? $flexiform_submission->is_new : 0){
    $flexiform_submission->created = time();
  }

  $flexiform_submission->changed = time();
  
  $flexiform_submission->save();
  $form_state['redirect'] = 'admin/content/flexiform_submissions';
}

/**
 * Form API submit callback for the delete button.
 * 
 * @todo Remove hard-coded path
 */
function flexiform_submission_form_submit_delete(&$form, &$form_state) {
  $form_state['redirect'] = 'admin/content/flexiform_submissions/flexiform_submission/' . $form_state['flexiform_submission']->flexiform_submission_id . '/delete';
}


/**
 * Form callback: confirmation form for deleting a flexiform_submission.
 *
 * @param $flexiform_submission
 *   The flexiform_submission to delete
 *
 * @see confirm_form()
 */
function flexiform_submission_delete_form($form, &$form_state, $flexiform_submission) {
  $form_state['flexiform_submission'] = $flexiform_submission;

  $form['#submit'][] = 'flexiform_submission_delete_form_submit';

  $form = confirm_form($form,
    t('Are you sure you want to delete flexiform_submission %name?', array('%name' => $flexiform_submission->name)),
    'admin/content/flexiform_submissions/flexiform_submission',
    '<p>' . t('This action cannot be undone.') . '</p>',
    t('Delete'),
    t('Cancel'),
    'confirm'
  );
  
  return $form;
}

/**
 * Submit callback for flexiform_submission_delete_form
 */
function flexiform_submission_delete_form_submit($form, &$form_state) {
  $flexiform_submission = $form_state['flexiform_submission'];

  flexiform_submission_delete($flexiform_submission);

  drupal_set_message(t('The flexiform_submission %name has been deleted.', array('%name' => $flexiform_submission->name)));
  watchdog('flexiform_submission', 'Deleted flexiform_submission %name.', array('%name' => $flexiform_submission->name));

  $form_state['redirect'] = 'admin/content/flexiform_submissions';
}



/**
 * Page to add FlexiformSubmission Entities.
 *
 * @todo Pass this through a proper theme function
 */
function flexiform_submission_add_page() {
  $controller = entity_ui_controller('flexiform_submission');
  return $controller->addPage();
}


/**
 * Displays the list of available flexiforms for flexiform_submission creation.
 *
 * @ingroup themeable
 */
function theme_flexiform_submission_add_list($variables) {
  $content = $variables['content'];
  $output = '';
  if ($content) {
    $output = '<dl class="flexiform_submission-type-list">';
    foreach ($content as $item) {
      $output .= '<dt>' . l($item['title'], $item['href']) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
    }
    $output .= '</dl>';
  }
  else {
    if (user_access('administer flexiforms')) {
      $output = '<p>' . t('FlexiformSubmission Entities cannot be added because you have not created any flexiforms yet. Go to the <a href="@create-flexiform_submission-type">flexiform creation page</a> to add a new flexiform.', array('@create-flexiform_submission-type' => url('admin/structure/flexiforms/add'))) . '</p>';
    }
    else {
      $output = '<p>' . t('No flexiforms have been created yet for you to use.') . '</p>';
    }
  }

  return $output;
}





/**
 * Sets the breadcrumb for administrative flexiform_submission pages.
 */
function flexiform_submission_set_breadcrumb() {
  $breadcrumb = array(
    l(t('Home'), '<front>'),
    l(t('Administration'), 'admin'),
    l(t('Content'), 'admin/content'),
    l(t('FlexiformSubmissions'), 'admin/content/flexiform_submissions'),
  );

  drupal_set_breadcrumb($breadcrumb);
}


