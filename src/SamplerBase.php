<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for sampler plugins.
 */
abstract class SamplerBase extends PluginBase implements ContainerFactoryPluginInterface, SamplerInterface {

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $anonymize;

  /**
   * The collected data.
   *
   * @var array
   */
  protected $collectedData = [];

  /**
   * {@inheritdoc}
   */
  public function anonymize(bool $anonymize) {
    $this->anonymize = $anonymize;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

}
