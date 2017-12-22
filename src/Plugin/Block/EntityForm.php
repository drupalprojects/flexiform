<?php

namespace Drupal\flexiform\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to see an entity form.
 *
 * @Block(
 *   id = "entity_form",
 *   deriver = "Drupal\flexiform\Plugin\Deriver\EntityFormBlockDeriver",
 * )
 */
class EntityForm extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new EntityForm.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
    $this->formBuilder = $form_builder;
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
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $entity \Drupal\Core\Entity\EntityInterface **/
    $entity = $this->getContextValue('entity');
    $definition = $this->getPluginDefinition();
    if ($entity->bundle() !== $definition['bundle']) {
      return;
    }

    $form_object = $this->entityTypeManager->getFormObject($definition['entity_type'], $definition['form_mode']);
    $form_object->setEntity($entity);

    $provided = [];
    $entity_form_display = EntityFormDisplay::collectRenderDisplay($entity, $definition['form_mode']);
    foreach ($entity_form_display->getFormEntityConfig() as $namespace => $configuration) {
      if ($configuration['plugin'] == 'provided' && ($provided_entity = $this->getContextValue($namespace))) {
        $provided[$namespace] = $provided_entity;
      }
    }
    $form_state = new FormState();
    $form_state->set('form_entity_provided', $additions['form_entity_provided']);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }
}
