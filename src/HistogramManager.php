<?php

namespace Drupal\sampler;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Histogram plugin manager.
 */
class HistogramManager extends DefaultPluginManager {

  /**
   * Constructs a new HistogramPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Sampler/Histogram', $namespaces, $module_handler, 'Drupal\sampler\HistogramInterface', 'Drupal\sampler\Annotation\SamplerHistogram');

    $this->alterInfo('sampler_histogram_plugin_info');
    $this->setCacheBackend($cache_backend, 'sampler_histogram_plugins');
  }

}
