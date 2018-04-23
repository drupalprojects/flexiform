<?php

namespace Drupal\flexiform_wizard\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Ticket type entities.
 */
interface WizardInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Returns whether the wizard entity is enabled.
   *
   * @return bool
   *   Whether the wizard entity is enabled or not.
   */
  public function status();

  /**
   * Returns the description for the wizard entity.
   *
   * @return string
   *   The description for the wizard entity.
   */
  public function getDescription();

  /**
   * Returns the path for the wizard entity.
   *
   * @return string
   *   The path for the wizard entity.
   */
  public function getPath();

  /**
   * Indicates if this wizard is an admin wizard or not.
   *
   * @return bool
   *   TRUE if this is an admin wizard, FALSE otherwise.
   */
  public function usesAdminTheme();

  /**
   * Get pages.
   *
   * @return array
   *   Array of information keyed by machine_name.
   */
  public function getPages();

  /**
   * Remove page.
   *
   * @param string $machine_name
   *   The machine name of the page to remove.
   */
  public function removePage($machine_name);

  /**
   * Add a page.
   *
   * @param string $machine_name
   *   The machine name of the page to add.
   * @param array $settings
   *   An array of settings describing the page.
   */
  public function addPage($machine_name, array $settings = []);

  /**
   * Whether to defer saving until all steps are completed.
   *
   * When set to true, entities will not be saved until the user
   * clicks the finish button. When set to false, entities will be saved at
   * the end of each wizard step.
   *
   * @return bool
   *   Whether the save should be delayed until all steps are completed.
   */
  public function shouldSaveOnFinish();

}
