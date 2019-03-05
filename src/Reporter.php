<?php

namespace Drupal\sampler;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldmanager;

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
   * @var string[]
   */
  protected $bundledEntityTypes = [];

  /**
   * Mapping of group names to integer values.
   *
   * @var string[]
   */
  protected $groupMapping = [];

  /**
   * The group count manager service.
   *
   * @var \Drupal\sampler\CountPluginManager
   */
  protected $countPluginManager;

  /**
   * The histogram manager service.
   *
   * @var \Drupal\sampler\HistogramPluginManager
   */
  protected $histogramPluginManager;

  /**
   * Store if data should be anonymized.
   *
   * @var bool
   */
  protected $anonymize;

  /**
   * Reporter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The Permission handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\sampler\CountPluginManager $count_plugin_manager
   *   The group count manager service.
   * @param \Drupal\sampler\HistogramPluginManager $histogram_plugin_manager
   *   The histogram manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, EntityFieldManagerInterface $entity_field_manager, PermissionHandlerInterface $permission_handler, Connection $connection, CountPluginManager $count_plugin_manager, HistogramPluginManager $histogram_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->entityFieldmanager = $entity_field_manager;
    $this->permissionHandler = $permission_handler;
    $this->connection = $connection;
    $this->countPluginManager = $count_plugin_manager;
    $this->histogramPluginManager = $histogram_plugin_manager;

    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition->getBundleEntityType() && $definition->getBaseTable()) {
        $this->bundledEntityTypes[] = $definition->id();
      }
    }

    $this->bundledEntityTypes[] = 'user';
    $this->setAnonymize(TRUE);
  }

  /**
   * Collect the data.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function collect(): Reporter {
    $report['count'] = $this->count();
    $report['histogram'] = $this->histogram();

    $this->report = $report;
    return $this;
  }

  /**
   * Print the report.
   *
   * @param null|string $file
   *   The file to put the report into.
   */
  public function output($file = NULL) {
    $report = $this->getReport(TRUE);

    if ($file) {
      file_put_contents($file, $report);
    }
    else {
      print $report;
    }
  }

  /**
   * Get the report.
   *
   * @param bool $formatted
   *   If set to true, the report will be formatted into a string.
   *
   * @return array|string
   *   The report.
   */
  public function getReport(bool $formatted = FALSE) {
    if ($formatted === TRUE) {
      return $this->getFormattedReport();
    }
    return $this->report;
  }

  /**
   * Set anonymize flag.
   *
   * If set to true, bundle and role names are anonymized in output.
   * Otherwise the machine name of bundles and roles are printed.
   *
   * @param bool $anonymize
   *   Anonymize or not.
   *
   * @return $this
   */
  public function setAnonymize(bool $anonymize): Reporter {
    $this->anonymize = $anonymize;

    return $this;
  }

  /**
   * Format the report.
   *
   * @return string
   *   The formatted report.
   */
  protected function getFormattedReport(): string {
    return json_encode($this->report, JSON_PRETTY_PRINT);
  }

  /**
   * Retrieve counts per entity.
   */
  protected function count(): array {
    $data = [];

    foreach ($this->bundledEntityTypes as $entityTypeId) {
      $data[$entityTypeId] = [];

      foreach ($this->countPluginManager->getDefinitions() as $definition) {
        /** @var \Drupal\sampler\CountInterface $instance */
        $instance = $this->countPluginManager->createInstance($definition['id']);
        $data[$entityTypeId][$definition['id']] = $instance->collect($entityTypeId);
      }

      $baseFields = array_keys($this->entityFieldmanager->getBaseFieldDefinitions($entityTypeId));

      $settings = $this->getGroupingSettings($entityTypeId);
      foreach (array_keys($settings['groups']) as $group) {
        $mapping = $this->getGroupMapping($entityTypeId, $group);

        $query = $this->connection->select($settings['baseTable'], 'b');
        $query->condition($settings['bundleField'], $group);
        $data[$entityTypeId][$settings['groupKey']][$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();

        if ($entityTypeId !== 'user') {
          $fields = array_diff_key(
            array_keys($this->entityFieldmanager->getFieldDefinitions($entityTypeId, $group)),
            array_keys($baseFields)
          );
          $data[$entityTypeId][$settings['groupKey']][$mapping]['fields'] = count($fields);
        }
      }

      if ($entityTypeId === 'user') {
        $roleCounts = $data[$entityTypeId][$settings['groupKey']];
        $data[$entityTypeId]['editing_users'] = $this->countEditingUsers($roleCounts);
      }
    }

    return $data;
  }

  /**
   * Collect histogram data from plugins.
   *
   * @return array
   *  The histogram data
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function histogram(): array {
    $data = [];

    foreach ($this->bundledEntityTypes as $entityTypeId) {
      $data[$entityTypeId] = [];

      foreach ($this->histogramPluginManager->getDefinitions() as $definition) {
        /** @var \Drupal\sampler\HistogramInterface $instance */
        $instance = $this->histogramPluginManager->createInstance($definition['id']);
        if ($instance->isApplicable($entityTypeId)) {
          $data[$entityTypeId][$definition['id']] = $instance->collect($entityTypeId);
        }
      }
    }

    return $data;
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
  protected function getEditorRoles($provider): array {
    // Filter all permissions, that allow changing of node content.
    $permissions = array_filter($this->permissionHandler->getPermissions(), function ($permission, $permissionName) use ($provider) {
      return ($permission['provider'] === $provider && preg_match("/^(create|delete|edit|revert)/", $permissionName));
    }, ARRAY_FILTER_USE_BOTH);

    // Find all roles, that have these permissions.
    $roles = [];
    foreach ($permissions as $permissionName => $permission) {
      $roles += user_roles(TRUE, $permissionName);
    }

    return array_keys($roles);
  }

  /**
   * Get some settings for grouping entities.
   *
   * @param string $entityTypeId
   *   The entity type to get settings for.
   *
   * @return array
   *   The settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupingSettings($entityTypeId): array {
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $settings = [];

    // For user we count per role, everything else is counted per bundle.
    if ($entityTypeId === 'user') {
      $settings['baseTable'] = 'user__roles';
      $settings['bundleField'] = 'roles_target_id';
      $settings['groups'] = user_roles(TRUE);
      $settings['groupKey'] = 'roles';
    }
    else {
      $settings['baseTable'] = $entityTypeDefinition->getBaseTable();
      $settings['bundleField'] = $entityTypeDefinition->getKey('bundle');
      $settings['groups'] = $this->bundleInfo->getBundleInfo($entityTypeId);
      $settings['groupKey'] = 'bundles';
    }

    return $settings;
  }

  /**
   * Count users, that can edit stuff.
   *
   * @param array $roleCounts
   *   Known counts of users in roles.
   *
   * @return array
   *   The number of editing users per provider.
   */
  protected function countEditingUsers(array $roleCounts): array {
    $provider = ['node', 'taxonomy'];
    $editingUsers = [];

    foreach ($provider as $p) {
      $editingUsers[$p] = ['instances' => 0];
      $editorRoles = $this->getEditorRoles($p);

      foreach ($editorRoles as $editorRole) {
        $editingUsers[$p]['instances'] += $roleCounts[$this->getGroupMapping('user', $editorRole)]['instances'];
      }
    }

    return $editingUsers;
  }

  /**
   * Map group names to an integer value.
   *
   * The mapped integer will be used in output instead of the group name.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   * @param int $mapping
   *   The id to use as mapping, must be an integer and unique.
   *
   * @throws \InvalidArgumentException
   */
  protected function setGroupMapping(string $entityTypeId, string $group, int $mapping) {
    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [];
    }
    if (in_array($mapping, $this->groupMapping[$entityTypeId])) {
      throw new \InvalidArgumentException('Mapping already exists');
    }

    $this->groupMapping[$group] = $mapping;
  }

  /**
   * Get mapped value of a group.
   *
   * @param string $entityTypeId
   *   The grouped entity.
   * @param string $group
   *   The group to map.
   *
   * @return string
   *   The mapped value.
   */
  protected function getGroupMapping($entityTypeId, $group): string {
    if ($this->anonymize === FALSE) {
      return $group;
    }

    if (!isset($this->groupMapping[$entityTypeId])) {
      $this->groupMapping[$entityTypeId] = [$group => 'group-0'];
      return $this->groupMapping[$entityTypeId][$group];
    }

    if (!isset($this->groupMapping[$entityTypeId][$group])) {
      $last = end($this->groupMapping[$entityTypeId]);
      $this->groupMapping[$entityTypeId][$group] = ++$last;
    }

    return $this->groupMapping[$entityTypeId][$group];
  }

}
