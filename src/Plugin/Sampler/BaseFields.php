<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\SamplerBase;
use Drupal\sampler\Traits\FieldDataTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Counts base fields of entities.
 *
 * @Sampler(
 *   id = "base_fields",
 *   label = @Translation("Base fields"),
 *   description = @Translation("Collects base field count."),
 *   deriver = "\Drupal\sampler\Plugin\Derivative\BaseFieldDeriver"
 * )
 */
class BaseFields extends SamplerBase {
  use FieldDataTrait;

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
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collect() {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition */
    foreach ($this->entityFieldManager->getBaseFieldDefinitions($this->entityTypeId()) as $fieldDefinition) {
      $fieldType = $fieldDefinition->getType();

      if (!isset($this->collectedData[$fieldType])) {
        $this->collectedData[$fieldType] = [];
      }

      $fieldData = $this->collectDefaultFieldData($fieldDefinition);

      if (in_array($fieldDefinition->getType(), ['entity_reference', 'entity_reference_revisions'])) {
        $fieldData = array_merge($fieldData, $this->collectEntityReferenceData($fieldDefinition));
      }

      $this->collectedData[$fieldType] = $fieldData;
    }

    return $this->collectedData;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): string {
    return $this->getBaseId();
  }

}
