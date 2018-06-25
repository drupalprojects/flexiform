<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\flexiform\FlexiformFormEntityPluginManager;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form enhancer for multiple entity forms.
 *
 * @FormEnhancer(
 *   id = "multiple_entities",
 *   label = @Translation("Multiple Entities"),
 * )
 */
class MultipleEntityForm extends ConfigurableFormEnhancerBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;
  use AjaxFormTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'init_form_entity_config',
  ];

  /**
   * The form entity plugin manager.
   *
   * @var \Drupal\flexiform\FlexiformFormEntityPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flexiform\FlexiformFormEntityPluginManager $plguin_manager
   *   The form entity plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FlexiformFormEntityPluginManager $plguin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plguin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.flexiform_form_entity'));
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {

    // @todo: Make this path NOT form mode specific.
    if ($this->formDisplay->get('targetEntityType')) {
      // Prepare a link to add an entity to this form.
      $target_entity_type = $this->formDisplay->get('targetEntityType');
      $target_entity_def = \Drupal::service('entity_type.manager')->getDefinition($target_entity_type);
      $url_params = [
        'form_mode_name' => $this->formDisplay->get('mode'),
      ];
      if ($target_entity_def->get('bundle_entity_type')) {
        $url_params[$target_entity_def->get('bundle_entity_type')] = $this->formDisplay->get('bundle');
      }

      $form['add'] = [
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
    }
    $form['entities'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Entity'),
        $this->t('Plugin'),
        $this->t('Operations'),
      ],
      '#title' => t('Entities'),
      '#empty' => t('This form display has no entities yet.'),
    ];

    foreach ($this->formDisplay->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
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

      $form['entities'][$namespace] = [
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
   * Initialise the enhancer config.
   *
   * @return array
   *   The initial config for the enhancer.
   */
  public function initFormEntityConfig() {
    $entities = $this->configuration['entities'] ?? [];
    foreach ($entities as &$definition) {
      $plugin = $this->pluginManager->getDefinition($definition['plugin']);
      $definition['entity_type'] = $plugin['entity_type'] ?? NULL;
      $definition['bundle'] = $plugin['bundle'] ?? NULL;
    }
    return $entities;
  }

  /**
   * Get the config for an entity namespace.
   *
   * @return array|false
   *   The entity config or FALSE if it doesn't exist.
   */
  public function getFormEntityConfig($namespace) {
    return $this->configuration['entities'][$namespace] ?? FALSE;
  }

  /**
   * Set the config for an entity namespace.
   *
   * @return $this
   *   The form enhancer.
   *
   * @todo: Rename to setFormEntityConfig()?
   */
  public function addFormEntityConfig($namespace, $configuration) {
    $this->configuration['entities'][$namespace] = $configuration;
    return $this;
  }

  /**
   * Remove an entity namespace.
   *
   * @return $this
   *   The form enhancer.
   */
  public function removeFormEntityConfig($namespace) {
    unset($this->configuration['entities'][$namespace]);
    return $this;
  }

}
