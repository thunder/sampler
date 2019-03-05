<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Histogram plugins.
 */
interface SamplerInterface extends PluginInspectionInterface {

  /**
   * Checks if the current entity type id is supported by this plugin.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return bool
   *   Supported or not.
   */
  public function isApplicable($entityTypeId);

  /**
   * Build histogram for the provided entity type ID.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return array
   *   The histogram array.
   */
  public function collect($entityTypeId);

  /**
   * The key of the data in the result.
   *
   * @param int $entityTypeId
   *   The entity type ID.
   *
   * @return string
   */
  public function key(int $entityTypeId);

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
