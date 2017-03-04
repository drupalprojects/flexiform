<?php

/**
 * @file
 * Contains \Drupal\flexiform\Form\FlexiformEntityFormDisplayEditForm.
 */

namespace Drupal\flexiform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;
use Drupal\flexiform\FormEntity\FlexiformFormEntityInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

class FlexiformEntityFormDisplayEditForm extends EntityFormDisplayEditForm {

  use AjaxFormTrait;

  /**
   * The form entity manager object.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager.
   */
  protected $formEntityManager;

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $form, $form_state);

    if (count($this->getFormEntityManager()->getFormEntities()) > 1) {
      $field_row['human_name']['#plain_text'] .= ' ['.$this->getFormEntityManager()->getFormEntity()->getFormEntityContextDefinition()->getLabel().']';
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExtraFieldRow($field_id, $extra_field) {
    $extra_field_row = parent::buildExtraFieldRow($field_id, $extra_field);

    if (count($this->getFormEntityManager()->getFormEntities()) > 1) {
      $extra_field_row['human_name']['#markup'] .= ' ['.$this->getFormEntityManager()->getFormEntity()->getFormEntityContextDefinition()->getLabel().']';
    }

    return $extra_field_row;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    // Add field rows from other entities.
    foreach ($this->getFormEntityFieldDefinitions() as $namespace => $definitions) {
      foreach ($definitions as $field_name => $field_definition) {
        $form['fields'][$namespace.':'.$field_name] = $this->buildFormEntityFieldRow($namespace, $this->getFormEntityManager()->getFormEntity($namespace), $field_definition, $form, $form_state);
      }
    }

    $form['entities_section'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    // Prepare a link to add an entity to this form.
    $target_entity_type = $this->entity->get('targetEntityType');
    $target_entity_def = \Drupal::service('entity_type.manager')->getDefinition($target_entity_type);
    $url_params = [
      'form_mode_name' => $this->entity->get('mode'),
    ];
    if ($target_entity_def->get('bundle_entity_type')) {
      $url_params[$target_entity_def->get('bundle_entity_type')] = $this->entity->get('bundle');
    }
    $form['entities_section']['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Entity'),
      '#url' => Url::fromRoute("entity.entity_form_display.{$target_entity_type}.form_mode.form_entity_add", $url_params),
      '#attributes' => $this->getAjaxButtonAttributes(),
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $form['entities_section']['entities'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Entity'),
        $this->t('Plugin'),
        $this->t('Operations'),
      ],
      '#title' => t('Entities'),
      '#empty' => t('This form display has no entities yet.'),
    ];

    foreach ($this->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      $operations = [];
      if (!empty($namespace)) {
        $operation_params = $url_params;
        $operation_params['entity_namespace'] = $namespace;

        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => Url::fromRoute(
            "entity.entity_form_display.{$target_entity_type}.form_mode.form_entity_edit",
            $operation_params
          ),
        ];
      }

      $form['entities_section']['entities'][$namespace] = [
        'human_name' => [
          '#plain_text' => $form_entity->getFormEntityContextDefinition()->getLabel(),
        ],
        'plugin' => [
          '#plain_text' => $form_entity->getLabel(),
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
    }

    return $form;
  }

  /**
   * Return an AJAX response to open the modal popup to add a form entity.
   */
  public function addFormEntity(array &$form, FormStateInterface $form_state) {
    $content = \Drupal::formBuilder()->getForm('Drupal\flexiform\Form\FormEntityAddForm', $this->entity);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $content['#attached']['library'][] = 'core/drupal.ajax';

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Add form entity'), $content, ['width' => 700]));
    return $response;
  }

  /**
   * Submit handler for adding a form entity.
   */
  public function addFormEntitySubmitForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * Builds the table row structure for a single field from a form entity.
   *
   * Unfortunately we had to cut and paste this from buildFieldRow and change
   * one line so that $field_name does not conflict.
   *
   * @param string $namespace
   *   The entity namespace.
   * @param \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface $form_entity
   *   The form entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A table row array.
   */
  protected function buildFormEntityFieldRow($namespace, FlexiformFormEntityInterface $form_entity, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_name = $namespace.':'.$field_definition->getName();
    $display_options = $this->entity->getComponent($field_name);
    $label = $field_definition->getLabel();

    // Disable fields without any applicable plugins.
    if (empty($this->getApplicablePluginOptions($field_definition))) {
      $this->entity->removeComponent($field_name)->save();
      $display_options = $this->entity->getComponent($field_name);
    }

    $regions = array_keys($this->getRegions());
    $field_row = array(
      '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
      '#row_type' => 'field',
      '#region_callback' => array($this, 'getRowRegion'),
      '#js_settings' => array(
        'rowHandler' => 'field',
        'defaultPlugin' => $this->getDefaultPlugin($field_definition->getType()),
      ),
      'human_name' => array(
        '#plain_text' => $label.' ['.$form_entity->getFormEntityContextDefinition()->getLabel().']',
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : '0',
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ),
      'parent_wrapper' => array(
        'parent' => array(
          '#type' => 'select',
          '#title' => $this->t('Label display for @title', array('@title' => $label)),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => array('class' => array('js-field-parent', 'field-parent')),
          '#parents' => array('fields', $field_name, 'parent'),
        ),
        'hidden_name' => array(
          '#type' => 'hidden',
          '#default_value' => $field_name,
          '#attributes' => array('class' => array('field-name')),
        ),
      ),
    );

    $field_row['plugin'] = array(
      'type' => array(
        '#type' => 'select',
        '#title' => $this->t('Plugin for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#options' => $this->getPluginOptions($field_definition),
        '#default_value' => $display_options ? $display_options['type'] : 'hidden',
        '#parents' => array('fields', $field_name, 'type'),
        '#attributes' => array('class' => array('field-plugin-type')),
      ),
      'settings_edit_form' => array(),
    );

    // Get the corresponding plugin object.
    $plugin = $this->entity->getRenderer($field_name);

    // Base button element for the various plugin settings actions.
    $base_button = array(
      '#submit' => array('::multistepSubmit'),
      '#ajax' => array(
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $field_name,
    );

    if ($form_state->get('plugin_settings_edit') == $field_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = array();

      if ($plugin) {
        // Generate the settings form and allow other modules to alter it.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);

        if ($settings_form || $third_party_settings_form) {
          $field_row['plugin']['#cell_attributes'] = array('colspan' => 3);
          $field_row['plugin']['settings_edit_form'] = array(
            '#type' => 'container',
            '#attributes' => array('class' => array('field-plugin-settings-edit-form')),
            '#parents' => array('fields', $field_name, 'settings_edit_form'),
            'label' => array(
              '#markup' => $this->t('Plugin settings'),
            ),
            'settings' => $settings_form,
            'third_party_settings' => $third_party_settings_form,
            'actions' => array(
              '#type' => 'actions',
              'save_settings' => $base_button + array(
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#name' => $field_name . '_plugin_settings_update',
                '#value' => $this->t('Update'),
                '#op' => 'update',
              ),
              'cancel_settings' => $base_button + array(
                '#type' => 'submit',
                '#name' => $field_name . '_plugin_settings_cancel',
                '#value' => $this->t('Cancel'),
                '#op' => 'cancel',
                // Do not check errors for the 'Cancel' button, but make sure we
                // get the value of the 'plugin type' select.
                '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
              ),
            ),
          );
          $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
        }
      }
    }
    else {
      $field_row['settings_summary'] = array();
      $field_row['settings_edit'] = array();

      if ($plugin) {
        // Display a summary of the current plugin settings, and (if the
        // summary is not empty) a button to edit them.
        $summary = $plugin->settingsSummary();

        // Allow other modules to alter the summary.
        $this->alterSettingsSummary($summary, $plugin, $field_definition);

        if (!empty($summary)) {
          $field_row['settings_summary'] = array(
            '#type' => 'inline_template',
            '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
            '#context' => array('summary' => $summary),
            '#cell_attributes' => array('class' => array('field-plugin-summary-cell')),
          );
        }

        // Check selected plugin settings to display edit link or not.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);
        if (!empty($settings_form) || !empty($third_party_settings_form)) {
          $field_row['settings_edit'] = $base_button + array(
            '#type' => 'image_button',
            '#name' => $field_name . '_settings_edit',
            '#src' => 'core/misc/icons/787878/cog.svg',
            '#attributes' => array('class' => array('field-plugin-settings-edit'), 'alt' => $this->t('Edit')),
            '#op' => 'edit',
            // Do not check errors for the 'Edit' button, but make sure we get
            // the value of the 'plugin type' select.
            '#limit_validation_errors' => array(array('fields', $field_name, 'type')),
            '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
            '#suffix' => '</div>',
          );
        }
      }
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    $form_values = $form_state->getValues();

    // Add field rows from other entities.
    foreach ($this->getFormEntityFieldDefinitions() as $namespace => $definitions) {
      foreach ($definitions as $field_name => $field_definition) {
        $name = $namespace.':'.$field_name;
        $values = $form_values['fields'][$name];
        if ($values['type'] == 'hidden') {
          $entity->removeComponent($name);
        }
        else {
          $options = $entity->getComponent($name);

          // Update field settings only if the submit handler told us to.
          if ($form_state->get('plugin_settings_update') === $name) {
            // Only store settings actually used by the selected plugin.
            $default_settings = $this->pluginManager->getDefaultSettings($options['type']);
            $options['settings'] = isset($values['settings_edit_form']['settings']) ? array_intersect_key($values['settings_edit_form']['settings'], $default_settings) : [];
            $options['third_party_settings'] = isset($values['settings_edit_form']['third_party_settings']) ? $values['settings_edit_form']['third_party_settings'] : [];
            $form_state->set('plugin_settings_update', NULL);
          }

          $options['type'] = $values['type'];
          $options['weight'] = $values['weight'];
          // Only formatters have configurable label visibility.
          if (isset($values['label'])) {
            $options['label'] = $values['label'];
          }
          $entity->setComponent($name, $options);
        }
      }
    }
  }

  /**
   * Get the form entity manager.
   *
   * @return Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  public function getFormEntityManager() {
    //return new FlexiformFormEntityManager($this->entity);
    if (empty($this->formEntityManager)) {
      $this->initFormEntityManager();
    }

    return $this->formEntityManager;
  }

  /**
   * Initialize the form entity manager.
   */
  protected function initFormEntityManager() {
    $this->formEntityManager = new FlexiformFormEntityManager($this->entity);
  }

  /**
   * Collects the field definitions of configurable fields on the form entities.
   */
  protected function getFormEntityFieldDefinitions() {
    $definitions = [];
    foreach ($this->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      // Ignore the base entity.
      if ($namespace == '') {
        continue;
      }

      $display_context = $this->displayContext;
      $definitions[$namespace] = array_filter(
        $this->entityManager->getFieldDefinitions($form_entity->getEntityType(), $form_entity->getBundle()),
        function (FieldDefinitionInterface $field_definition) use ($display_context) {
          return $field_definition->isDisplayConfigurable($display_context);
        }
      );
    }
    return $definitions;
  }
}
