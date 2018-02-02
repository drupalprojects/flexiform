<?php

namespace Drupal\flexiform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class ReloadCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'reload',
    ];
  }

}
