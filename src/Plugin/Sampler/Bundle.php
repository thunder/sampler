<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\FieldData;
use Drupal\sampler\FieldHelperTrait;
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

  use FieldHelperTrait;

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
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle information service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\sampler\FieldData $fieldData
   *   The field data service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, GroupMapping $group_mapping, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfo, Connection $connection, FieldData $fieldData, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $group_mapping);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundleInfo;
    $this->connection = $connection;
    $this->fieldData = $fieldData;
    $this->entityDisplayRepository = $entityDisplayRepository;
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
      $container->get('sampler.field_data'),
      $container->get('entity_display.repository')
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
      $this->collectedData[$mapping]['components'] = $this->getComponents($bundle);
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

    $fields = $this->getNonBaseFieldDefinitions($entityTypeId, $bundle, $this->entityFieldManager);

    /** @var \Drupal\Core\Field\FieldConfigInterface $fieldConfig */
    foreach ($fields as $fieldConfig) {
      $fieldData[] = $this->fieldData->collect($fieldConfig, $entityTypeId);
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
   *
   * @return array
   *  The found components, given as an array of field names.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getComponents($bundle) {
    $components = [];
    $displays = array_keys($this->entityDisplayRepository->getViewModeOptionsByBundle($this->entityTypeId(), $bundle));

    foreach ($displays as $display) {
      if ($displayConfig = $this->entityTypeManager
          ->getStorage('entity_view_display')
          ->load($this->entityTypeId() . '.' . $bundle . '.' . $display)) {

        $entityTypeId = $this->entityTypeId();
        $baseFields = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId);
        $fieldNames = array_keys($displayConfig->getComponents());

        $indexes = ['base_field' => [], 'field' => []];
        foreach($fieldNames as $fieldName) {
          if (isset($baseFields[$fieldName])){
            $indexes['base_field'][] = $this->getFieldIndex($entityTypeId, $fieldName, $this->entityFieldManager);
          }
          else {
            $indexes['field'][] = $this->getFieldIndex($entityTypeId, $fieldName, $this->entityFieldManager, $bundle);
          }
        }

        $components[] = array_filter($indexes);
      }
    }

    return $components;
  }

}
