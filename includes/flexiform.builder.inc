<?php
/**
 * @file
 * Specify builder classes for flexiforms.
 */

/**
 * Interface for all Flexiform Builders.
 */
interface FlexiformBuilderInterface {

  /**
   * Build the form for this flexiform.
   */
  public function form($form, &$form_state);

  /**
   * Validate the input for the form.
   */
  public function formValidate($form, &$form_state);

  /**
   * Submit the form.
   */
  public function formSubmit($form, &$form_state);

  /**
   * Support Components.
   *
   * Determine whether or not a builder supports a paricular component.
   */
  public function supportsComponent($component);
}

/**
 * Base class for flexiform builders.
 */
class FlexiformBuilder implements FlexiformBuilderInterface {

  /**
   * The flexiform object.
   */
  protected $flexiform;

  /**
   * The base entity.
   */
  protected $base_entity;

  /**
   * The base entity type.
   */
  protected $base_entity_type;

  /**
   * Construct the Flexiform Builder.
   */
  public function __construct($flexiform, $base_entity) {
    $this->flexiform = $flexiform;
    $this->base_entity = $base_entity;
    $this->base_entity_type = $flexiform->base_entity;
  }

  /**
   * Build the form for this flexiform.
   */
  public function form($form, &$form_state) {
    $form['#flexiform'] = $this->flexiform;
    $form['#flexiform_base_entity'] = $this->base_entity;
    $form['#flexiform_builder'] = $this;
    $form['#theme'] = array('flexiform__'.$this->flexiform->form, 'flexiform');
    $form['#contextual_links']['flexiform'] = array('admin/structure/flexiforms/manage', array($this->flexiform->form));

    return $form;
  }

  /**
   * Validate the input for the form.
   */
  public function formValidate($form, &$form_state) {}

  /**
   * Submit the form.
   */
  public function formSubmit($form, &$form_state) {}

  /**
   * Submit the form and set up the redirect.
   */
  public function formSubmitRedirect($form, &$form_state) {
    if (empty($this->flexiform->settings['redirect']['path'])) {
      return;
    }

    $redirect = $this->flexiform->settings['redirect']['path'];
    $form_state['redirect'] = $this->replaceCtoolsSubstitutions($redirect);
  }

  /**
   * Invoke hooks to alter the build of this form.
   */
  public function invoke(&$form, &$form_state, $hook = '') {
    if (!empty($hook)){
      $hooks = array(
        'flexiform_build_' . $hook,
        'flexiform_build_' . $this->flexiform->builder . '_' . $hook,
      );
    }
    else {
      $hooks = array(
        'flexiform_build',
        'flexiform_build_' . $this->flexiform->builder,
      );
    }

    drupal_alter($hooks, $form, $form_state, $this->flexiform);
  }

  /**
   * Get the Flexiform.
   */
  public function getFlexiform() {
    return $this->flexiform;
  }

  /**
   * Get a form entity.
   *
   * @param $namespace
   *  The namespace of the entity required.
   *
   * @return
   *  The entity with that namespace.
   */
  public function getFormEntity($namespace, &$state = array()) {
    return ($namespace == 'base_entity') ? $this->base_entity : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsComponent($component) {
    return FALSE;
  }

  /**
   * Get an array of ctools context for the flexiform.
   *
   * @return ctools_context[]
   *   An array of ctools contexts.
   */
  public function getCtoolsContexts() {
    global $user;

    ctools_include('context');
    $contexts = array(
      'current-user' => ctools_context_create('entity:user', $user),
    );
    $contexts['current-user']->keyword = 'current-user';
    $contexts['current-user']->identifier = t('Logged-in user');
    foreach ($this->flexiform->entities as $namespace => $info) {
      // Attempt to get the entity. Ignore any exceptions.
      try {
        $entity = $this->getFormEntity($namespace);
      }
      catch(Exception $e) {}

      // Create context.
      $type = 'entity:' . $info['entity_type'];
      if (!empty($entity)) {
        $contexts[$namespace] = ctools_context_create($type, $entity);
      }
      else {
        $contexts[$namespace] = ctools_context_create_empty($type);
      }
      $contexts[$namespace]->keyword = $namespace;
      $contexts[$namespace]->identifier = $info['label'];
    }

    return $contexts;
  }

  /**
   * Replace ctools substitutions with their values.
   *
   * @param string $string
   *   The string we want to replace in.
   * @param array $keywords
   *   Optionally provide additional keywords to replace.
   *
   * @return string
   *   $string with it's substitutions replaced.
   */
  public function replaceCtoolsSubstitutions($string, $keywords = array()) {
    $contexts = $this->getCtoolsContexts();
    return ctools_context_keyword_substitute($string, $keywords, $contexts);
  }

  /**
   * Build a list of possible ctools substitutions.
   *
   * @param array $keywords
   *   Optionally provide additional keywords to show.
   *
   * @return array
   *   A render array of substitutions.
   */
  public function getCtoolsSubstitutionsList($keywords = array()) {
    $content = array(
      '#theme' => 'table',
      '#header' => array(t('Keyword'), t('Value')),
      '#rows' => array(),
    );

    foreach ($this->getCtoolsContexts() as $context) {
      foreach (ctools_context_get_converters('%' . check_plain($context->keyword) . ':', $context) as $keyword => $title) {
        $content['#rows'][] = array(
          check_plain($keyword),
          t('@identifier: @title', array('@title' => $title, '@identifier' => $context->identifier)),
        );
      }
    }

    if (count($content['#rows'])) {
      return $content;
    }
    else {
      return array();
    }
  }
}
