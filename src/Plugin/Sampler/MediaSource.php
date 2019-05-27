<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\GroupMapping;
use Drupal\sampler\SamplerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects user data.
 *
 * @Sampler(
 *   id = "media_source",
 *   label = @Translation("Media source"),
 *   description = @Translation("Collects media source data."),
 *   entity_type_id = "media"
 * )
 */
class MediaSource extends SamplerBase {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, GroupMapping $group_mapping, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $group_mapping);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_field.manager')
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
    $bundleEntityType = $entityTypeDefinition->getBundleEntityType();

    $types = $this->entityTypeManager->getStorage($bundleEntityType)->loadMultiple();

    foreach ($types as $name => $type) {
      if ($type->getEntityType()->getProvider() === 'media_entity') {
        $sourceField = $type->getTypeConfiguration()['source_field'];
        $source = $type->getType()->getPluginId();
      }
      else {
        $sourceField = $type->getSource()->getSourceFieldDefinition($type)->getName();
        $source = $type->getSource()->getPluginId();
      }

      $mapping = $this->groupMapping->getGroupMapping($entityTypeId, $name);
      $this->collectedData[$mapping]['source'] = $source;
      $this->collectedData[$mapping]['source-field-index'] = $this->getSourceFieldIndex($sourceField, $name);
    }

    return $this->collectedData;
  }

  /**
   * Retrieve the index of the source field.
   *
   * This implementation mimics the generation of fields in Bundle.php.
   *
   * @param string $sourceField
   *   The source field name.
   * @param string $bundle
   *   The media bundle.
   *
   * @return int
   *   The index of the source field
   */
  protected function getSourceFieldIndex(string $sourceField, string $bundle) {
    $entityTypeId = $this->entityTypeId();
    $baseFields = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId);

    $fields = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle),
      $baseFields
    );

    return array_search($sourceField, array_keys($fields));
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return 'bundle';
  }

}
