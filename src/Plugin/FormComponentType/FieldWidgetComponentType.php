<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormComponent\FormComponentTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "field_widget",
 *   label = @Translation("Field Widget"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\FieldWidgetComponent",
 * )
 */
class FieldWidgetComponentType extends FormComponentTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $pluginManager;

  /**
   * Field type definitions.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.field.widget'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Construct a new FieldWidgetComponentType object
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definitions
   * @param \Drupal\Core\Field\WidgetPluginManager $plugin_manager
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WidgetPluginManager $plugin_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fieldTypes = $field_type_manager->getDefinitions();
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($form_display->getFormEntityFieldDefinitions() as $namespace => $definitions)  {
      foreach ($definitions as $field_name => $field_definition) {
        $component_name = $namespace.':'.$field_name;
        $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  protected function getApplicableRendererPluginOptions($component_name) {
    $field_definition = $this->getFormDisplay()->getFieldDefinition($component_name);
    $options = $this->pluginManager->getOptions($field_definition->getType());
    $applicable_options = [];

    foreach ($options as $options => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->pluginManager->getDefinition($option));
      if ($plugin_class::isApplicable($field_Definition)) {
        $applicable_options[$option] = $label;
      }
    }
    return $applicable_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultRendererPlugin($component_name) {
    $type = $this->getFormDisplay()->getFieldDefinition($component_name)->getType();
    return isset($this->fieldTypes[$type]['default_widget']) ? $this->fieldTypes[$field_type]['default_widget'] : NULL;
  }

  /**
   * Build a component row.
   */
  protected function buildComponentRow(EntityDisplayFormBase $form_object, $component_name, array $form, FormStateInterface $form_state) {
    if (strpos($component_name, ':')) {
      list($namespace, $field_name) = explode(':', $component_name, 2);
    }
    else {
      $namespace = '';
      $field_name = $component_name;
    }

    $form_display = $this->getFormDisplay();
    $display_options = $form_display->getComponent($component_name);
    $field_definition = $form_display->getFieldDefinition($component_name);
    $form_entity = $form_display->getFormEntityManager()->getFormEntity($namespace);
    $label = $field_definition->getLabel();

    // Disable fields without any applicable plugins.
    if (empty($form_object->getApplicablePluginOptions($field_definition))) {
      $form_display->removeComponent($component_name)->save();
      $display_options = $form_display->getComponent($component_name);
    }

    $regions = array_keys($form_object->getRegions());
    $row = [
      '#attributes' => ['class' => ['draggable', 'tabledrag-leaf']],
      '#row_type' => 'field',
      '#region_callback' => [$form_object, 'getRowRegion'],
      '#js_settings' => [
        'rowHandler' => 'field',
        'defaultPlugin' => $form_object->getDefaultPlugin($field_definition->getType()),
      ],
      'human_name' => [
        '#plain_text' => $label.' ['.$form_entity->getFormEntityContextDefinition()->getLabel().']',
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => t('Weight for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : '0',
        '#size' => 3,
        '#attributes' => array('class' => array('field-weight')),
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => t('Label display for @title', array('@title' => $label)),
          '#title_display' => 'invisible',
          '#options' => array_combine($regions, $regions),
          '#empty_value' => '',
          '#attributes' => ['class' => ['js-field-parent', 'field-parent']],
          '#parents' => ['fields', $component_name, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $compoenent_name,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => t('Region for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $form_object->getRegionOptions(),
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => [
          'class' => [
            'field-region',
          ],
        ],
      ],
    ];

    $row['plugin'] = array(
      'type' => array(
        '#type' => 'select',
        '#title' => t('Plugin for @title', array('@title' => $label)),
        '#title_display' => 'invisible',
        '#options' => $form_object->getApplicablePluginOptions($field_definition),
        '#default_value' => $display_options ? $display_options['type'] : 'hidden',
        '#parents' => array('fields', $compoenent_name, 'type'),
        '#attributes' => array('class' => array('field-plugin-type')),
      ),
      'settings_edit_form' => array(),
    );

    // Get the corresponding plugin object.
    $plugin = $form_display->getRenderer($component_name);

    // Base button element for the various plugin settings actions.
    $base_button = array(
      '#submit' => array('::multistepSubmit'),
      '#ajax' => array(
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ),
      '#field_name' => $component_name,
    );

    if ($form_state->get('plugin_settings_edit') == $component_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = array();

      if ($plugin) {
        // Generate the settings form and allow other modules to alter it.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = static::thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);

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

}
