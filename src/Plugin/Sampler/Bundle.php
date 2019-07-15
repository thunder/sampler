<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\FieldData;
use Drupal\sampler\GroupMapping;
use Drupal\sampler\SamplerBase;
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
   * The field data service.
   *
   * @var \Drupal\sampler\FieldData
   */
  protected $fieldData;

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
   * @param \Drupal\sampler\GroupMapping $group_mapping
   *   The group mapping service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\sampler\FieldData $fieldData
   *   The field data service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, GroupMapping $group_mapping, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundle_info, Connection $connection, FieldData $fieldData) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $group_mapping);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundle_info;
    $this->connection = $connection;
    $this->fieldData = $fieldData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sampler.group_mapping'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('database'),
      $container->get('sampler.field_data')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function collect() {
    $entityTypeId = $this->entityTypeId();
    $bundles = array_keys($this->bundleInfo->getBundleInfo($entityTypeId));

    foreach ($bundles as $bundle) {
      $mapping = $this->groupMapping->getGroupMapping($entityTypeId, $bundle);

      $this->collectedData[$mapping] = [];
      $this->collectedData[$mapping]['fields'] = $this->getFieldData($bundle);
      $this->collectedData[$mapping]['instances'] = $this->getInstances($bundle);
      $this->collectedData[$mapping]['components'] = $this->getComponents($bundle, $this->getPreferredViewMode());
    }

    return $this->collectedData;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return $this->getBaseId();
  }

  /**
   * Stub function to provide a view mode.
   *
   * For now, this will return th default view mode. We could make this more
   * intelligent, and maybe get the most used view mode, or the view mode with
   * the most components.
   *
   * @return string
   *   The view mode to collect components for
   */
  protected function getPreferredViewMode(): string {
    return 'default';
  }

  /**
   * Get field data from FieldData service.
   *
   * @param $bundle
   *  The bundle to collect data for.
   *
   * @return array
   *  The collected data.
   *
   * @see \Drupal\sampler\FieldData
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldData($bundle): array {
    $fieldData = [];
    $entityTypeId = $this->entityTypeId();
    $baseFields = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId);

    $fields = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
      $baseFields
    );

    /** @var \Drupal\Core\Field\FieldConfigInterface $fieldConfig */
    foreach ($fields as $fieldConfig) {
      $fieldData[] = $this->fieldData->collect($fieldConfig, $this->entityTypeId());
    }

    return $fieldData;
  }

  /**
   * Count number of entity instances with a given bundle.
   *
   * @param $bundle
   *  The bundle to collect data for.
   *
   * @return int
   *  The number of instances found.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getInstances($bundle): int {
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entityTypeId());

    $baseTable = $entityTypeDefinition->getBaseTable();
    $bundleField = $entityTypeDefinition->getKey('bundle');

    $query = $this->connection->select($baseTable, 'b');
    $query->condition($bundleField, $bundle);

    return (integer) $query->countQuery()->execute()->fetchField();
  }

  /**
   * Get displayed components (fields) of a given bundle for a view mode
   *
   * @param $bundle
   *  The bundle to collect data for.
   * @param $viewMode
   *  The view mode to collect data for.
   *
   * @return array
   *  The found components, given as an array of field names.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getComponents($bundle, $viewMode) {
    $components = [];

    if ($display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($this->entityTypeId() . '.' . $bundle . '.' . $viewMode)) {
      $components = array_keys($display->getComponents());
    }

    return $components;
  }

}
