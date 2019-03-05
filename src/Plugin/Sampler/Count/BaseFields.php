<?php

namespace Drupal\sampler\Plugin\Sampler\Count;

use Drupal\sampler\CountBase;

/**
 * Counts base fields of entities.
 *
 * @SamplerCount(
 *   id = "base_fields",
 *   label = @Translation("Base fields"),
 *   description = @Translation("Collects base field count.")
 * )
 */
class BaseFields extends CountBase {

  /**
   * {@inheritdoc}
   */
  public function collect($entityTypeId) {
    $baseFields = array_keys($this->entityFieldManager->getBaseFieldDefinitions($entityTypeId));
    return count($baseFields);
  }

}
