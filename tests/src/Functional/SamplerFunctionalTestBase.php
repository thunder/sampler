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
  protected static $modules = [
    'node',
    'sampler',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Create a given amount of users with a certain role.
   *
   * @param string $rid
   *   The role id the users should have.
   * @param int $number
   *   The number of users to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
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
   * @param int $numberOfNodes
   *   The number of nodes to create.
   * @param int $numberOfRevisions
   *   The number of additional revisions to create for each node.
   *
   * @return array
   *   The created nodes.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodesOfType(string $type, int $numberOfNodes, int $numberOfRevisions = 1) {
    $nodes = [];
    for ($i = 0; $i < $numberOfNodes; $i++) {
      $node = Node::create([
        'type' => $type,
        'title' => $this->randomString(),
      ]);

      for ($j = 0; $j < $numberOfRevisions; $j++) {
        $node->setNewRevision(TRUE);
        $node->save();
      }
      $nodes[] = $node;
    }
    return $nodes;
  }

}
