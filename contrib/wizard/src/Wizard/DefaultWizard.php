<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\ctools\Wizard\FormWizardBase;

class DefaultWizard extends FormWizardBase {

  /**
   * The wizard configuration object.
   *
   * @var \Drupal\flexiform_wizard\Entity\Wizard
   */
  protected $wizard = NULL;

  /**
   * The provided entities
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface[] $provided_entities
   */
  protected $provided = [];

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
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $provided
   *   The provided (parameter) entities.
   */
  public function __construct(PrivateTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, $tempstore_id, $machine_name = NULL, $step = NULL, $provided = []) {
    $this->tempstore = $tempstore;
    $this->builder = $builder;
    $this->classResolver = $class_resolver;
    $this->dispatcher = $event_dispatcher;
    $this->routeMatch = $route_match;
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $this->step = $step;
    $this->provided = $provided;
  }

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    return [
      'tempstore' => \Drupal::service('user.private_tempstore'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $values = parent::initValues();
    $values['entities'] += $this->provided;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->wizard->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstore() {
    return \Drupal::service('user.private_tempstore')->get($this->getTempstoreId());
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];
    foreach ($this->wizard->getPages() as $name => $page) {
      $operations[$name] = [
        'form' => 'Drupal\flexiform_wizard\Form\DefaultWizardOperation',
        'title' => $page['name'],
      ];
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
