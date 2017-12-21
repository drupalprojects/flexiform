<?php
namespace Drupal\flexiform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\flexiform\FlexiformFormEntityPluginManager;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
