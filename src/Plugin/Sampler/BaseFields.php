<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\sampler\SamplerBase;

/**
 * Counts base fields of entities.
 *
 * @Sampler(
 *   id = "base_fields",
 *   label = @Translation("Base fields"),
 *   description = @Translation("Collects base field count.")
 * )
 */
class BaseFields extends SamplerBase {

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $baseFields = array_keys($this->entityFieldManager->getBaseFieldDefinitions($this->entityTypeId));
    return count($baseFields);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->getPluginId();
  }

}
