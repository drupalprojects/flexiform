<?php

/**
 * @file
 * Contains \Drupal\flexiform\FormEntity\FlexiformFormEntityBase.
 */

namespace Drupal\flexiform\FormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

abstract class FlexiformFormEntityBase extends ContextAwarePluginBase implements FlexiformFormEntityInterface {

  /**
   * The flexiform entity manager.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager;

  /**
   * The actual context, wraps the entity item.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface.
   */
  protected $formEntityContext;

  /**
   * Whether or not the form entity has been prepared.
   */
  protected $prepared;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['manager'])) {
      throw new \Exception('No Form Entity Manager Supplied');
    }

    // Set the form entity manager.
    $this->formEntityManager = $configuration['manager'];
    $namespace = !empty($configuration['namespace']) ? $configuration['namespace'] : NULL;
    $entity = !empty($configuration['entity']) ? $configuration['entity'] : NULL;

    // Unset these values so they can't be accessed like normal configuration.
    unset($configuration['manager']);
    unset($configuration['namespace']);

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Load in the required contexts for this plugin.
    if (!empty($configuration['context_mapping'])) {
      foreach ($configuration['context_mapping'] as $key => $context_namespace) {
        $formEntity = $this->formEntityManager->getFormEntity($context_namespace);
        if (!$formEntity) {
          throw new \Exception('No Form Entity with namespace '.$context_namespace);
        }

        $this->context[$key] = $formEntity->getFormEntityContext();
      }
    }

    $this->formEntityContext = FormEntityContext::createFromFlexiformFormEntity($this, $entity);
    if (!empty($namespace)) {
      $this->formEntityContext->setEntityNamespace($namespace);
    }
  }

  /**
   * Check whether a given entity matches bundle required.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function checkBundle(EntityInterface $entity) {
    return !$entity->getEntityType()->hasKey('bundle') || ($entity->bundle() == $this->getBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (!empty($this->configuration['label'])) ? $this->configuration['label'] : $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->pluginDefinition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->pluginDefinition['bundle'];
  }

  /**
   * Get the context.
   *
   * @return \Drupal\flexiform\FormEntity\FormEntityContext
   */
  public function getFormEntityContext() {
    return $this->formEntityContext;
  }

  /**
   * Get the context definition.
   */
  public function getFormEntityContextDefinition() {
    return $this->formEntityContext->getContextDefinition();
  }

  /**
   * Get the Entity.
   */
  abstract public function getEntity();

  /**
   * Save the entity.
   */
  public function saveEntity(EntityInterface $entity) {
    $entity->save();
  }

  /**
   * Prepare a configuration form.
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form['context_mapping'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    if (empty($this->pluginDefinition['context']) || !is_array($this->pluginDefinition['context'])) {
      return $form;
    }
    foreach ($this->pluginDefinition['context'] as $key => $context_definition) {
      $matching_contexts = $this->contextHandler()->getMatchingContexts($this->formEntityManager->getContexts(), $context_definition);
      $context_options = [];
      foreach ($matching_contexts as $context) {
        $context_options[$context->getEntityNamespace()] = $context->getContextDefinition()->getLabel();
      }

      $form['context_mapping'][$key] = [
        '#type' => 'select',
        '#title' => $context_definition->getLabel(),
        '#options' => $context_options,
        '#default_value' => !empty($this->configuration['context_mapping'][$key]) ? $this->configuration['context_mapping'][$key] : NULL,
      ];
    }

    return $form;
  }

  /**
   * Validate the configuration form.
   */
  public function configurationFormValidate(array $form, FormStateInterface $form_state) {
  }

  /**
   * Submit the configuration form.
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
  }
}
