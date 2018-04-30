<?php

namespace Drupal\flexiform_wizard\Form;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\flexiform_wizard\WizardStep\WizardStepPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default class for flexiform wizard operations.
 */
class DefaultWizardOperation extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Wizard step plugin manager.
   *
   * @var \Drupal\flexiform_wizard\WizardStep\WizardStepPluginManager
   */
  protected $wizardStepPluginManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The wizard configuration.
   *
   * @var \Drupal\flexiform_wizard\Entity\Wizard
   */
  protected $wizardConfig = NULL;

  /**
   * The form step.
   *
   * @var string
   */
  protected $step = '';

  /**
   * The plugin that handles this wizard step.
   *
   * @var \Drupal\flexiform_wizard\WizardStep\WizardStepInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexiform_wizard_operation_form';
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.flexiform_wizard.wizard_step'),
      $container->get('context.handler')
    );
  }

  /**
   * Construct a new DefaultWizardOperation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WizardStepPluginManager $wizard_step_plugin_manager, ContextHandlerInterface $context_handler) {
    $this->wizardStepPluginManager = $wizard_step_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * Get the operation form display.
   *
   * @return \Drupal\flexiform\FlexiformEntityFormDisplay
   */
  public function getFormDisplay() {
    $id = 'flexiform_wizard.' . $this->wizardConfig->id() . '.' . $this->step;
    $display = $this->entityTypeManager->getStorage('entity_form_display')->load($id);
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $this->wizardConfig = $cached_values['flexiform_wizard'] ?? [];
    $this->step = $cached_values['step'];

    $entity_contexts = [];
    foreach ($cached_values['entities'] as $parameter_name => $entity) {
      $entity_contexts[$parameter_name] = new Context(
        new ContextDefinition(
          'entity:'.$entity->getEntityTypeId(),
          $parameter_name,
          TRUE
        ),
        $entity
      );
    }

    // Add a current_user context.
    $entity_contexts['_current_user'] = new Context(
      new ContextDefinition(
        'entity:user',
        '_current_user',
        TRUE
      ),
      $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id())
    );

    $page = $this->wizardConfig->getPages()[$this->step];
    $page['settings']['step'] = $this->step;
    $page['settings']['wizard_config'] = $this->wizardConfig;

    $this->plugin = $this->wizardStepPluginManager->createInstance(
      $page['plugin'],
      $page['settings'] ?: []
    );

    $this->contextHandler->applyContextMapping($this->plugin, $entity_contexts);
    $form += $this->plugin->buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->submitForm($form, $form_state);

    if ($this->plugin instanceof ContextProvidingWizardStepInterface) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      $cached_values['entities'] = $this->plugin->getProvidedContexts() + $cached_values['entities'];
      $form_state->setTemporaryValue('wizard', $cached_values);
    }
  }

}
