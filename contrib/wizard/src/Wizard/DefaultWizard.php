<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\flexiform_wizard\Entity\Wizard as WizardEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a default form wizard.
 */
class DefaultWizard extends FormWizardBase {

  /**
   * The wizard configuration object.
   *
   * @var \Drupal\flexiform_wizard\Entity\Wizard
   */
  protected $wizard = NULL;

  /**
   * The provided entities.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface[]
   */
  protected $provided = [];

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    $params = parent::getParameters();
    $params['tempstore'] = \Drupal::service('user.private_tempstore');
    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $cached_values['entities'] = [];
    foreach ($this->provided as $namespace => $provided) {
      $cached_values['entities'][$namespace] = $provided;
    }
    $cached_values['flexiform_wizard'] = $this->wizard;
    $cached_values['step'] = $this->step;

    return $cached_values;
  }

  /**
   * Build the wizard object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\flexiform_wizard\Entity\Wizard $wizard
   *   The wizard configuration object.
   * @param string|null $step
   *   The current active step of the wizard.
   */
  public function __construct(
    PrivateTempStoreFactory $tempstore,
    FormBuilderInterface $builder,
    ClassResolverInterface $class_resolver,
    EventDispatcherInterface $event_dispatcher,
    RouteMatchInterface $route_match,
    WizardEntity $wizard,
    $step = NULL
  ) {
    $this->tempstore = $tempstore;
    $this->builder = $builder;
    $this->classResolver = $class_resolver;
    $this->dispatcher = $event_dispatcher;
    $this->routeMatch = $route_match;
    $this->tempstore_id = 'flexiform_wizard.' . $wizard->id();
    $this->machine_name = 'flexiform_wizard__' . $wizard->id();
    $this->step = $step;
    $this->wizard = $wizard;

    $provided = [];
    foreach ($this->wizard->get('parameters') as $param_name => $param_info) {
      if ($provided_entity = $route_match->getParameter($param_name)) {
        $provided[$param_name] = $provided_entity;
      }
    }
    $this->provided = $provided;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'flexiform_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Flexiform');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Administrative title');
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstore() {
    return $this->tempstore->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    $parameters = parent::getNextParameters($cached_values);

    foreach ($this->wizard->get('parameters') as $param_name => $param_info) {
      if (!empty($cached_values['entities'][$param_name])) {
        $parameters[$param_name] = $cached_values['entities'][$param_name]->id();
      }
      elseif (!empty($this->provided[$param_name])) {
        $parameters[$param_name] = $this->provided[$param_name]->id();
      }
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);

    foreach ($this->wizard->get('parameters') as $param_name => $param_info) {
      if (!empty($cached_values['entities'][$param_name])) {
        $parameters[$param_name] = $cached_values['entities'][$param_name]->id();
      }
      elseif (!empty($this->provided[$param_name])) {
        $parameters[$param_name] = $this->provided[$param_name]->id();
      }
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];

    if ($this->wizard) {
      foreach ($this->wizard->getPages() as $name => $page) {
        /* @var \Drupal\flexiform_wizard\WizardStep\WizardStepInterface $plugin */
        $plugin = \Drupal::service('plugin.manager.flexiform_wizard.wizard_step')
          ->createInstance($page['plugin'], $page['settings'] ?: []);
        $operations[$name] = $plugin->stepInfo($name, $cached_values);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'flexiform_wizard.' . $this->wizard->id() . '.step';
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(FormInterface $form_object, FormStateInterface $form_state) {
    $actions = parent::actions($form_object, $form_state);
    $actions['#weight'] = 200;
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    if ($this->wizard->shouldSaveOnFinish()) {
      $cached_values = $this->getTempstore()->get($this->getMachineName());

      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      // Save the main entity for each step.
      foreach ($cached_values['entities'] as $key => $entity) {
        $entity->save();
      }
    }

    // Save entities before calling parent.
    // Parent clears the cached data.
    parent::finish($form, $form_state);
  }

}
