<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerInterface;

/**
 * Provides Flexiform form elements for the EntityFormDisplay entity type.
 */
class FlexiformEntityFormDisplayEditForm extends EntityFormDisplayEditForm {

  use AjaxFormTrait;

  /**
   * The form entity manager object.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager;

  /**
   * {@inheritdoc}
   */
  public function getTableHeader() {
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = EntityForm::form($form, $form_state);
    $form['#entity_type'] = $this->entity->getTargetEntityTypeId();
    $form['#bundle'] = $this->entity->getTargetBundle();
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['fields'] = [
      '#type' => 'field_ui_table',
      '#header' => [
        $this->t('Component'),
        $this->t('Weight'),
        $this->t('Parent'),
        $this->t('Region'),
        [
          'data' => $this->t('Widget'),
          'colspan' => 3,
        ],
      ],
      '#regions' => $this->getRegions(),
      '#attributes' => [
        'class' => [
          'field-ui-overview',
        ],
        'id' => 'field-display-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-parent',
          'subgroup' => 'field-parent',
          'source' => 'field-name',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-region',
          'subgroup' => 'field-region',
          'source' => 'field-name',
        ],
      ],
    ];

    // Components.
    $component_type_manager = \Drupal::service('plugin.manager.flexiform.form_component_type');
    $component_rows = [];
    foreach ($component_type_manager->getDefinitions() as $component_type => $definition) {
      $form['fields'] += $component_type_manager
        ->createInstance($component_type)
        ->setFormDisplay($this->entity)
        ->componentRows($this, $form, $form_state);
    }

    // Enhancers.
    $form['enhancer'] = [
      '#type' => 'vertical_tabs',
    ];
    foreach ($this->entity->getFormEnhancers('configuration_form') as $enhancer_name => $enhancer) {
      if ($enhancer instanceof ConfigurableFormEnhancerInterface) {
        $form['enhancer_' . $enhancer_name] = [
          '#type' => 'details',
          '#title' => $enhancer->getPluginDefinition()['label'],
          '#parents' => ['enhancer', $enhancer_name],
          '#array_parents' => ['enhancer_' . $enhancer_name],
          '#group' => 'enhancer',
          '#tree' => TRUE,
        ];
        $form['enhancer_' . $enhancer_name] += $enhancer->configurationForm($form['enhancer_' . $enhancer_name], $form_state);
      }
    }

    // Custom display settings.
    if ($this->entity
      ->getMode() == 'default') {

      // Only show the settings if there is at least one custom display mode.
      $display_mode_options = $this
        ->getDisplayModeOptions();

      // Unset default option.
      unset($display_mode_options['default']);
      if ($display_mode_options) {
        $form['modes'] = [
          '#type' => 'details',
          '#title' => $this
            ->t('Custom display settings'),
        ];

        // Prepare default values for the 'Custom display settings' checkboxes.
        $default = [];
        if ($enabled_displays = array_filter($this
          ->getDisplayStatuses())) {
          $default = array_keys(array_intersect_key($display_mode_options, $enabled_displays));
        }
        $form['modes']['display_modes_custom'] = [
          '#type' => 'checkboxes',
          '#title' => $this
            ->t('Use custom display settings for the following @display_context modes', [
            '@display_context' => $this->displayContext,
          ]),
          '#options' => $display_mode_options,
          '#default_value' => $default,
        ];

        // Provide link to manage display modes.
        $form['modes']['display_modes_link'] = $this
          ->getDisplayModesLink();
      }
    }

    // In overviews involving nested rows from contributed modules (i.e
    // field_group), the 'plugin type' selects can trigger a series of changes
    // in child rows. The #ajax behavior is therefore not attached directly to
    // the selects, but triggered by the client-side script through a hidden
    // #ajax 'Refresh' button. A hidden 'refresh_rows' input tracks the name of
    // affected rows.
    $form['refresh_rows'] = [
      '#type' => 'hidden',
    ];
    $form['refresh'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => [
        '::multistepSubmit',
      ],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        // The button stays hidden, so we hide the Ajax spinner too. Ad-hoc
        // spinners will be added manually by the client-side script.
        'progress' => 'none',
      ],
      '#attributes' => [
        'class' => [
          'visually-hidden',
        ],
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this
        ->t('Save'),
    ];
    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $form_values = array_diff_key($form_values, $this->entity->getPluginCollections());
    }

    // Collect component options.
    foreach ($form_values['fields'] as $component_name => $values) {
      // Do not handle extra fields here.
      // @todo: Make extra fields a component type.
      if (!empty($form['#extra'][$component_name])) {
        continue;
      }

      if ($values['region'] == 'hidden') {
        $entity->removeComponent($component_name);
      }
      else {
        $options = $entity->getComponent($component_name);
        $options = $entity->getComponentTypePlugin(!empty($options['component_type']) ? $options['component_type'] : 'field_widget')->submitComponentRow($component_name, $values, $form, $form_state);
        $entity->setComponent($component_name, $options);
      }
    }

    // Collect options for extra field components.
    if (!empty($form['#extra'])) {
      foreach ($form['#extra'] as $name) {
        if ($form_values['fields'][$name]['region'] == 'hidden') {
          $entity->removeComponent($name);
        }
        else {
          $entity->setComponent($name, [
            'weight' => $form_values['fields'][$name]['weight'],
            'region' => $form_values['fields'][$name]['region'],
          ]);
        }
      }
    }

    // Loop over the enhancers and let them set their configuration internally
    // this then gets saved in the presave of the FormDisplay entity.
    foreach ($entity->getFormEnhancers('configuration_form') as $enhancer_name => $enhancer) {
      if ($enhancer instanceof ConfigurableFormEnhancerInterface) {
        $enhancer->configurationFormSubmit($form['enhancer_' . $enhancer_name], $form_state);
      }
    }
  }

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   *   The entity form manager.
   */
  public function getFormEntityManager() {
    if (empty($this->formEntityManager)) {
      $this->formEntityManager = $this->entity->getFormEntityManager();
    }

    return $this->formEntityManager;
  }

}
