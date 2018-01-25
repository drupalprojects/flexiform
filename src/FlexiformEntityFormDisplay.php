<?php

/**
 * @file
 * Contains \Drupal\flexiform\FlexiformEntityFormDisplay.
 */

namespace Drupal\flexiform;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

/**
 * Defines a class to extend EntityFormDisplays to work with multiple entity
 * forms.
 */
class FlexiformEntityFormDisplay extends EntityFormDisplay implements FlexiformEntityFormDisplayInterface {

  /**
   * The base entity namespace.
   *
   * @var string.
   */
  protected $baseEntityNamespace = '';

  /**
   * The form entity configuration.
   */
  protected $formEntities = [];

  /**
   * The flexiform form Entity Manager.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager;

  /**
   * What entities the form entity manager has been provided with. If more
   * entities are supplied build a new entity manager.
   *
   * @var string[]
   */
  protected $formEntityManagerSuppliedNamespaces;

  /**
   * Component types.
   *
   * @var \Drupal\flexiform\FormComponent\FormComponentTypeInterface[]
   */
  protected $componentTypePlugins = [];

  /**
   * Get the regions needed to create the overview form.
   *
   * I don't understand why in core these two methods are on the form_object
   * rather than the EntityFormDisplay object itself. I have put them here
   * so that it's easier to get access to the correct regions.
   *
   * @see \Drupal\field_ui\Form\EntityDisplayFormBase::getRegions()
   *
   * @return array
   *   Example usage:
   *   @code
   *     return array(
   *       'content' => array(
   *         // label for the region.
   *         'title' => $this->t('Content'),
   *         // Indicates if the region is visible in the UI.
   *         'invisible' => TRUE,
   *         // A message to indicate that there is nothing to be displayed in
   *         // the region.
   *         'message' => $this->t('No field is displayed.'),
   *       ),
   *     );
   *   @endcode
   */
  public function getRegions() {
    return [
      'content' => [
        'title' => t('Content'),
        'invisible' => TRUE,
        'message' => t('No component is displayed.'),
      ],
      'hidden' => [
        'title' => t('Disabled', [],
        [
          'context' => 'Plural',
        ]),
        'message' => t('No component is hidden.'),
      ],
    ];
  }

