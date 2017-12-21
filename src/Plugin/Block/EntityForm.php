<?php

namespace Drupal\flexiform\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to see an entity form.
 *
 * @Block(
 *   id = "entity_form",
 *   deriver = "Drupal\flexiform\Plugin\Deriver\EntityFormDeriver",
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
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $entity \Drupal\Core\Entity\EntityInterface **/
    $entity = $this->getContextValue('entity');
    $definition = $this->getPluginDefinition();
    $form_object = $this->entityTypeManager->getFormObject($definition['entity_type_id'], $definition['operation']);
    $form_object->setEntity($entity);

    $additions = [
      'form_entity_provided' => [],
    ];
    foreach ($form_object->getFormDisplay()->getFormEntityConfig() as $namespace => $configuration) {
      if ($configuration['plugin'] == 'provided' && ($provided = $this->getContextValue($namespace))) {
        $additions['form_entity_provided'][$namespace] = $provided;
      }
    }
    $form_state = (new FormState())->setFormState($additions);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }
}
