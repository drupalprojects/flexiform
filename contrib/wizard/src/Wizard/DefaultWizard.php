<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a default form wizard.
 */
class DefaultWizard extends EntityFormWizardBase {

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
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $provided
   *   The provided (parameter) entities.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, RouteMatchInterface $route_match, $tempstore_id, $machine_name = NULL, $step = NULL, $provided = []) {
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $entity_manager, $route_match, $tempstore_id, $machine_name, $step);
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
  public function exists() {
    return '\Drupal\flexiform_wizard\Entity\Wizard::load';
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
    $parameters['commerce_order'] = 1;

    return $parameters;
  }

  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);
    $parameters['commerce_order'] = 1;

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];

//    if ($this->wizard) {
//      foreach ($this->wizard->getPages() as $name => $page) {

//      }
//    }

    $operations['test'] = [
      'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
      'title' => 'Test',
    ];

    $operations['final'] = [
      'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
      'title' => 'Final',
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'flexiform_wizard.'.$this->wizard->id().'.step';
  }

}

