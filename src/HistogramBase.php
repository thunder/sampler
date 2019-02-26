<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Histogram plugin plugins.
 */
abstract class HistogramBase extends PluginBase implements ContainerFactoryPluginInterface, HistogramInterface {

  protected $entityTypeManager;

  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, $entityTypeManager, $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

}
