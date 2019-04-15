<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sampler\SamplerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds histogram for revisionable entity types.
 *
 * @Sampler(
 *   id = "revision",
 *   label = @Translation("Revision"),
 *   description = @Translation("Builds histogram for revisions."),
 *   deriver = "\Drupal\sampler\Plugin\Derivative\RevisionDeriver"
 * )
 */
class Revision extends SamplerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Connection $connection) {
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

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $baseId = $this->getBaseId();
    $this->collectedData[$baseId] = [];

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entityTypeId());
    $revisionTable = $entityTypeDefinition->getRevisionTable();

    $idKey = $entityTypeDefinition->getKey('id');
    $revisionId = $entityTypeDefinition->getKey('revision');

    $query = $this->connection->select($revisionTable, 'r');
    $query->addExpression('count(' . $revisionId . ')', 'count');
    $query->groupBy($idKey);

    $results = $query->execute();
    foreach ($results as $record) {
      if (!isset($this->collectedData[$baseId][$record->count])) {
        $this->collectedData[$baseId][$record->count] = 1;
        continue;
      }
      $this->collectedData[$baseId][$record->count]++;
    }

    ksort($this->collectedData[$baseId]);
    return $this->collectedData;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return 'histogram';
  }

}
