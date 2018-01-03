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
use Drupal\Core\Field\PluginSettingsInterface;
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

    $component_type_manager = \Drupal::service('plugin.manager.flexiform.form_component_type');
    foreach ($component_type_manager->getDefinitions() as $component_type => $definition) {
      $form['fields'] += $component_type_manager
        ->createInstance($component_type)
        ->setFormDisplay($this->entity)
        ->componentRows($form, $form_state);
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
          'attributes' => $this->getAjaxButtonAttributes(),
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
          '#attached' => [
            'library' => [
              'core/drupal.ajax',
            ],
          ],
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
        if ($values['region'] == 'hidden') {
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
          $options['region'] = $values['region'];
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
    if (empty($this->formEntityManager)) {
      $this->initFormEntityManager();
    }

    return $this->formEntityManager;
  }

  /**
   * Initialize the form entity manager.
   */
  protected function initFormEntityManager() {
    $this->formEntityManager = $this->entity->getFormEntityManager();
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
