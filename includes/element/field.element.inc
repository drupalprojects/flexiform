<?php
/**
 * @file
 * Contains FlexiformElementField class.
 */

/**
 * Class for Field API elements.
 */
class FlexiformElementField extends FlexiformElement implements FlexiformElementFieldAPIInterface {

  /**
   * The field name of the field.
   */
  protected $field_name;

  /**
   * The instance settings for the field
   */
  protected $instance;

  /**
   * The field settings for the field.
   */
  protected $field;

  /**
   * Overrides FlexiformElement::__construct().
   */
  public function __construct($flexiform, $settings, $element_namespace = '') {
    parent::__construct($flexiform, $settings, $element_namespace);
    $this->field_name = $this->element_info['field_name'];

    // Build the fake instance for this form (incase the widget has changed -
    // we would normally have to be careful of default_value but that doesn't
    // figure here, so we should be fine.
    $this->instance = !empty($settings['instance']) ? $settings['instance'] : array();
    $this->field = !empty($settings['field']) ? $settings['field'] : array();

    // Get the initial weight.
    $instance = $this->getInstance();
    $this->weight = isset($settings['weight']) ? $settings['weight'] : $instance['widget']['weight'];
  }

  /**
   * Overrides FlexiformElement::label().
   *
   * @return string
   *   The field label.
   */
  public function label() {
    $instance = $this->getInstance();
    return check_plain($instance['label']);
  }

  /**
   * Overrides FlexiformElement::setLabel().
   */
  public function setLabel($label) {
    $this->instance['label'] = $label;
  }

  /**
   * Return the type.
   */
  public function type() {
    return 'Field';
  }

  /**
   * Get the widget type from the instance settings.
   *
   * @return string
   *   Machine name of the field widget in use.
   */
  public function getWidgetType() {
    $instance = $this->getInstance();
    return $instance['widget']['type'];
  }

  /**
   * Get the widget label from the instance settings.
   *
   * @return string
   *   Human readable name of the field widget.
   */
  public function getWidgetLabel() {
    $widget_types = field_info_widget_types();
    return $widget_types[$this->getWidgetType()]['label'];
  }

  /**
   * Get the instance array.
   *
   * At this point we merge the instance settings for the form into the normal
   * field instance settings.
   */
  public function getInstance() {
    if (empty($this->full_instance)) {
      $field_instance = field_info_instance($this->entity_type, $this->field_name, $this->bundle);
      $this->full_instance = array_replace_recursive($field_instance, $this->instance);

      // Default value might be a numerically indexed array, so we'll overwrite
      if (isset($this->instance['default_value'])) {
        $this->full_instance['default_value'] = $this->instance['default_value'];
      }

      // Here we check if a default value function is used.
      if (isset($this->instance['use_default_value_function'])) {
        if (empty($this->instance['use_default_value_function'])) {
          unset($this->full_instance['default_value_function']);
        }
        else {
          unset($this->full_instance['default_value']);
        }
      }
    }

    return $this->full_instance;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    parent::setWeight($weight);

    $this->instance['widget']['weight'] = $weight;
  }

  /**
   * Check widget behavior.
   *
   * @param array $instance
   *   (optional) The instance array to check the widget behavior of. Defaults
   *   to the result of getInstance().
   * @param string $op
   *   (optional) The $op to check the behavior of, either 'default value' (default) or 'multiple values'.
   * @param $behavior
   *   (optional) The behavior.
   *
   * @see field_behaviors_widget()
   */
  public function checkWidgetBehavior($instance = array(), $op = 'default value', $behavior = FIELD_BEHAVIOR_DEFAULT) {
    if (empty($instance)) {
      $instance = $this->getInstance();
    }

    return (field_behaviors_widget($op, $instance) == $behavior);
  }

