<?php

namespace Drupal\sampler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for sampler plugins.
 */
abstract class SamplerBase extends PluginBase implements ContainerFactoryPluginInterface, SamplerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The permission handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $anonymize;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   *
   * Overrides the construction of sampler count plugins to inject some
   * services.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, Connection $connection, EntityTypeBundleInfoInterface $bundle_info, PermissionHandlerInterface $permission_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->connection = $connection;
    $this->bundleInfo = $bundle_info;
    $this->permissionHandler = $permission_handler;
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
      $container->get('entity_field.manager'),
      $container->get('database'),
      $container->get('entity_type.bundle.info'),
      $container->get('user.permissions')
    );
  }

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
