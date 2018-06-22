<?php

namespace Drupal\flexiform_wizard\Controller;

use Drupal\flexiform_wizard\Entity\WizardInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Symfony\Component\HttpFoundation\Request;

/**
 * The wizard edit page controller.
 */
class WizardPageEdit extends ControllerBase {

  /**
   * Get the page title.
   *
   * @param \Drupal\flexiform_wizard\Entity\WizardInterface $flexiform_wizard
   *   The wizard entity.
   * @param string $page
   *   The page we are on.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(WizardInterface $flexiform_wizard, $page) {
    $settings = $flexiform_wizard->getPages();
    return $settings[$page]['label'];
  }

  /**
   * Get the edit page form.
   *
   * @param \Drupal\flexiform_wizard\Entity\WizardInterface $flexiform_wizard
   *   The wizard entity.
   * @param string $page
   *   The page we are on.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The form.
   */
  public function pageContent(WizardInterface $flexiform_wizard, $page, Request $request) {
    $entity_form_display = EntityFormDisplay::load('flexiform_wizard.' . $flexiform_wizard->id() . '.' . $page);
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'status' => TRUE,
      ]);
    }

    $form_object = $this->entityTypeManager()->getFormObject('entity_form_display', 'edit');
    $form_object->setEntity($entity_form_display);

    $form_state = new FormState();
    $request->attributes->set('form', []);
    $request->attributes->set('form_state', $form_state);

    $args = \Drupal::service('controller_resolver')->getArguments(
      $request,
      [$form_object, 'buildForm']
    );

    $request->attributes->remove('form');
    $request->attributes->remove('form_state');

    unset($args[0], $args[1]);
    $form_state->addBuildInfo('args', array_values($args));
    return $this->formBuilder()->buildForm($form_object, $form_state);
  }

}
