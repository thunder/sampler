<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for sampler plugins.
 */
abstract class SamplerBase extends PluginBase implements ContainerFactoryPluginInterface, SamplerInterface {

  /**
   * The group mapping service.
   *
   * @var \Drupal\sampler\GroupMapping
   */
  protected $groupMapping;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sampler\GroupMapping $group_mapping
   *   The group mapping service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    GroupMapping $group_mapping
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupMapping = $group_mapping;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sampler.group_mapping')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function anonymize(bool $anonymize) {
    $this->groupMapping->anonymize($anonymize);
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

}
