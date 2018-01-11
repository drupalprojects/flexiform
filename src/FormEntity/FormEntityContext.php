<?php

namespace Drupal\flexiform\FormEntity;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Class for form entity contexts.
 */
class FormEntityContext extends Context implements FormEntityContextInterface {

  /**
   * The entity namespace.
   *
   * @var string
   */
  protected $entityNamespace;

  /**
   * The form entity plugin.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   */
  protected $formEntity;

  /**
   * {@inheritdoc}
   */
  public function setEntityNamespace($namespace) {
    $this->entityNamespace = $namespace;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityNamespace() {
    return $this->entityNamespace;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormEntity() {
    return $this->formEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormEntity(FlexiformFormEntityInterface $form_entity) {
    $this->formEntity = $form_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasContextValue() {
    if (!$this->contextData) {
      $this->getContextValue();
    }
    return parent::hasContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValue() {
    if (!$this->contextData) {
      $this->setContextValue($this->getFormEntity()->getEntity());
    }
    return parent::getContextValue();
  }

  /**
   * Create from a form entity plugin.
   *
   * @param string $namespace
   * @param \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   *   The form entity plugin.
   */
  public static function createFromFlexiformFormEntity(FlexiformFormEntityInterface $form_entity, FieldableEntityInterface $entity = NULL) {
    $context_definition = new ContextDefinition('entity:'.$form_entity->getEntityType(), $form_entity->getLabel());
    $context_definition->addConstraint('Bundle', [$form_entity->getBundle()]);
    $context = new static($context_definition, $entity);
    $context->setFormEntity($form_entity);

    return $context;
  }
}
