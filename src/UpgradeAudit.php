<?php

namespace wesleydv\DrupalUpgradeAudit;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpgradeAudit.
 *
 * Upgrade Audit.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class UpgradeAudit extends Command {

  protected $data;
  protected $git;
  protected $version;
  protected $complexity;
  protected $drupalCheck;
  protected $compatibility;

  public function __construct(Data $data, Git $git, Version $version, Complexity $complexity, DrupalCheck $drupalCheck, Compatibility $compatibility, string $name = NULL) {
    parent::__construct($name);

    $this->data = $data;
    $this->git = $git;
    $this->version = $version;
    $this->complexity = $complexity;
    $this->drupalCheck = $drupalCheck;
    $this->compatibility = $compatibility;
  }

  protected static $defaultName = 'upgrade-audit';

  protected function configure(): void {
    $this
      ->setDescription('Run upgrade audit.')
      ->setHelp('Check code for compatibility with Drupal 9.')
      ->addArgument('repo', InputArgument::REQUIRED , 'Code repository url')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $progressBar = new ProgressBar($output, 5);
    $progressBar->setFormat('%current%/%max% %message%');
    $progressBar->setMessage('Starting');
    $progressBar->start();

    $progressBar->setMessage('Getting the code');
    $progressBar->display();
    $this->data->setRepo($input->getArgument('repo'));
    $this->git->prepare();
    $progressBar->advance();

    $progressBar->setMessage('Looking up Drupal versions');
    $progressBar->display();
    $this->data->addResult('Original Drupal version: ' . $this->version->getInitialDrupalVersion());
    $progressBar->advance();

    $progressBar->setMessage('Assessing complexity');
    $progressBar->display();
    $this->data->addResult(sprintf('There are %s custom modules and %s lines of custom code', $this->complexity->getCustomModules(), $this->complexity->getCustomCodeLines()));
    $progressBar->advance();

    // Check compatibility modules
    $progressBar->setMessage('Checking compatibility, this might take a while');
    $progressBar->display();
    $this->compatibility->run();
    $this->data->addResult($this->compatibility->getSummary());
    $progressBar->advance();

    // Run drupal-check
    $progressBar->setMessage("Looking for deprecated code");
    $this->data->addResult($this->drupalCheck->runDeprecated());
    $progressBar->finish();

    $line = str_pad('', 80, '#');
    $output->writeln(['', '', $line, '']);
    $output->writeln($this->data->getResult());
    $output->writeln(['', $line, '']);

    return Command::SUCCESS;
  }

}