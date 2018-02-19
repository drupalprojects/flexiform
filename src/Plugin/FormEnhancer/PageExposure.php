<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for exposing custom form modes on pages.
 *
 * @FormEnhancer(
 *   id = "page_exposure",
 *   label = @Translation("Form Exposure"),
 * )
 */
class PageExposure extends ConfigurableFormEnhancerBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The entity_type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Create a new PageExposure form enhancer plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get form mode storage.
   */
  protected function entityFormModeStorage() {
    return $this->entityTypeManager->getStorage('entity_form_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $target_entity_type_id = $this->getFormDisplay()->getTargetEntityTypeId();
    $mode = $this->getFormDisplay()->getMode();

    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $form_mode = $this->entityFormModeStorage()->load("{$target_entity_type_id}.{$mode}");
    $form_state->set('entity_form_mode', $form_mode);

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Expose this form on a page at the path specified below, for more options consider using Page Manager.'),
    ];

    $form['warning'] = [
      '#type' => 'item',
      '#markup' => $this->t(
        '<strong>Warning:</strong> These settings apply to all %entity_type %mode forms.',
        [
          '%entity_type' => $target_entity_type->getLabel(),
          '%mode' => $form_mode->label(),
        ]
      ),
    ];

    $settings = $form_mode->getThirdPartySettings('flexiform');
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('The path at which to expose the form. Use the following placeholders for form entity values:'),
      '#default_value' => !empty($settings['exposure']['path']) ? $settings['exposure']['path'] : '',
    ];
    $form['path']['#description'] .= '<ul>';
    foreach ($this->getFormDisplay()->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      if ($namespace == '') {
        $namespace = 'base_entity';
      }

      if ($form_entity->getPluginId() == 'provided') {
        $form['path']['#description'] .= '<li><strong>{'.$namespace.'}</strong> - '.$form_entity->getFormEntityContextDefinition()->getLabel().'</li>';
      }
    }
    $form['path']['#description'] .= '</ul>';

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#description' => $this->t('The title of the page on which the form will appear.'),
      '#default_value' => !empty($settings['exposure']['title']) ? $settings['exposure']['title'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $form_mode = $form_state->get('entity_form_mode');
    $values = $form_state->getValue($form['#parents']);
    $settings = [];
    $settings['path'] = $values['path'];
    $settings['title'] = $values['title'];
    $settings['parameters'] = [];

    $form_display = $this->getFormDisplay();
    if (preg_match_all('/{(?P<namespace>[A-Za-z0-9_]+)}/', $settings['path'], $matches)) {
      foreach ($matches['namespace'] as $namespace) {
        if ($form_entity = $form_display->getFormEntityManager()->getFormEntity(($namespace == 'base_entity') ? $form_display->getBaseEntityNamespace() : $namespace)) {
          $settings['parameters'][$namespace] = [
            'entity_type' => $form_entity->getEntityType(),
            'bundle' => $form_entity->getBundle(),
          ];
        }
      }
    }
    $form_mode->setThirdPartySetting('flexiform', 'exposure', $settings);
    $form_mode->save();
  }

  /**
   * {@inheritdoc}
   */
  public function applies($event) {
    // Only applies on custom form modes.
    if (!in_array($event, ['configuration_form', 'init_form_entity_config'])) {
      return FALSE;
    }
    else {
      $target_entity_type = $this->getFormDisplay()->getTargetEntityTypeId();
      $mode = $this->getFormDisplay()->getMode();

      if (!$target_entity_type || !$mode) {
        return FALSE;
      }

      // If we can load a custom form mode entity for this form display
      // than this enhancer applies.
      if ($this->entityFormModeStorage()->load("{$target_entity_type}.{$mode}")) {
        return 100;
      }
    }

    return FALSE;
  }

  /**
   * Init form entity config.
   *
   * @return array
   */
  public function initFormEntityConfig() {
    $target_entity_type_id = $this->getFormDisplay()->getTargetEntityTypeId();
    $mode = $this->getFormDisplay()->getMode();

    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $form_mode = $this->entityFormModeStorage()->load("{$target_entity_type_id}.{$mode}");

    $form_entity_settings = [];
    if (($settings = $form_mode->getThirdPartySetting('flexiform', 'exposure')) && !empty($settings['parameters'])) {
      foreach ($settings['parameters'] as $namespace => $entity_info) {
        if ($namespace == 'base_entity') {
          continue;
        }

        $form_entity_settings[$form_namespace] = [
          'entity_type' => $entity_info['entity_type'],
          'bundle' => ($namespace != 'base_entity') ? $entity_info['bundle'] : $this->getFormDisplay()->getTargetBundle(),
          'plugin' => 'provided',
          'label' => $namespace,
        ];
      }
    }

    return $form_entity_settings;
  }
}
