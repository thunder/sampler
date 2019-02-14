<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PermissionHandlerInterface;

class Reporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The report.
   *
   * @var array
   */
  protected $report;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity types whose usage will get counted per bundle.
   *
   * @var array
   */
  protected $bundledEntityTypes = [
    'node',
    'taxonomy_term',
    'media',
    'comment',
    'paragraph'
  ];

  /**
   * Reporter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The Permission handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PermissionHandlerInterface $permission_handler, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->permissionHandler = $permission_handler;
    $this->connection = $connection;
  }

  /**
   * Collect the data.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function collect() {
    $report = ['per_bundle_count' => []];
    foreach ($this->bundledEntityTypes as $entity_type) {
      // Entity does not exist in this installation? ignore it.
      if (!$this->entityTypeManager->hasDefinition($entity_type)) {
        continue;
      }
      $report['per_bundle_count'][$entity_type] = $this->countEntitiesPerBundle($entity_type);
    }
    $report += ['user_count' => $this->countUsers()];
    $report += ['paragraph_histogram' => $this->paragraphHistogram()];

    $this->report = $report;

    return $this;
  }

  /**
   * Print the report.
   *
   * @param null|string $filename
   *   The file to put the report into.
   */
  public function output($filename = NULL) {
    $report = $this->getFormattedReport();
    if ($filename) {
      file_put_contents($filename, $report);
    }
    else {
      print $report;
    }
  }

  /**
   * Format the report.
   *
   * @return string
   *   The formatted report.
   */
  protected function getFormattedReport() {
    return json_encode($this->report, JSON_PRETTY_PRINT);
  }

  /**
   * Count entity instances per bundle for given entity type.
   *
   * @param string $entity_type
   *   The entity type to count per bundle.
   *
   * @return array
   *   The entity count. Keyed by entity_type and bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function countEntitiesPerBundle($entity_type) {
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    $bundle_key = $entity_type_definition->getKey('bundle');
    $id_key = $entity_type_definition->getKey('id');

    return $this->countGroupedInstances(
        $entity_type,
        $bundle_key,
        $id_key
      );

  }

  /**
   * Count users per role, and count editors.
   *
   * For this we call "editors" all users, that are allowed to create, modify
   * or delete content. This might not only be users with the role "editor"
   *
   * @return array
   *   Return an array, that is keyed by role and has the grouped count as value.
   *   An additional key "editors" is added, that provides the count of editors.
   */
  protected function countUsers() {
    $entity_type = 'user';
    $group_key = 'roles';
    $results = ['per_role' => [], 'with_edit_permission' => 0];

    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    $id_key = $entity_type_definition->getKey('id');

    $results['per_role'] = $this->countGroupedInstances($entity_type, $group_key, $id_key);

    $editor_roles = $this->getEditorRoles('node');

    foreach ($editor_roles as $editor_role) {
      $results['with_edit_permission'] += $results['per_role'][$editor_role];
    }

    return $results;
  }

  /**
   * Count paragraph usage.
   */
  protected function paragraphHistogram() {
    $histogram = [];

    $entity_type = 'paragraph';
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      return;
    }

    $query = $this->connection->select('paragraphs_item_field_data', 'p')
      ->fields('p', ['parent_type', 'parent_id']);

    $query->addExpression('count(id)', 'count');
    $query->groupBy('parent_type');
    $query->groupBy('parent_id');

    $results = $query->execute();
    foreach ($results as $record) {
      $histogram[$record->parent_type][$record->count]++;
    }

    foreach ($histogram as $parent_type => $counts) {
      ksort($histogram[$parent_type]);
    }

    return $histogram;
  }

  /**
   * Get roles that can create, modify or delete things.
   *
   * @param $provider
   *  The permission provider
   *
   * @return array
   *  The roles.
   */
  protected function getEditorRoles($provider) {
    // Filter all permissions, that allow changing of node content.
    $permissions = array_filter($this->permissionHandler->getPermissions(), function ($permission, $permission_name) use ($provider) {
      return ($permission['provider'] === $provider && preg_match("/^(create|delete|edit|revert)/", $permission_name));
    }, ARRAY_FILTER_USE_BOTH);

    // Find all roles, that have these permissions.
    $roles = [];
    foreach ($permissions as $permission_name => $permission) {
      $roles += user_roles(FALSE, $permission_name);
    }

    return array_keys($roles);
  }

  protected function countGroupedInstances($entity_type, $group_key, $id_key) {
    $entity_type_storage = $this->entityTypeManager->getStorage($entity_type);
    $entity_aggregate_query = $entity_type_storage->getAggregateQuery();

    $count_alias = 'count';
    $counts = [];

    $result = $entity_aggregate_query
      ->groupBy($group_key)
      ->aggregate($id_key, 'COUNT', NULL, $count_alias)
      ->execute();

    foreach ($result as $item) {
      $first_key = key($item);
      if (!empty($item[$first_key])) {
        $counts[$item[$first_key]] = $item[$count_alias];
      }
    }

    return $counts;
  }

}