  /**
   * Returns an associative array of all regions.
   *
   * @return array
   *   An array containing the region options.
   *
   * @see \Drupal\field_ui\Form\EntityDisplayFormBase::getRegionOptions()
   */
  public function getRegionOptions() {
    $options = [];
    foreach ($this->getRegions() as $region => $data) {
      $options[$region] = $data['title'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage, $update = TRUE) {
    $form_entities = $this->formEntities;
    if ($this->getTargetEntityTypeId() && !empty($form_entities[$this->baseEntityNamespace])) {
      unset($form_entities[$this->baseEntityNamespace]);
    }
    $this->setThirdPartySetting('flexiform', 'form_entities', $form_entities);
    parent::preSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    foreach ($entities as $entity) {
      $entity->initFormEntityConfig();
    }
    parent::postLoad($storage, $entities);
  }

  /**
   * Get the component plugin.
   */
  public function getComponentPlugin($name, $options) {
    $plugin_id = !empty($options['component_type']) ? $options['component_type'] : (empty($options['type']) ? 'extra_field' : 'field_widget');
    return $this->getComponentTypePlugin($plugin_id)->getComponent($name, $options);
  }

  /**
   * Get a compoenet type plugin.
   */
  public function getComponentTypePlugin($plugin_id = 'field_widget') {
    if (empty($this->componentTypePlugins[$plugin_id])) {
      $this->componentTypePlugins[$plugin_id] = \Drupal::service('plugin.manager.flexiform.form_component_type')
        ->createInstance($plugin_id)
        ->setFormDisplay($this);
    }

    return $this->componentTypePlugins[$plugin_id];
  }

  /**
   * Get the array of provided entities.
   */
  protected function getProvidedEntities(FormStateInterface $form_state, FieldableEntityInterface $base_entity = NULL) {
    $provided = [];
    if ($base_entity) {
      $provided[$this->baseEntityNamespace] = $base_entity;
    }
    $provided += $form_state->get('form_entity_provided') ?: [];

    return $provided;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $this->buildAdvancedForm(
      $this->getProvidedEntities($form_state, $entity),
      $form,
      $form_state
    );
  }

  /**
   * Build standalone form. A standalone form does not have a single base
   * this allows the passing of a single array of provided entities.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   An array of provided entities keyed by namespace.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildAdvancedForm(array $provided, array &$form, FormStateInterface $form_state) {
    $this->getFormEntityManager($provided);

    // Set #parents to 'top-level' by default.
    $form += array('#parents' => array());
    $original_parents = $form['#parents'];

    // Let each widget generate the form elements.
    foreach ($this->getComponents() as $name => $options) {
      $component = $this->getComponentPlugin($name, $options);

      // On each component reset the parents back to the original.
      $form['#parents'] = $original_parents;

      $component->render($form, $form_state, $this->renderer);
    }

    // Set form parents back to the original
    $form['#parents'] = $original_parents;

    // Associate the cache tags for the form display.
    $this->renderer->addCacheableDependency($form, $this);

    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = [$this, 'processForm'];
  }

  /**
   * {@inheritform}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $element = parent::processForm($element, $form_state, $form);
    static::addSaveFormEntitiesSubmit($element, $this);
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Work with component types.
   */
  public function extractFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Make sure the form entity manager is appropriately constructed.
    $extracted = [];
    $this->getFormEntityManager($this->getProvidedEntities($form_state, $entity));

    foreach ($this->getComponents() as $name => $options) {
      if ($component = $this->getComponentPlugin($name, $options)) {
        $component->extractFormValues($form, $form_state);
        $extracted[$name] = $name;
      }
    }

    return $extracted;
  }

  /**
   * Save the extra entities added to the form.
   */
  public function saveFormEntities(array $form, FormStateInterface $form_state) {
    $this->getFormEntityManager()->saveFormEntities();
  }

  /**
   * Look through the form to find submit buttons, if they have the save submit
   * method then add our saveEntities submit callback.
   *
   * @param array $element
   *   The element to add the submit callback to. If this is not a submit
   *   element then continue to search the children.
   * @param \Drupal\flexiform\FlexiformEntityFormDisplayInterface $form_display
   *   The flexiform entity form display.
   */
  public static function addSaveFormEntitiesSubmit(array &$element, FlexiformEntityFormDisplayInterface $form_display) {
    if (isset($element['#type']) && $element['#type'] == 'submit') {
      if (!empty($element['#submit']) && in_array('::save', $element['#submit'])) {
        $new_submit = [];
        foreach ($element['#submit'] as $callback) {
          $new_submit[] = $callback;
          if ($callback == '::save') {
            $new_submit[] = [$form_display, 'saveFormEntities'];
          }
        }
        $element['#submit'] = $new_submit;
      }
    }
    else {
      foreach (Element::children($element) as $key) {
        FlexiformEntityFormDisplay::addSaveFormEntitiesSubmit($element[$key], $form_display);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormEntityConfig() {
    $this->initFormEntityConfig();
    return $this->formEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function addFormEntityConfig($namespace, $configuration) {
    $this->initFormEntityConfig();
    $this->formEntities[$namespace] = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeFormEntityConfig($namespace) {
    $this->initFormEntityConfig();
    unset($this->formEntities[$namespace]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function initFormEntityConfig() {
    if (empty($this->formEntities)) {
      $this->formEntities = [];

      $form_entities = $this->getThirdPartySetting('flexiform', 'form_entities', []);

      // If there is a base entity add it to the configuration.
      if ($this->getTargetEntityTypeId() && empty($form_entities[$this->baseEntityNamespace])) {
        $this->formEntities[$this->baseEntityNamespace] = [
          'entity_type' => $this->getTargetEntityTypeId(),
          'bundle' => $this->getTargetBundle(),
          'plugin' => 'provided',
          'label' => t(
            'Base @entity_type',
            [
              '@entity_type' => \Drupal::service('entity_type.manager')->getDefinition($this->getTargetEntityTypeId())->getLabel(),
            ]
          ),
        ];
      }

      $this->formEntities += $form_entities;
    }
  }

  /**
   * Get the form entity manager.
   */
  public function getFormEntityManager(array $provided = array()) {
    $supplied_namespaces = array_keys($provided);

    // If entities are being supplied that have not been supplied before then
    // rebuild the form entity manager.
    if (empty($this->formEntityManager) || count(array_diff($supplied_namespaces, $this->formEntityManagerSuppliedNamespaces))) {
      $this->formEntityManager = new FlexiformFormEntityManager($this, $provided);
      $this->formEntityManagerSuppliedNamespaces = $supplied_namespaces;
    }

    return $this->formEntityManager;
  }

}
