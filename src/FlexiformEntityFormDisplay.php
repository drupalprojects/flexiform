<?php

/**
 * @file
 * Contains \Drupal\flexiform\FlexiformEntityFormDisplay.
 */

namespace Drupal\flexiform;

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
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->setThirdPartySetting('flexiform', 'form_entities', $this->formEntities);
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
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $provided = $form_state->get('form_entity_provided') ?: [];
    $this->getFormEntityManager($entity, $provided);

    // Set #parents to 'top-level' by default.
    $form += array('#parents' => array());
    $original_parents = $form['#parents'];

    // Let each widget generate the form elements.
    foreach ($this->getComponents() as $name => $options) {
      // On each component reset the parents back to the original.
      $form['#parents'] = $original_parents;

      if ($widget = $this->getRenderer($name)) {
        if (strpos($name, ':')) {
          list($namespace, $field_name) = explode(':', $name, 2);

          // This is a form entity element so we need to tweak parents so that
          // form state values are grouped by entity namespace.
          $form['#parents'][] = $namespace;

          // Get the items from the entity manager.
          if ($form_entity = $this->getFormEntityManager($entity)->getEntity($namespace)) {
            $items = $form_entity->get($field_name);
          }
          else {
            // Skip this component if we can't get hold of an entity.
            continue;
          }
        }
        else {
          $items = $entity->get($name);
        }
        $items->filterEmptyItems();

        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$name]['#weight'] = $options['weight'];

        // Associate the cache tags for the field definition & field storage
        // definition.
        $field_definition = $this->getFieldDefinition($name);
        $this->renderer->addCacheableDependency($form[$name], $field_definition);
        $this->renderer->addCacheableDependency($form[$name], $field_definition->getFieldStorageDefinition());
      }
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
   */
  public function extractFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $extracted = parent::extractFormValues($entity, $form, $form_state);

    $original_parents = $form['#parents'];
    foreach ($this->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      // Skip base entity.
      if ($namespace == '') {
        continue;
      }

      // Skip any entity that didn't have a value.
      if (!$form_entity->getFormEntityContext()->hasContextValue()) {
        continue;
      }

      // Tweak parents to make the field values detectable.
      $form['#parents'] = $original_parents;
      $form['#parents'][] = $namespace;

      // Get the entity object.
      $entity_object = $form_entity->getFormEntityContext()->getContextValue();
      foreach ($entity_object as $field_name => $items) {
        $element_name = $namespace.':'.$field_name;
        if ($widget = $this->getRenderer($element_name)) {
          $widget->extractFormValues($items, $form, $form_state);
          $extracted[$element_name] = $element_name;
        }
      }
    }
    $form['#parents'] = $original_parents;

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
    if (empty($this->formEntities) && ($form_entities = $this->getThirdPartySetting('flexiform', 'form_entities'))) {
      $this->formEntities = $form_entities;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition($field_name) {
    if ($definition = parent::getFieldDefinition($field_name)) {
      return $definition;
    }

    // Find our own.
    if (strpos($field_name, ':')) {
      list($namespace, $form_entity_field_name) = explode(':', $field_name, 2);
      return $this->getFormEntityFieldDefinition($namespace, $form_entity_field_name);
    }
  }

  /**
   * Get the form entity manager.
   */
  public function getFormEntityManager(FieldableEntityInterface $entity = NULL, array $provided = array()) {
    if (empty($this->formEntityManager)) {
      $this->formEntityManager = new FlexiformFormEntityManager($this, $entity, $provided);
    }

    return $this->formEntityManager;
  }

  /**
   * Get the form entity field definitions.
   */
  public function getFormEntityFieldDefinitions() {
    $definitions = [];
    foreach ($this->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      // Ignore the base entity.
      if ($namespace == '') {
        continue;
      }

      $display_context = $this->displayContext;
      $definitions[$namespace] = array_filter(
        $this->entityManager()->getFieldDefinitions($form_entity->getEntityType(), $form_entity->getBundle()),
        function (FieldDefinitionInterface $field_definition) use ($display_context) {
          return $field_definition->isDisplayConfigurable($display_context);
        }
      );
    }
    return $definitions;
  }

  /**
   * Get a specific form entity field definition.
   */
  public function getFormEntityFieldDefinition($namespace, $field_name) {
    $definitions = $this->getFormEntityFieldDefinitions();
    if (isset($definitions[$namespace][$field_name])) {
      return $definitions[$namespace][$field_name];
    }
  }
}
