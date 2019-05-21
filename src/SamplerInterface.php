<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Sampler plugins.
 */
interface SamplerInterface extends PluginInspectionInterface {

  /**
   * Sample the data.
   *
   * @return array
   *   The histogram array.
   */
  public function collect();

  /**
   * The key of the data in the result.
   *
   * @return string
   *   The key.
   */
  public function key();

  /**
   * Retrieve the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function entityTypeId();

}
