<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;

/**
 * Provides the entity edit form.
 */
class FormEntityEditForm extends FormEntityBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexiform_form_entity_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlexiformEntityFormDisplayInterface $form_display = NULL, $entity_namespace = '') {
    $form = parent::buildForm($form, $form_state, $form_display);
    $form_entity = $this->formEntityManager()->getFormEntity($entity_namespace);

    return $this->buildConfigurationForm($form, $form_state, $form_entity, $entity_namespace);
  }

}
