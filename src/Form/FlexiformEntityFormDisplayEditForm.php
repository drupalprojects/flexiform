<?php

/**
 * @file
 * Contains \Drupal\flexiform\Form\FlexiformEntityFormDisplayEditForm.
 */

namespace Drupal\flexiform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $component_type_manager = \Drupal::service('plugin.manager.flexiform.form_component_type');
    $component_rows = [];
    foreach ($component_type_manager->getDefinitions() as $component_type => $definition) {
      $component_rows += $component_type_manager
        ->createInstance($component_type)
        ->setFormDisplay($this->entity)
        ->componentRows($this, $form, $form_state);
    }
    $form['fields'] = $component_rows + $form['fields'];

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

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
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
}
