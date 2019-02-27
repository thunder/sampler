<?php

namespace Drupal\Tests\sampler\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Base class for Sampler functional tests.
 */
abstract class SamplerFunctionalTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field',
    'media',
    'taxonomy',
    'sampler',
    'sampler_test',
  ];

  /**
   * Create a given amount of users with a certain role.
   *
   * @param string $rid
   *   The role id the users should have.
   * @param int $number
   *   The number of users to create.
   */
  protected function createUsersWithRole(string $rid, int $number) {
    for ($i = 0; $i < $number; $i++) {
      $user = [];
      $user['name'] = $this->randomMachineName();
      $user['mail'] = $user['name'] . '@example.com';
      $user['pass'] = user_password();
      $user['status'] = 1;
      $user['roles'] = [$rid];

      $account = User::create($user);
      $account->save();
    }
  }

  /**
   * Create a given amount of nodes of a certain type.
   *
   * @param string $type
   *   The type of nodes to create.
   * @param int $number
   *   The number of nodes to create.
   */
  protected function createNodesOfType(string $type, int $number) {
    for ($i = 0; $i < $number; $i++) {
      $node = Node::create([
        'type' => $type,
        'title' => $this->randomString(),
      ]);

      $node->save();
    }
  }

}