  /**
   * Get the Difference of two settings arrays.
   *
   * @see http://uk1.php.net/manual/en/function.array-diff-assoc.php#111675
   *
   * @todo: Build in special casing for default values.
   */
  public function diffArrays($array, $original_array) {
    $difference = array();
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        if (!isset($original_array[$key]) || !is_array($original_array[$key])) {
          $difference[$key] = $value;
        }
        else {
          $new_diff = $this->diffArrays($value, $original_array[$key]);
          if (!empty($new_diff)) {
            $difference[$key] = $new_diff;
          }
        }
      }
      elseif (!array_key_exists($key,$original_array) || $original_array[$key] !== $value) {
        $difference[$key] = $value;
      }
    }
    return $difference;
  }

  /**
   * Get the field array.
   *
   * At this point we merge the field settings for the form into the normal
   * field settings.
   */
  public function getField() {
    if (empty($this->full_field)) {
      $field = field_info_field($this->field_name);
      $this->full_field = array_replace_recursive($field, $this->field);
    }

    return $this->full_field;
  }

  /**
   * Add the Field Settings section to the config form.
   *
   * We add this method separately as it allows us to pass our overridden field
   * settings to the form, whereas field_ui_field_edit_form assumes
   * field_info_field will provide the correct values.
   *
   * @param array $element
   *   The Field Settings section of the form.
   * @param array &$form_state
   *   The form state.
   * @param array $instance
   *   Instance settings.
   * @param array $field
   *   The field settings.
   *
   * @return array
   *   The form element for the field settings.
   */
  public function configureFieldSettingsForm($element, $form_state, $field, $instance) {
    $has_data = field_has_data($field);

    // Create a form structure for the field values.
    $element = array(
      '#type' => 'fieldset',
      '#title' => t('Field Settings'),
      '#description' => t('These settings will override default Field API settings for this field. Overrides will only apply to this flexiform. <strong>Handle with care: Altering these settings can cause unexpected results.</strong>'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );

    // Build the configurable field values.
    $description = t('Maximum number of values users can enter for this field.');
    if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_DEFAULT) {
      $description .= '<br/>' . t("'Unlimited' will provide an 'Add more' button so the users can add as many values as they like.");
    }
    $element['cardinality'] = array(
      '#type' => 'select',
      '#title' => t('Number of values'),
      '#options' => array(FIELD_CARDINALITY_UNLIMITED => t('Unlimited')) + drupal_map_assoc(range(1, 10)),
      '#default_value' => $field['cardinality'],
      '#description' => $description,
    );

    // Add additional field type settings. The field type module is
    // responsible for not returning settings that cannot be changed if
    // the field already has data.
    $additions = module_invoke($field['module'], 'field_settings_form', $field, $instance, $has_data);
    if (is_array($additions)) {
      $element['settings'] = $additions;
    }

    return $element;
  }

  /**
   * Overrides FlexiformElement::configureForm().
   */
  public function configureForm($form, &$form_state, $flexiform) {
    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
    $instance = $this->getInstance();
    $field = $this->getField();
    $form = field_ui_field_edit_form($form, $form_state, $instance);

    // Invoke hook_form_alter() and hook_form_field_ui_field_edit_form_alter()
    /*$hooks = array('form');
    $hooks[] = 'form_field_ui_field_edit_form';
    $fake_form_id = 'field_ui_field_edit_form';
    drupal_alter($hooks, $form, $form_state, $fake_form_id);*/

    // If the default value behaviour is default, expose our better default
    // value form.
    if ($this->checkWidgetBehavior()) {
      // Allow changes to the default values regardless of anything else thats
      // going on.
      $form['instance']['use_default_value_function'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use a Default Value Function'),
        '#default_value' => !empty($instance['default_value_function']),
      );
      $form['instance']['default_value_function'] = array(
        '#type' => 'textfield',
        '#title' => t('Default Value Function'),
        '#description' => t('If you wish to use a custom default value function, enter the name of it here. Reccommended for advanced users only.'),
        '#default_value' => isset($instance['default_value_function']) ? $instance['default_value_function'] : NULL,
        '#states' => array('invisible' => array(':input[name="instance[use_default_value_function]"]' => array('checked' => FALSE))),
      );
      $form['instance']['default_value_widget'] = field_ui_default_value_widget($this->getField(), $instance, $form, $form_state);
      $form['instance']['default_value_widget']['#states'] = array(
        'visible' => array(':input[name="instance[use_default_value_function]"]' => array('checked' => FALSE)),
      );
      $form['instance']['use_default_value_tokens'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Tokens in Default Value'),
        '#default_value' => !empty($instance['use_default_value_tokens']),
        '#states' => array(
          'visible' => array(':input[name="instance[use_default_value_function]"]' => array('checked' => FALSE)),
        ),
      );
      $form['instance']['contexts'] = array(
        '#title' => t('Substitutions'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#status' => array(
          'visible' => array(':input[name="instance[use_default_value_tokens]"]' => array('checked' => TRUE)),
        ),
      );
      $form['instance']['contexts']['contexts'] = $this->getCtoolsSubstitutionsList();
    }

    // Rename the boxes and discourage use of field settings.
    $form['instance']['#title'] = t('Form Settings');
    $form['instance']['#description'] = t('These settings will override default Field API settings for this field instance. Overrides will only apply to this flexiform.');
    $form['field'] = $this->configureFieldSettingsForm($form, $form_state, $field, $instance);

    // Delete the 'Save Settings' button added by FieldUI.
    unset($form['actions']);

    $form = parent::configureForm($form, $form_state, $flexiform);

    // Unset the label field added by  FlexiformElementBase.
    unset($form['label']);

    // Allow the field label to be longer.
    $form['instance']['label']['#maxlength'] = NULL;

    return $form;
  }

  /**
   * Overrides FlexiformElement::configureFormValidate().
   */
  public function configureFormValidate($form, &$form_state, $flexiform) {
    if ($this->checkWidgetBehavior()
        && $form_state['values']['instance']['use_default_value_function']) {
      unset($form['instance']['default_value_widget']);
    }

    field_ui_field_edit_form_validate($form, $form_state);
  }

  /**
   * Overrides FlexiformElement::configureFormSubmit().
   */
  public function configureFormSubmit($form, &$form_state, $flexiform) {
    $instance = $form_state['values']['instance'];
    $field = $form_state['values']['field'];

    // Update any field settings that have changed.
    $element_field = $this->getField();
    $field = array_merge($element_field, $field);
    $original_field = field_info_field($instance['field_name']);
    $this->field = $this->diffArrays($field, $original_field);

    // Work out element instance settings. This gets a little complicated as
    // we want to make sure the default value settings work.
    $element_instance = $this->getInstance();
    $instance = array_merge($element_instance, $instance);
    $original_instance = field_read_instance($instance['entity_type'], $instance['field_name'], $instance['bundle']);

    if ($this->checkWidgetBehavior()) {
      if (!$instance['use_default_value_function']) {
        $element = $form['instance']['default_value_widget'];

        // Extract field values.
        $items = array();
        field_default_extract_form_values(NULL, NULL, $element_field, $element_instance, LANGUAGE_NONE, $items, $element, $form_state);
        field_default_submit(NULL, NULL, $element_field, $element_instance, LANGUAGE_NONE, $items, $element, $form_state);

        $instance['default_value'] = $items ? $items : NULL;
      }
      else {
        unset($instance['default_value']);
      }
    }

    // Cast Required to a boolean so it doesn't get erroneously stored.
    $instance['required'] = (bool) $instance['required'];
    $this->instance = $this->diffArrays($instance, $original_instance);

    parent::configureFormSubmit($form, $form_state, $flexiform);
  }

  /**
   * Return the form element for this FlexiformElement.
   */
  public function form($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $items = field_get_items($this->entity_type, $entity, $this->field_name, $language);

    // field_default_form expects $items to be an array or NULL but
    // field_get_items returns FALSE when there are none. If $items is FALSE
    // turn it into an empty array to prevent errors later on.
    if (!$items) {
      $items = array();
    }

    if (!field_info_field($this->field_name)
      || !field_info_instance($this->entity_type, $this->field_name, $this->bundle)) {
      debug("Error in " . __CLASS__ . "::" . __METHOD__ .": Field <em>{$this->field_name}</em> does not exist or does not have an instance on {$this->entity_type}:{$this->bundle}.", "error");
      return $form;
    }

    $instance = $this->getInstance();

    // Deal with default value tokens.
    if ($this->checkWidgetBehavior()
        && !empty($instance['use_default_value_tokens'])) {
      $defaults = &$instance['default_value'];
      foreach ($defaults as $delta => &$item) {
        foreach ($item as &$value) {
          if (is_string($value)) {
            $value = $this->replaceCtoolsSubstitutions($value, $form['#flexiform_entities']);
          }
        }
      }
    }

    $form_fields = field_default_form($this->entity_type, $entity, $this->getField(), $instance, $language, $items, $form, $form_state);
    foreach ($form_fields as $form_key => $form_field) {
      // Make sure the parents stuff is always correct.
      $form_field['#parents'] = $form['#parents'];
      array_push($form_field['#parents'], $this->field_name);

      if (!empty($instance['remove_none'])) {
        unset($form_field[$language]['#options']['']);
        unset($form_field[$language]['#options']['_none']);
      }

      if (isset($instance['attributes']) && is_array($instance['attributes'])) {
        if (isset($form_field['#attributes'])) {
          $form_field['#attributes'] = array_replace_recursive($form_field['#attributes'], $instance['attributes']);
        }
        else {
          $form_field['#attributes'] = $instance['attributes'];
        }
      }

      // Honour the hide title settings.
      if (!empty($this->settings['display_options']['hide_label'])) {
        $form_field[LANGUAGE_NONE]['#title_display'] = 'invisible';
      }

      $form[$this->element_namespace] = $form_field;
    }

    $form = parent::form($form, $form_state, $entity);
    return $form;
  }

  /**
   * Validate this element.
   *
   * This is effectively the same as field_attach_form_validate passed with the
   * field_name options, except that we have replaced _field_invoke and
   * _field_invoke_default with our own methods to make sure the correct field
   * settings get used.
   *
   * @see field_attach_validate()
   * @see field_attach_form_validate()
   */
  public function formValidate($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $entity = clone $entity;
    $form_elements = $this->extractFormElements($form);

    $this->fieldInvokeDefault('extract_form_values', $entity, $form_elements, $form_state);

    try {
      $errors = array();
      $null = NULL;

      $this->fieldInvokeDefault('validate', $entity, $errors, $null);
      $this->fieldInvoke('validate', $entity, $errors, $null);

      // Let other modules validate the entity.
      foreach (module_implements('field_attach_validate') as $module) {
        $function = $module . '_field_attach_validate';
        $function($this->getEntityType(), $entity, $errors);
      }

      if ($errors) {
        throw new FieldValidationException($errors);
      }
    }
    catch (FieldValidationException $e) {
      // Pass field-level validation errors back to widgets for accurate error
      // flagging.
      foreach ($e->errors as $field_name => $field_errors) {
        foreach ($field_errors as $langcode => $errors) {
          $field_state = field_form_get_state($form_elements['#parents'], $field_name, $langcode, $form_state);
          $field_state['errors'] = $errors;
          field_form_set_state($form_elements['#parents'], $field_name, $langcode, $form_state, $field_state);
        }
      }

      $this->fieldInvokeDefault('form_errors', $entity, $form_elements, $form_state);
    }
  }

  /**
   * Invoke default field hooks on this element.
   *
   * @see _field_invoke_default()
   */
  protected function fieldInvokeDefault($op, $entity, &$a = NULL, &$b = NULL, $options = array()) {
    $options['default'] = TRUE;
    return $this->fieldInvoke($op, $entity, $a, $b, $options);
  }

  /**
   * Invoke field hooks on a specific field.
   *
   * This method is the equivelant of _field_invoke. For a specific field in flexiform.
   *
   * @see _field_invoke()
   */
  protected function fieldInvoke($op, $entity, &$a = NULL, &$b = NULL, $options = array()) {
    $entity_type = $this->getEntityType();
    $bundle = $this->bundle;
    $default_options = array(
      'default' => FALSE,
      'deleted' => FALSE,
      'language' => NULL,
    );
    $options += $default_options;

    $return = array();
    $instance = $this->getInstance();
    $field = $this->getField();
    $field_name = $field['field_name'];
    $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
    if (function_exists($function)) {
      // Determine the list of languages to iterate on.
      $available_languages = field_available_languages($entity_type, $field);
      $languages = _field_language_suggestion($available_languages, $options['language'], $field_name);

      foreach ($languages as $langcode) {
        $items = isset($entity->{$field_name}[$langcode]) ? $entity->{$field_name}[$langcode] : array();
        $result = $function($entity_type, $entity, $field, $instance, $langcode, $items, $a, $b);
        if (isset($result)) {
          // For hooks with array results, we merge results together.
          // For hooks with scalar results, we collect results in an array.
          if (is_array($result)) {
            $return = array_merge($return, $result);
          }
          else {
            $return[] = $result;
          }
        }

        // Populate $items back in the field values, but avoid replacing missing
        // fields with an empty array (those are not equivalent on update).
        if ($items !== array() || isset($entity->{$field_name}[$langcode])) {
          $entity->{$field_name}[$langcode] = $items;
        }
      }
    }

    return $return;
  }

  /**
   * Submit callback for this form.
   */
  function formSubmit($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $form_elements = $this->extractFormElements($form);
    field_attach_submit($this->getEntityType(), $entity, $form_elements, $form_state, array('field_name' => $this->field_name));
  }

  /**
   * Overrides FlexiformElement::formIsEmpty().
   */
  function formIsEmpty($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    $items = $this->formExtractValues($form, $form_state, $entity, $language);
    $items = _field_filter_items($this->getField(), $items);

    if (empty($items)) {
      return TRUE;
    }
  }

  /**
   * Overrides FlexiformElement::formExtractValues().
   */
  function formExtractValues($form, &$form_state, $entity, $language = LANGUAGE_NONE) {
    // Extract form values.
    $form_elements = $this->extractFormElements($form);
    $items = array();
    field_default_extract_form_values($this->getEntityType(), $entity, $this->getField(), $this->getInstance(), $language, $items, $form_elements, $form_state);

    return $items;
  }

  /**
   * Extract the form element from $form and give it the correct key.
   */
  function extractFormElements($form) {
    $form_element = $form[$this->getElementNamespace()];
    return array(
      '#parents' => array_merge($form['#parents'], array($this->getEntityNamespace())),
      $this->field_name => $form_element,
    );
  }

  /**
   * Overrides FlexifromElement::toSettingsArray();
   */
  public function toSettingsArray() {
    $settings = parent::toSettingsArray();
    $settings['type'] = 'field';
    $settings['field_name'] = $this->field_name;
    $settings['instance'] = $this->instance;
    $settings['field'] = $this->field;

    return $settings;
  }
}
