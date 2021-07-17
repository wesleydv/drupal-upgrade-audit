<?php

namespace wesleydv\DrupalUpgradeAudit;

/**
 * Class DrupalCheck.
 *
 * Check for module compatibility.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class DrupalCheck {

  /**
   * DrupalCheck constructor.
   */
  public function __construct() {
    $this->commandAvailable();
  }

  /**
   * Run drupal-check and check for deprecated code.
   *
   * Todo: Remove dir argument.
   *
   * @return string
   *   A summary of drupal-check
   */
  public function runDeprecated($dir): string {
    $check_results_file = $dir . '-check.txt';
    `drupal-check docroot/modules/custom &> $check_results_file`;
    $check_results = file_get_contents($check_results_file);
    if (preg_match('/\[OK\]/', $check_results)) {
      return sprintf('Drupal check: Found no errors, make sure you check %s for details', $check_results_file);
    }
    if (preg_match('/Found (\d+) error/', $check_results, $num_errors)) {
      return sprintf('Drupal check: Found %s errors, check %s for details', $num_errors[1], $check_results_file);
    }
    throw new \RuntimeException('Unable to read drupal-check results');
  }

  /**
   * Check if drupal-check is available.
   *
   * ToDo: Find a way do this better, simply requiring the module and running it
   * from vendor/bin results in dependency errors.
   */
  private function commandAvailable() {
    if (!`which drupal-check`) {
      throw new \RuntimeException('drupal-check command not found, please install it globally https://github.com/mglaman/drupal-check#installation');
    }
  }

}
