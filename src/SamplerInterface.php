<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Sampler plugins.
 */
interface SamplerInterface extends PluginInspectionInterface {

  /**
   * Sets the entity type to use.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   *
   * @return bool
   *   Returns true, if entity type is applicable for this plugin.
   */
  public function setEntityType(string $entityTypeId);

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
   * Set anonymize flag.
   *
   * If set to true, data used in keys will be anonymized in output.
   * Otherwise the machine names will be printed.
   *
   * @param bool $anonymize
   *   Anonymize or not.
   */
  public function anonymize(bool $anonymize);

}
