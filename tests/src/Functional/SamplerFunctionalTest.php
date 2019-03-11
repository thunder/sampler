<?php

namespace Drupal\Tests\sampler\Functional;

/**
 * Tests the sampler module.
 *
 * @group sampler
 */
class SamplerFunctionalTest extends SamplerFunctionalTestBase {

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

    $this->assertEquals(17, $userReport['base_fields']);

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

    $this->createNodesOfType($nodeTypeOne, $numberOfNodesTypeOne, 1);
    $this->createNodesOfType($nodeTypeTwo, $numberOfNodesTypeTwo, 1);

    $report = $this->container->get('sampler.reporter')
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $nodeReport = $report['node'];

    $this->assertEquals(18, $nodeReport['base_fields']);
    $this->assertEquals($numberOfNodesTypeOne, $nodeReport['bundle'][$nodeTypeOne]['instances']);
    $this->assertEquals($numberOfNodesTypeTwo, $nodeReport['bundle'][$nodeTypeTwo]['instances']);

    $this->assertEquals(2, $nodeReport['bundle'][$nodeTypeOne]['fields']);
    $this->assertEquals(0, $nodeReport['bundle'][$nodeTypeTwo]['fields']);
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
      ->anonymize(FALSE)
      ->collect()
      ->getReport();

    $histogramReport = $report['node']['histogram'];

    $this->assertEquals($numberOfNodesWithOneRevisions, $histogramReport['revision'][1]);
    $this->assertEquals($numberOfNodesWithTwoRevisions, $histogramReport['revision'][2]);
    $this->assertEquals($numberOfNodesWithThreeRevisions, $histogramReport['revision'][3]);
  }

}
