<?php

namespace Drupal\Tests\sampler\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the sampler module.
 *
 * @group sampler
 */
class SamplerFunctionalTest extends SamplerFunctionalTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'media',
    'taxonomy',
    // Enable contact as it provides a fieldable entity with no storage.
    'contact',
    // Enable file as it provides a fieldable entity with SQL storage but no
    // bundles.
    'file',
    'sampler_test',
    'media_test_type',
  ];

  /**
   * Test sampling of user data.
   */
  public function testUserDataSampling() {
    $nonEditingRid = 'restricted';
    $nodeEditingRid = 'node_editor';
    $taxonomyEditingRid = 'term_editor';
    $allEditingRid = 'all_editor';

    $numberOfRestricted = 1;
    $numberOfNodeEditors = 2;
    $numberOfTaxonomyEditors = 3;
    $numberOfAllEditors = 4;

    // Create multiple roles with different editing capabilities.
    $this->createRole([], $nonEditingRid);
    $this->createRole(['create type_one content'], $nodeEditingRid);
    $this->createRole(['edit terms in vocabulary_one'], $taxonomyEditingRid);
    $this->createRole(['create type_two content', 'edit terms in vocabulary_one'], $allEditingRid);

    // Foreach role create a defined number of users.
    $this->createUsersWithRole($nonEditingRid, $numberOfRestricted);
    $this->createUsersWithRole($nodeEditingRid, $numberOfNodeEditors);
    $this->createUsersWithRole($taxonomyEditingRid, $numberOfTaxonomyEditors);
    $this->createUsersWithRole($allEditingRid, $numberOfAllEditors);

    // Get the report.
    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $userReport = $report['user'];

    // Test if the report contains the correct number of users per role.
    $this->assertEquals($numberOfRestricted, $userReport['role'][$nonEditingRid]['instances']);
    $this->assertEquals($numberOfNodeEditors, $userReport['role'][$nodeEditingRid]['instances']);
    $this->assertEquals($numberOfTaxonomyEditors, $userReport['role'][$taxonomyEditingRid]['instances']);
    $this->assertEquals($numberOfAllEditors, $userReport['role'][$allEditingRid]['instances']);

    // Test if the report correctly determines, if roles are allow to edit nodes
    // and terms.
    $this->assertEquals(FALSE, $userReport['role'][$nonEditingRid]['is_node_editing']);
    $this->assertEquals(FALSE, $userReport['role'][$nonEditingRid]['is_taxonomy_editing']);

    $this->assertEquals(TRUE, $userReport['role'][$nodeEditingRid]['is_node_editing']);
    $this->assertEquals(FALSE, $userReport['role'][$nodeEditingRid]['is_taxonomy_editing']);

    $this->assertEquals(FALSE, $userReport['role'][$taxonomyEditingRid]['is_node_editing']);
    $this->assertEquals(TRUE, $userReport['role'][$taxonomyEditingRid]['is_taxonomy_editing']);

    $this->assertEquals(TRUE, $userReport['role'][$allEditingRid]['is_node_editing']);
    $this->assertEquals(TRUE, $userReport['role'][$allEditingRid]['is_taxonomy_editing']);
  }

  /**
   * Test sampling of node data.
   */
  public function testNodeDataSampling() {
    $nodeTypeOne = 'type_one';
    $nodeTypeTwo = 'type_two';

    $numberOfNodesTypeOne = 2;
    $numberOfNodesTypeTwo = 3;

    $stringFieldNotRequiredTranslatable = 'field_one';
    $stringFieldRequiredNotTranslatable = 'field_two';
    $entityReferenceFieldSingleTarget = 'field_three';
    $entityReferenceFieldMultipleTargets = 'field_four';

    $this->createNodesOfType($nodeTypeOne, $numberOfNodesTypeOne, 1);
    $this->createNodesOfType($nodeTypeTwo, $numberOfNodesTypeTwo, 1);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $nodeReport = $report['node'];

    $this->assertEquals($numberOfNodesTypeOne, $nodeReport['bundle'][$nodeTypeOne]['instances']);
    $this->assertEquals($numberOfNodesTypeTwo, $nodeReport['bundle'][$nodeTypeTwo]['instances']);

    $nodeOneFieldsReport = $nodeReport['bundle'][$nodeTypeOne]['fields'];
    $nodeTwoFieldsReport = $nodeReport['bundle'][$nodeTypeTwo]['fields'];

    $this->assertEquals('string', $nodeOneFieldsReport[$stringFieldNotRequiredTranslatable]['type']);
    $this->assertEquals(FALSE, $nodeOneFieldsReport[$stringFieldNotRequiredTranslatable]['required']);
    $this->assertEquals(TRUE, $nodeOneFieldsReport[$stringFieldNotRequiredTranslatable]['translatable']);
    $this->assertEquals(1, $nodeOneFieldsReport[$stringFieldNotRequiredTranslatable]['cardinality']);

    $this->assertEquals('string', $nodeOneFieldsReport[$stringFieldRequiredNotTranslatable]['type']);
    $this->assertEquals(TRUE, $nodeOneFieldsReport[$stringFieldRequiredNotTranslatable]['required']);
    $this->assertEquals(FALSE, $nodeOneFieldsReport[$stringFieldRequiredNotTranslatable]['translatable']);
    $this->assertEquals(1, $nodeOneFieldsReport[$stringFieldRequiredNotTranslatable]['cardinality']);

    $this->assertEquals(['type_one'], $nodeOneFieldsReport[$entityReferenceFieldSingleTarget]['target_bundles']);
    $this->assertEquals('entity_reference', $nodeOneFieldsReport[$entityReferenceFieldMultipleTargets]['type']);
    $this->assertEquals('node', $nodeOneFieldsReport[$entityReferenceFieldMultipleTargets]['target_type']);
    $this->assertEquals(['type_one', 'type_two'], $nodeOneFieldsReport[$entityReferenceFieldMultipleTargets]['target_bundles']);

    $this->assertEmpty($nodeTwoFieldsReport);
  }

  /**
   * Test sampling of media data.
   */
  public function testMediaDataSampling() {
    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $mediaReport = $report['media'];

    $this->assertEquals('test', $mediaReport['bundle']['test']['source']['plugin_id']);
  }

  /**
   * Test sampling of data for revision histograms.
   */
  public function testRevisionHistogramDataSampling() {
    $nodeTypeOne = 'type_one';

    $numberOfNodesWithOneRevisions = 3;
    $numberOfNodesWithTwoRevisions = 2;
    $numberOfNodesWithThreeRevisions = 1;

    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithOneRevisions, 1);
    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithTwoRevisions, 2);
    $this->createNodesOfType($nodeTypeOne, $numberOfNodesWithThreeRevisions, 3);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(TRUE)
      ->collect()
      ->getReport();

    $histogramReport = $report['node']['histogram'];

    $this->assertEquals($numberOfNodesWithOneRevisions, $histogramReport['revision'][1]);
    $this->assertEquals($numberOfNodesWithTwoRevisions, $histogramReport['revision'][2]);
    $this->assertEquals($numberOfNodesWithThreeRevisions, $histogramReport['revision'][3]);
  }

  /**
   * Test sampling of data for entity reference histograms.
   */
  public function testEntityReferenceHistogramDataSampling() {
    $nodeTypeOne = 'type_one';

    $nodes = $this->createNodesOfType($nodeTypeOne, 6);

    Node::create([
      'type' => $nodeTypeOne,
      'title' => $this->randomString(),
      'field_three' => [$nodes[3]->id()],
      'field_four' => [$nodes[1]->id(), $nodes[2]->id()],
    ])->save();

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $nodeReport = $report['node'];

    $this->assertEquals('entity_reference', $nodeReport['bundle'][$nodeTypeOne]['fields']['field_three']['type']);
    $this->assertEquals(1, $nodeReport['bundle'][$nodeTypeOne]['fields']['field_four']['histogram'][2]);
    $this->assertEquals(1, $nodeReport['bundle'][$nodeTypeOne]['fields']['field_three']['histogram'][1]);
  }

}
