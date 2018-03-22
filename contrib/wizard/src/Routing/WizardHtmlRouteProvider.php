<?php

namespace Drupal\flexiform_wizard\Routing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\flexiform_wizard\Entity\WizardInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines HTML route provider for flexiform wizard entities.
 */
class WizardHtmlRouteProvider extends DefaultHtmlRouteProvider {


  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Get a route for configuring a step.
    if ($entity_type->hasLinkTemplate('edit-form')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('edit-form').'/page/{page}');
      $route->setDefaults([
        '_controller' => '\Drupal\flexiform_wizard\Controller\WizardPageEdit::pageContent',
        '_title_callback' => '\Drupal\flexiform_wizard\Controller\WizardPageEdit::pageTitle',
      ]);
      $route->setRequirements([
        '_entity_access' => 'flexiform_wizard.update',
      ]);
      $collection->add("entity.flexiform_wizard.edit_page_form", $route);
    }

    $entity_type_id = $entity_type->id();
    $wizards = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();

    foreach ($wizards as $wizard) {
      if ($wizard_route = $this->getWizardRoute($wizard)) {
        $collection->add("{$entity_type_id}.{$wizard->id()}", $wizard_route);
      }

      if ($wizard_step_route = $this->getWizardRoute($wizard, TRUE)) {
        $collection->add("{$entity_type_id}.{$wizard->id()}.step", $wizard_step_route);
      }
    }

    return $collection;
  }


  /**
   * Gets the add page route.
   *
   * Built only for entity types that have bundles.
   *
   * @param \Drupal\flexiform_wizard\Entity\WizardInterface $wizard
   *   The wizard entity.
   * @param bool $step
   *   Whether to include the step parameter.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getWizardRoute(WizardInterface $wizard, $step = FALSE) {
    // Options.
    $options = [];
    foreach ($wizard->get('parameters') as $param_name => $param_info) {
      $options['parameters'][$param_name] = [
        'type' => 'entity:'.$param_info['entity_type'],
      ];
    }
    $options['parameters']['wizard'] = [
      'type' => 'entity:flexiform_wizard',
    ];

    if ($path = $wizard->getPath()) {
      if ($step) {
        $path .= '/{step}';
      }

      $defaults = [
        '_wizard' => '\Drupal\flexiform_wizard\Wizard\DefaultWizard',
        '_title' => 'Wizard Test',
        'wizard' => $wizard->id(),
      ];

      $route = new Route($path);
      $route->setDefaults($defaults);
      $route->setOptions($options);
      $route->setRequirement('_access', 'TRUE');

      return $route;
    }
  }

}
