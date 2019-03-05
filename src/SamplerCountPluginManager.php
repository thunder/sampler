<?php

namespace Drupal\sampler;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the entity data plugin manager.
 */
class SamplerCountPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new SamplerCountPluginManager object.
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
    parent::__construct('Plugin/Sampler/Count', $namespaces, $module_handler, 'Drupal\sampler\SamplerCountInterface', 'Drupal\sampler\Annotation\SamplerCountData');

    $this->alterInfo('sampler_count_plugin_info');
    $this->setCacheBackend($cache_backend, 'sampler_count_plugins');
  }

}