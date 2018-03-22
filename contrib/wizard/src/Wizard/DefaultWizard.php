<?php

namespace Drupal\flexiform_wizard\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\SharedTempStoreFactory;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\flexiform_wizard\Entity\Wizard;
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
  public function initValues() {
    $values['entities'] = $this->provided;
    $values['wizard'] = $this->wizard;
    $values['step'] = $this->getStep($values);

    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    return $event->getValues();
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\flexiform_wizard\Entity\Wizard $wizard
   *   The wizard configuration object.
   * @param string|null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, Wizard $wizard, $step = NULL) {
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $route_match, 'flexiform_wizard.'.$wizard->id(), 'flexiform_wizard__'.$wizard->id(), $step);

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
    foreach ($this->provided as $key => $entity) {
      $parameters[$key] = $entity->id();
    }
    return $parameters;
  }

  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);
    foreach ($this->provided as $key => $entity) {
      $parameters[$key] = $entity->id();
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

