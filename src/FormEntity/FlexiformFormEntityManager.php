<?php

namespace Drupal\flexiform\FormEntity;

use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for form entity managers.
 */
class FlexiformFormEntityManager {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The form display config entity.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * The Form entity plugins.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface[]
   */
  protected $formEntities = [];

  /**
   * Construct a new FlexiformFormEntityManager.
   *
   * @param \Drupal\flexiform\FlexiformEntityFormDisplayInterface $form_display
   *   The form display to manage the entities for.
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $provided
   *   Array of provided entities keyed by namespace.
   */
  public function __construct(FlexiformEntityFormDisplayInterface $form_display, array $provided = []) {
    $this->formDisplay = $form_display;
    $this->initFormEntities($provided);
  }

  /**
   * Get the flexiform form entity plugin manager.
   */
  protected function getPluginManager() {
    return \Drupal::service('plugin.manager.flexiform_form_entity');
  }

  /**
   * Initialize form entities.
   */
  protected function initFormEntities(array $provided = []) {
    foreach ($this->formDisplay->getFormEntityConfig() as $namespace => $configuration) {
      $configuration['namespace'] = $namespace;
      $configuration['manager'] = $this;
      if (isset($provided[$namespace])) {
        $configuration['entity'] = $provided[$namespace];
      }
      $this->formEntities[$namespace] = $this->getPluginManager()->createInstance($configuration['plugin'], $configuration);
    }
  }

  /**
   * Get the context definitions from the form entity plugins.
   */
  public function getContextDefinitions() {
    $context_definitions = [];
    foreach ($this->formEntities as $namespace => $form_entity) {
      $context_definitions[$namespace] = $form_entity->getFormEntityContextDefinition();
    }
    return $context_definitions;
  }

  /**
   * Get the actual contexts.
   */
  public function getContexts() {
    $contexts = [];
    foreach ($this->formEntities as $namespace => $form_entity) {
      $contexts[$namespace] = $form_entity->getFormEntityContext();
      $contexts[$namespace]->setEntityNamespace($namespace);
    }
    return $contexts;
  }

  /**
   * Get the form entities.
   */
  public function getFormEntities() {
    return $this->formEntities;
  }

  /**
   * Get the form entity at a given namespace.
   *
   * @param string $namespace
   *   The namespace for the entity to retrieve.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   *   The form entity for the given namespace.
   */
  public function getFormEntity($namespace = '') {
    return $this->formEntities[$namespace];
  }

  /**
   * Save the form entities.
   *
   * @param bool $save_base
   *   Whether or not to save the base entity.
   */
  public function saveFormEntities($save_base = FALSE) {
    foreach ($this->getFormEntities() as $namespace => $form_entity) {
      if ($namespace == '' && !$save_base) {
        continue;
      }

      if ($entity = $this->getEntity($namespace)) {
        $form_entity->saveEntity($entity);
      }
    }
  }

  /**
   * Get the entity at a given namespace.
   *
   * @param string $namespace
   *   The entity namespace to get.
   */
  public function getEntity($namespace = '') {
    if (!isset($this->formEntities[$namespace])) {
      throw new Exception($this->t('No entity at namespace :namespace', [':namespace' => $namespace]));
    }

    $context = $this->formEntities[$namespace]->getFormEntityContext();
    return $context->hasContextValue() ? $context->getContextValue() : NULL;
  }

}
