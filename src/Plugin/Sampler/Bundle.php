<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\sampler\FieldData;
use Drupal\sampler\Mapping;
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
   * The sampler settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $samplerSettings;
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
   * @param \Drupal\sampler\Mapping $mapping
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
   * @param \Drupal\Core\Config\ImmutableConfig $samplerSettings
   *   The sampler settings.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, Mapping $mapping, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundle_info, Connection $connection, FieldData $fieldData, ImmutableConfig $samplerSettings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $mapping);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundle_info;
    $this->connection = $connection;
    $this->fieldData = $fieldData;
    $this->samplerSettings = $samplerSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sampler.mapping'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('database'),
      $container->get('sampler.field_data'),
      $container->get('config.factory')->get('sampler.settings')
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
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);

    $baseTable = $entityTypeDefinition->getBaseTable();
    $bundleField = $entityTypeDefinition->getKey('bundle');
    $bundles = array_keys($this->bundleInfo->getBundleInfo($entityTypeId));

    foreach ($bundles as $bundle) {
      $mapping = $this->mapping->getBundleMapping($entityTypeId, $bundle);
      $this->collectedData[$mapping] = ['fields' => []];

      $query = $this->connection->select($baseTable, 'b');
      $query->condition($bundleField, $bundle);
      $this->collectedData[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

      $fields = $this->getSupportedFieldDefinitions($entityTypeId, $bundle);

      /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldConfig */
      foreach ($fields as $fieldName => $fieldConfig) {
        if ($this->isBaseField($fieldConfig)) {
          continue;
        }

        $fieldData = $this->fieldData->collect($fieldConfig, $this->entityTypeId());
        $this->collectedData[$mapping]['fields'][$this->mapping->getFieldMapping($entityTypeId, $fieldName)] = $fieldData;
      }
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
   * Check, if current field is an entity base field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldConfig
   *   The field config.
   *
   * @return bool
   *   Field is base field.
   */
  protected function isBaseField(FieldDefinitionInterface $fieldConfig) {
    return ($fieldConfig instanceof BaseFieldDefinition || $fieldConfig instanceof BaseFieldOverride);
  }

  /**
   * Get all supported field definitions.
   *
   * Filters all fields that have a not supported type. Supported types are
   * defined in sampler settings.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   The field definitions.
   */
  protected function getSupportedFieldDefinitions(string $entityTypeId, string $bundle) {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
    $supportedFieldTypes = array_flip($this->samplerSettings->get('supported_field_types'));

    $supportedFields = [];
    foreach ($fieldDefinitions as $fieldName => $fieldConfig) {
      if (isset($supportedFieldTypes[$fieldConfig->getType()])) {
        $supportedFields[$fieldName] = $fieldConfig;
      }
    }

    return $supportedFields;
  }

}
