<?php

namespace Drupal\Tests\flexiform_wizard\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Basic tests for flexiform wizard.
 *
 * @group FlexiformWizard
 */
class FlexiformWizardTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['flexiform_wizard_test'];

  /**
   * Use the standard profile so that we can use the 'page' node type.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Tests that the test flexiform wizard works.
   */
  public function testTestFlexiformWizard() {
    $account = $this->drupalCreateUser(['administer content']);
    $this->drupalLogin($account);

    // Create a test page.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test Page Node',
    ]);
    $node->save();

    $this->drupalGet('flexiform_wizard/test/' . $node->id() . '/' . $account->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
