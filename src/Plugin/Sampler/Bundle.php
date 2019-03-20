<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\SamplerBase;
use Drupal\sampler\Traits\GroupedDataTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects bundle data.
 *
 * @Sampler(
 *   id = "bundle",
 *   label = @Translation("Bundle"),
 *   description = @Translation("Collects bundle data."),
 *   deriver = "\Drupal\sampler\Plugin\Derivative\BundleDeriver"
 * )
 */
class Bundle extends SamplerBase {
  use GroupedDataTrait;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundle_info, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundle_info;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $data = [];
    $entityTypeId = $this->entityTypeId();
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);

    $baseTable = $entityTypeDefinition->getBaseTable();
    $bundleField = $entityTypeDefinition->getKey('bundle');
    $bundles = array_keys($this->bundleInfo->getBundleInfo($entityTypeId));

    $baseFields = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId);

    foreach ($bundles as $bundle) {
      $mapping = $this->getGroupMapping($entityTypeId, $bundle);
      $data[$mapping] = [];

      $query = $this->connection->select($baseTable, 'b');
      $query->condition($bundleField, $bundle);
      $data[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

      $fields = array_diff_key(
        $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
        $baseFields
      );

      if (empty($fields)) {
        continue;
      }

      $data[$mapping]['fields'] = ['count' => count($fields)];

      /** @var \Drupal\Core\Field\FieldConfigInterface $fieldConfig */
      foreach ($fields as $fieldConfig) {
        $fieldType = $fieldConfig->getType();

        if (!isset($data[$mapping]['fields'][$fieldType])) {
          $data[$mapping]['fields'][$fieldType] = 1;
          continue;
        }

        $data[$mapping]['fields'][$fieldConfig->getType()]++;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return $this->getBaseId();
  }

}
