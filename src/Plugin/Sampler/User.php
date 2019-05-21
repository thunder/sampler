<?php

namespace Drupal\sampler\Plugin\Sampler;

use Drupal\Core\Database\Connection;
use Drupal\sampler\GroupMapping;
use Drupal\sampler\SamplerBase;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects user data.
 *
 * @Sampler(
 *   id = "user",
 *   label = @Translation("User"),
 *   description = @Translation("Collects user data."),
 *   entity_type_id = "user"
 * )
 */
class User extends SamplerBase {

  /**
   * The permission handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

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
   * @param \Drupal\sampler\GroupMapping $group_mapping
   *   The group mapping service.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, GroupMapping $group_mapping, PermissionHandlerInterface $permission_handler, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $group_mapping);

    $this->permissionHandler = $permission_handler;
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
      $container->get('sampler.group_mapping'),
      $container->get('user.permissions'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $roles = array_keys(user_roles(TRUE));

    $nodeEditingRoles = $this->getEditorRoles('node');
    $taxonomyEditingRoles = $this->getEditorRoles('taxonomy');

    foreach ($roles as $role) {
      $mapping = $this->groupMapping->getGroupMapping($this->entityTypeId(), $role);

      $query = $this->connection->select('user__roles', 'b');
      $query->condition('roles_target_id', $role);

      $this->collectedData[$mapping]['instances'] = (integer) $query->countQuery()->execute()->fetchField();
      $this->collectedData[$mapping]['is_node_editing'] = in_array($mapping, $nodeEditingRoles);
      $this->collectedData[$mapping]['is_taxonomy_editing'] = in_array($mapping, $taxonomyEditingRoles);

    }

    // @todo user configurable field count?
    return $this->collectedData;
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
      $roles += user_roles(TRUE, $permissionName);
    }

    $roleNames = array_map(
      function ($role) {
        return $this->groupMapping->getGroupMapping($this->entityTypeId(), $role);
      },
      array_keys($roles)
    );

    return $roleNames;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return 'role';
  }

}
