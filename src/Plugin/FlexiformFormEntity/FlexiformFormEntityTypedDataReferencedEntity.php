<?php

/**
 * @file
 * Contains \Drupal\flexiform\Plugin\FlexiformFormEntity\FlexiformFormEntityReferencedEntity.
 */

namespace Drupal\flexiform\Plugin\FlexiformFormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flexiform\Annotation\FlexiformFormEntity;
use Drupal\flexiform\FormEntity\FlexiformFormEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Entity plugin for entities that are passed in through the configuration
 * like the base entity.
 *
 * @FlexiformFormEntity(
 *   id = "referenced_entity",
 *   deriver = "\Drupal\flexiform\Plugin\Deriver\FormEntityTypedDataReferencedEntityDeriver"
 * )
 *
 */
class FlexiformFormEntityTypedDataReferencedEntity extends FlexiformFormEntityBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity() {
    $base = $this->getContextValue('base');
    $property = $this->pluginDefinition['property_name'];

    try {
      if ($entity = $base->{$property}->entity) {
        return $entity;
      }
      else if (!empty($this->configuration['create'])) {
        $entity = $this->createEntity();
        $base->{$property}->entity = $entity;
        return $entity;
      }
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Create a new entity ready for this situation.
   */
  protected function createEntity() {
    $values = [];
    if ($bundle_key = $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('bundle')) {
      $values[$bundle_key] = $this->getBundle();
    }

    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create($values);
    $this->moduleHandler->invokeAll('flexiform_form_entity_entity_create', [$entity, $this]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::configurationForm($form, $form_state);
    $form['create'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create New Entity'),
      '#description' => $this->t('If the property is empty, and new entity will be created.'),
      '#default_value' => !empty($this->configuration['create']),
    );

    return $form;
  }
}
