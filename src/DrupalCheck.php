<?php

namespace wesleydv\DrupalUpgradeAudit;

use Nette\Neon\Neon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class DrupalCheck.
 *
 * Check custom code for deprecated code.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class DrupalCheck {

  private $data;

  /**
   * DrupalCheck constructor.
   */
  public function __construct(Data $data) {
    $this->data = $data;
  }

  /**
   * Run drupal-check and check for deprecated code.
   *
   * @return string
   *   A summary of drupal-check
   */
  public function runDeprecated(InputInterface $input, OutputInterface $output) {
    $this->call($input, $output);
  }

  /**
   * Call Drupal check.
   * 
   * Copied from drupal-check/Command/CheckCommand.php.
   * ToDo: needs some cleanup.
   */
  private function call(InputInterface $input, OutputInterface $output) {
    $drupalFinder = $this->data->getDrupalFinder();
    $drupalRoot = realpath($drupalFinder->getDrupalRoot());
    $vendorRoot = realpath($drupalFinder->getVendorDir());

    $output->writeln(sprintf('<comment>Current working directory: %s</comment>', getcwd()), OutputInterface::VERBOSITY_DEBUG);
    $output->writeln(sprintf('<info>Using Drupal root: %s</info>', $drupalRoot), OutputInterface::VERBOSITY_DEBUG);
    $output->writeln(sprintf('<info>Using vendor root: %s</info>', $vendorRoot), OutputInterface::VERBOSITY_DEBUG);
    if (!is_file($vendorRoot . '/autoload.php')) {
      throw new \RuntimeException('Could not find autoload file');
    }

    $configuration_data = [
      'parameters' => [
        'tipsOfTheDay' => false,
        'reportUnmatchedIgnoredErrors' => false,
        'excludePaths' => [
          '*/tests/Drupal/Tests/Listeners/Legacy/*',
          '*/tests/fixtures/*.php',
          '*/settings*.php',
          '*/node_modules/*'
        ],
        'ignoreErrors' => [],
        'drupal' => [
          'drupal_root' => $drupalRoot,
        ],
        'customRulesetUsed' => true,
      ]
    ];

    $ignored_deprecation_errors = [
      '#\Drupal calls should be avoided in classes, use dependency injection instead#',
      '#Plugin definitions cannot be altered.#',
      '#Missing cache backend declaration for performance.#',
      '#Plugin manager has cache backend specified but does not declare cache tags.#'
    ];
    $configuration_data['parameters']['ignoreErrors'] = array_merge($ignored_deprecation_errors, $configuration_data['parameters']['ignoreErrors']);

    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
      // Running as a project dependency.
      $output->writeln('<comment>Assumed running as local dependency</comment>', OutputInterface::VERBOSITY_DEBUG);
      $phpstanBin = \realpath(__DIR__ . '/../vendor/phpstan/phpstan/phpstan.phar');
      $configuration_data['parameters']['bootstrapFiles'] = [\realpath(__DIR__ . '/../vendor/mglaman/drupal-check/error-bootstrap.php')];
      $configuration_data['includes'] = [
        \realpath(__DIR__ . '/../vendor/phpstan/phpstan-deprecation-rules/rules.neon'),
        \realpath(__DIR__ . '/../vendor/mglaman/phpstan-drupal/extension.neon'),
      ];
    } else {
      throw new \RuntimeException('Could not determine if local or global installation');
    }

    if (!file_exists($phpstanBin)) {
      throw new \RuntimeException(sprintf('Could not find PHPStan at  %s', $phpstanBin));
    }

    $output->writeln(sprintf('<comment>PHPStan path: %s</comment>', $phpstanBin), OutputInterface::VERBOSITY_DEBUG);
    $configuration_encoded = Neon::encode($configuration_data, Neon::BLOCK);
    $configuration = sys_get_temp_dir() . '/drupal_check_phpstan_' . time() . '.neon';
    file_put_contents($configuration, $configuration_encoded);
    $configuration = realpath($configuration);
    $output->writeln(sprintf('<comment>PHPStan configuration path: %s</comment>', $configuration), OutputInterface::VERBOSITY_DEBUG);

    $output->writeln('<comment>PHPStan configuration:</comment>', OutputInterface::VERBOSITY_DEBUG);
    $output->writeln($configuration_encoded, OutputInterface::VERBOSITY_DEBUG);

    $command = [
      $phpstanBin,
      'analyse',
      '-c',
      $configuration,
      '--error-format=table'
    ];

    if (substr(PHP_OS, 0, 3) == 'WIN') {
      array_unshift($command, 'php');
    }

    if ($output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE) {
      $command[] = '-v';
    } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_VERY_VERBOSE) {
      $command[] = '-vv';
    } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
      $command[] = '-vvv';
    }

    // Add path to analyze to command.
    $command[] = $this->data->getCustomFolder();
    $process = new Process($command);
    $process->setTimeout(null);

    $output->writeln('<comment>Executing PHPStan</comment>', OutputInterface::VERBOSITY_DEBUG);
    $process->run(static function ($type, $buffer) use ($output) {
      $output->write($buffer, false, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_DEBUG);
    });
    $output->writeln('<comment>Finished executing PHPStan</comment>', OutputInterface::VERBOSITY_DEBUG);
    $output->writeln('<comment>Unlinking PHPStan configuration</comment>', OutputInterface::VERBOSITY_DEBUG);
    unlink($configuration);

    $check_results_file = sprintf('%s-check.txt', $this->data->getDir());
    $output->writeln(sprintf('<comment>Writing PHPStan results to %s</comment>', $check_results_file), OutputInterface::VERBOSITY_VERBOSE);
    $phpstan_output = $process->getOutput();
    file_put_contents($check_results_file, $phpstan_output);

    $output->writeln('<comment>Analyzing PHPStan results</comment>', OutputInterface::VERBOSITY_DEBUG);
    if (preg_match('/\[OK\]/', $phpstan_output)) {
      $this->data->addResult(sprintf('Drupal check: Found no errors, make sure you check %s for details', $check_results_file));
      return;
    }

    if (preg_match('/Found (\d+) error/', $phpstan_output, $num_errors)) {
      $this->data->addResult(sprintf('Drupal check: Found %s errors, check %s for details', $num_errors[1], $check_results_file));
      return;
    }

    throw new \RuntimeException('Unable to analyse PHPStan output');
  }

}
