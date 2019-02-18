<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\PermissionHandlerInterface;

/**
 * The Reporter class.
 *
 * @package Drupal\sampler
 */
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
    'paragraph',
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
   */
  public function collect() {
    $report = [];

    $report['structure'] = $this->structureData();

    $report['count'] = array_merge_recursive(
      $this->countBundleGroups(),
      $this->countUsers()
    );

    $report['histogram'] = array_merge_recursive(
      $this->revisionHistogram(),
      $this->paragraphHistogram()
    );

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
   * Count users per role, and count editors.
   *
   * For this we call "editors" all users, that are allowed to create, modify
   * or delete content. This might not only be users with the role "editor"
   *
   * @return array
   *   Return an array that is keyed by role and has the grouped count as value.
   *   An additional key "with_edit_permission" is added that provides the
   *   count of editors grouped by entity type..
   */
  protected function countUsers() {
    $baseTable = 'user__roles';
    $groupKey = 'roles_target_id';
    $idKey = 'entity_id';

    $results['user']['per_role'] = $this->countGroupedInstances($baseTable, $groupKey, $idKey);

    $provider = ['node', 'taxonomy'];
    foreach ($provider as $p) {
      $editorRoles = $this->getEditorRoles($p);

      foreach ($editorRoles as $editorRole) {
        $results['user']['with_edit_permission'][$p] += $results['user']['per_role'][$editorRole];
      }
    }

    return $results;
  }

  protected function revisionHistogram() {
    $histogram = [];

    foreach ($this->bundledEntityTypes as $entityType) {
      if (!$this->entityTypeManager->hasDefinition($entityType)) {
        continue;
      }

      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityType);
      if (!$entityTypeDefinition->isRevisionable()) {
        continue;
      }

      $revisionTable = $entityTypeDefinition->getRevisionTable();
      $idKey = $entityTypeDefinition->getKey('id');
      $revisionId = $entityTypeDefinition->getKey('revision');

      $query = $this->connection->select($revisionTable, 'r');
      $query->addExpression('count(' . $revisionId . ')', 'count');
      $query->groupBy($idKey);

      $results = $query->execute();
      foreach ($results as $record) {
        $histogram[$entityType]['revision'][$record->count]++;
      }

      foreach ($histogram as $entityType => $counts) {
        ksort($histogram[$entityType]['revision']);
      }
    }

    return $histogram;
  }

  /**
   * Count paragraph usage.
   */
  protected function paragraphHistogram() {
    $histogram = [];

    $entityType = 'paragraph';
    if (!$this->entityTypeManager->hasDefinition($entityType)) {
      return;
    }

    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityType);
    $dataTable = $entityTypeDefinition->getDataTable();

    $query = $this->connection
      ->select($dataTable, 'r')
      ->fields('r', ['parent_type']);

    $query->addExpression('count(id)', 'count');
    $query->groupBy('parent_type');
    $query->groupBy('parent_id');

    $results = $query->execute();
    foreach ($results as $record) {
      $histogram[$record->parent_type]['paragraph'][$record->count]++;
    }

    foreach ($histogram as $parentType => $counts) {
      ksort($histogram[$parentType]['paragraph']);
    }

    return $histogram;
  }

  /**
   * Get roles that can create, modify or delete things.
   *
   * @param string $provider
   *   The permission provider.
   *
   * @return array
   *   The roles.
   */
  protected function getEditorRoles($provider) {
    // Filter all permissions, that allow changing of node content.
    $permissions = array_filter($this->permissionHandler->getPermissions(), function ($permission, $permissionName) use ($provider) {
      return ($permission['provider'] === $provider && preg_match("/^(create|delete|edit|revert)/", $permissionName));
    }, ARRAY_FILTER_USE_BOTH);

    // Find all roles, that have these permissions.
    $roles = [];
    foreach ($permissions as $permissionName => $permission) {
      $roles += user_roles(FALSE, $permissionName);
    }

    return array_keys($roles);
  }

  protected function countGroupedInstances($baseTable, $groupKey, $idKey) {
    $groupAlias = 'group';
    $countAlias = 'count';

    $counts = [];

    $query = $this->connection->select($baseTable, 'b');
    $query->addField('b', $groupKey, $groupAlias);
    $query->addExpression("count($idKey)", $countAlias);
    $query->groupBy($groupKey);
    $result = $query->execute();


    foreach ($result as $item) {
      $counts[$item->$groupAlias] = $item->$countAlias;
    }

    return $counts;
  }

  /**
   * Count items per bundle.
   *
   * @return mixed
   *   The bundle counts.
   */
  protected function countBundleGroups() {
    $bundles = [];
    foreach ($this->bundledEntityTypes as $entityType) {
      // Entity does not exist in this installation? ignore it.
      if (!$this->entityTypeManager->hasDefinition($entityType)) {
        continue;
      }

      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityType);
      $baseTable = $entityTypeDefinition->getBaseTable();
      $bundleKey = $entityTypeDefinition->getKey('bundle');
      $idKey = $entityTypeDefinition->getKey('id');

      $bundles[$entityType]['per_bundle'] = $this->countGroupedInstances(
        $baseTable,
        $bundleKey,
        $idKey
      );

    }
    return $bundles;
  }

  /**
   * Retrieve structural information.
   */
  protected function structureData() {
    foreach ($this->bundledEntityTypes as $entityType) {
      if (!$this->entityTypeManager->hasDefinition($entityType)) {
        continue;
      }

      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityType);

    }
  }

}
