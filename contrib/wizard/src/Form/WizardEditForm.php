<?php

namespace Drupal\flexiform_wizard\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\flexiform_wizard\WizardStep\WizardStepPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an edit form for flexiform wizard entities.
 */
class WizardEditForm extends WizardForm {

  /**
   * The wizard step pluign manager.
   *
   * @var \Drupal\flexiform_wizard\WizardStep\WizardStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flexiform_wizard.wizard_step')
    );
  }

  /**
   * Create a new WizardEditForm class
   *
   * @param \Drupal\flexiform_wizard\WizardStep\WizardStepPluginManager $step_plugin_manager
   *   The wizard step plugin manager.
   */
  public function __construct(WizardStepPluginManager $step_plugin_manager) {
    $this->stepPluginManager = $step_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\flexiform_wizard\Entity\Wizard $entity */
    if (!$form_state->get('entity')) {
      $form_state->set('entity', $this->entity);
    }
    $entity = $form_state->get('entity');

    $form = parent::form($form, $form_state);

    $form['parameters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Machine-Name'),
        $this->t('Label'),
        $this->t('Entity Type'),
      ],
      '#empty' => $this->t("This wizard doesn't have an parameters defined yet. Add parameters by altering the path."),
      '#theme_wrappers' => ['fieldset' => ['#title' => $this->t('Parameters')]],
    ];
    preg_match_all('/\{(?P<parameter>[A-Za-z0-9_\-]+)\}/', $entity->get('path'), $matches, PREG_PATTERN_ORDER);
    $parameters = $entity->get('parameters');

    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      foreach (\Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id) as $bundle_id => $bundle_info) {
        $entity_type_options[$entity_type->getLabel()->render()][$entity_type_id . ':' . $bundle_id] = $bundle_info['label'];
      }
    }

    $parameter_contexts = [];
    foreach ($matches['parameter'] as $param_name) {
      $form['parameters'][$param_name]['machine_name'] = [
        '#type' => 'item',
        '#markup' => $param_name,
        '#value' => $param_name,
      ];
      $form['parameters'][$param_name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Parameter Label'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($parameters[$param_name]['label']) ? $parameters[$param_name]['label'] : '',
      ];
      $form['parameters'][$param_name]['entity_type_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Type'),
        '#title_display' => 'invisible',
        '#options' => $entity_type_options,
        '#default_value' => !empty($parameters[$param_name]['entity_type']) && !empty($parameters[$param_name]['bundle']) ? $parameters[$param_name]['entity_type'] . ':' . $parameters[$param_name]['bundle'] : NULL,
        '#element_validate' => [
          ['\Drupal\flexiform_wizard\Form\WizardEditForm', 'parameterEntityTypeBundleElementValidate'],
        ],
      ];

      if (!empty($parameters[$param_name]['entity_type']) && !empty($parameters[$param_name]['bundle'])) {
        $parameter_contexts[$param_name] = new Context(new ContextDefinition($parameters[$param_name]['entity_type'], $parameters[$param_name]['label']));
      }
    }

    $form['pages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine-Name'),
        $this->t('Plugin'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t("This wizard doesn't have any pages defined yet."),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'wizard-page-weight',
        ],
      ],
      '#theme_wrappers' => ['fieldset' => ['#title' => $this->t('Pages')]],
    ];

    $max_weight = 0;
    foreach ($entity->getPages() as $name => $page) {
      $plugin_definition = $this->stepPluginManager->getDefinition($page['plugin']);

      $plugin = $this->stepPluginManager->createInstance(
        $page['plugin'],
        $page + ['step' => $name, 'wizard_config' => $entity]
      );
      if ($plugin instanceof ContextProvidingWizardStepInterface) {
        $parameter_contexts += $plugin->getProvidedContexts();
      }

      $form['pages'][$name]['#attributes']['class'][] = 'draggable';
      $form['pages'][$name]['#weight'] = $page['weight'] ?? 0;
      $form['pages'][$name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page Label'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($page['settings']['title']) ? $page['settings']['title'] : '' ,
      ];
      $form['pages'][$name]['machine_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-Name'),
        '#title_display' => 'invisible',
        '#default_value' => $name,
        '#disabled' => TRUE,
      ];
      $form['pages'][$name]['plugin'] = [
        '#type' => 'markup',
        '#markup' => $plugin_definition['admin_label'],
      ];
      $form['pages'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $page['weight'] ?? 0,
        '#attributes' => [
          'class' => [ 'wizard-page-weight' ],
        ],
      ];
      $form['pages'][$name]['operations'] = [
        '#type' => 'container',
        'form_display' => [
          '#type' => 'submit',
          '#value' => $this->t('Manage Form Display'),
          '#name' => 'manage_page_'.$name,
          '#page' => $name,
          '#limit_validation_errors' => [],
          '#submit' => [
            [ $this, 'submitManagePage' ],
          ],
        ],
        'remove' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_page_'.$name,
          '#page' => $name,
          '#limit_validation_errors' => [],
          '#submit' => [
            [ $this, 'submitRemovePage' ],
          ],
        ],
      ];

      if (!empty($page['weight']) && ($page['weight'] > $max_weight)) {
        $max_weight = $page['weight'];
      }
    }

    $page_plugin_options = [];
    foreach ($this->stepPluginManager->getDefinitionsForContexts($parameter_contexts) as $plugin_id => $plugin_info) {
      $page_plugin_options[$plugin_id] = $plugin_info['label'];
    }

    $form['pages']['__add_new'] = [
      '#attributes' => [
        'class' => [ 'draggable' ],
      ],
      '#weight' => $max_weight,
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Page Label'),
        '#title_display' => 'invisible',
      ],
      'machine_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Machine-Name'),
        '#title_display' => 'invisible',
      ],
      'plugin' => [
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#title_display' => 'invisible',
        '#options' => $page_plugin_options,
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => $this->t('Weight for New Page'),
        '#title_display' => 'invisible',
        '#default_value' => $max_weight,
        '#attributes' => [
          'class' => [ 'wizard-page-weight' ],
        ],
      ],
      'operations' => [
        '#type' => 'container',
        'remove' => [
          '#type' => 'submit',
          '#value' => $this->t('Add Page'),
          '#name' => 'add_page',
          '#limit_validation_errors' => [
            [ 'pages', '__add_new' ],
          ],
          '#validate' => [
            [ $this, 'validateAddPage' ],
          ],
          '#submit' => [
            [ $this, 'submitAddPage' ],
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Submit to remove a page.
   */
  public function submitRemovePage(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $form_state->get('entity')->removePage($element['#page']);
    $form_state->setRebuild();
  }

  /**
   * Validate adding of a page.
   */
  public function validateAddPage(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['pages', '__add_new']);
    if (empty($values['machine_name'])) {
      $form_state->setError($form['pages']['__add_new'], $this->t('You must specify a machine name for a page.'));
    }
  }

  /**
   * Submit to add a page.
   */
  public function submitAddPage(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['pages', '__add_new']);
    $input = &$form_state->getUserInput();
    unset($input['pages']['__add_new']);
    unset($values['operations']);
    $form_state->get('entity')->addPage($values['machine_name'], $values);
    $form_state->setRebuild();
  }

  /**
   * Submit to manage a page.
   */
  public function submitManagePage(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $form_state->setRedirect(
      'entity.flexiform_wizard.edit_page_form',
      [
        'flexiform_wizard' => $this->entity->id(),
        'page' => $element['#page'],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    $page_values = $form_state->getValue(['pages']);
    unset($page_values['__add_new']);
    foreach ($page_values as &$page) {
      unset($page['operations']);
    }
    $entity->set('pages', $page_values);
  }

  /**
   * Element validation handler for the parameter entity type bundle.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The complete form array.
   */
  public static function parameterEntityTypeBundleElementValidate(array $element, FormStateInterface $form_state, array $form = []) {
    $parents = $element['#parents'];
    $entity_type_bundle = $form_state->getValue($parents);

    array_pop($parents);
    $parameter_info = $form_state->getValue($parents);
    unset($parameter_info['entity_type_bundle']);
    list($parameter_info['entity_type'], $parameter_info['bundle']) = explode(':', $entity_type_bundle, 2);

    $form_state->setValue($parents, $parameter_info);
  }

}
