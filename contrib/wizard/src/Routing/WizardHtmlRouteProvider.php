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
    if ($path = $wizard->getPath()) {
      if ($step) {
        $path .= '/{step}';
      }

      $defaults = [
        '_wizard' => '\Drupal\flexiform_wizard\Wizard\DefaultWizard',
        '_title' => 'Wizard Test',
        'tempstore_id' => 'flexiform_wizard.wizard.test',
        'machine_name' => $wizard->id(),
      ];

      $route = new Route($path);
      $route->setDefaults($defaults);
      $route->setRequirement('_access', 'TRUE');

      return $route;
    }
  }

}
