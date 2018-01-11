<?php

namespace Drupal\flexiform;

use Drupal\Core\Utility\Token as CoreToken;

class Token extends CoreToken {

  /**
   * {@inheritdoc}
   *
   * Additionally this allows the user to supply aliases into options to allow
   * more than one of a given type of token.
   */
  public function generate($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    if (!empty($options['alias'][$type])) {
      $unaliased_data = [
        $options['alias'][$type] => $data[$type],
      ] + $data;
      return parent::generate($options['alias'][$type], $tokens, $unaliased_data, $options, $bubbleable_metadata);
    }
    else {
      return parent::generate($type, $tokens, $data, $options, $bubbleable_metadata);
    }
  }
}
