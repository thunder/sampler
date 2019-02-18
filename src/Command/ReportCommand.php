<?php

namespace Drupal\sampler\Command;

use Drupal\sampler\Reporter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
// phpcs:disable
use Drupal\Console\Annotations\DrupalCommand;
// phpcs:enable

/**
 * Class ReportCommand.
 *
 * @DrupalCommand (
 *     extension="sampler",
 *     extensionType="module"
 * )
 */
class ReportCommand extends ContainerAwareCommand {

  /**
   * The reporter instance.
   *
   * @var \Drupal\sampler\Reporter
   */
  protected $reporter;

  /**
   * Constructs a new ReportCommand object.
   */
  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sampler:report')
      ->addArgument('filename', InputArgument::OPTIONAL, $this->trans('commands.sampler.report.arguments.filename'))
      ->setDescription($this->trans('commands.sampler.report.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $filename = $input->getArgument('filename');
    $this->reporter
      ->collect()
      ->output($filename);
  }

}
