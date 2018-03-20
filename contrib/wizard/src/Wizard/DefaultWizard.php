<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
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


  public function initValues() {
    $cached_values = parent::initValues();

    $cached_values['step'] = 'test';

    if (!empty($cached_values[$this->getEntityType()])) {
      $this->wizard = $cached_values[$this->getEntityType()];
    }
    return $cached_values;
  }

  /**
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
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
    parent::__construct(
      $tempstore,
      $builder,
      $class_resolver,
      $event_dispatcher,
      $route_match,
      'flexiform_wizard.'.$wizard->id(),
      'flexiform_wizard__'.$wizard->id(),
      $step
    );

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
    return \Drupal::service('user.private_tempstore')->get($this->getTempstoreId());
  }

  public function getNextParameters($cached_values) {
    $parameters = parent::getNextParameters($cached_values);

    return $parameters;
  }

  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];

    if ($this->wizard) {
      foreach ($this->wizard->getPages() as $name => $page) {
        $plugin = \Drupal::service('plugin.manager.flexiform_wizard.wizard_step')
          ->createInstance($page['plugin'], $page['settings']);
        $operations[$name] = $plugin->stepInfo();
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'flexiform_wizard.'.$this->wizard->id().'.step';
  }

}

