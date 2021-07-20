<?php

namespace wesleydv\DrupalUpgradeAudit;

/**
 * Class Version.
 *
 * Extract core versions.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Version {

  private $git;

  public function __construct(Git $git) {
    $this->git = $git;
  }

  /**
   * Get initial Drupal version from Git history.
   *
   * @return string
   *   Initial Drupal version
   */
  public function getInitialDrupalVersion(): string {
    $content = $this->getFirstDrupalPhp();
    return $this->extractVersion($content);
  }

  /**
   * Get content of the first lib/Drupal.php that was added.
   *
   * @return string
   *   Content of lib/Drupal.php.
   */
  private function getFirstDrupalPhp(): string {
    $repo = $this->git->getRepo();

    // Find commit where lib/Drupal.php is added the first time.
    $rev_results = $repo->execute('log' , '--format=%H',  '--diff-filter=A', '--', '*lib/Drupal.php');
    $revision = reset($rev_results);
    $ls_tree = $repo->execute('ls-tree', '--name-only', '-r', $revision);

    // Find the full path to lib/Drupal.php;
    $results = preg_grep('/.*lib\/Drupal\.php$/', $ls_tree);
    if (empty($results)) {
      throw new \RuntimeException('Could not find original lib/Drupal.php file');
    }
    $drupal_php_path = reset($results);

    // Return the content of lib/Drupal.php at that initial commit.
    return implode(PHP_EOL, $repo->execute('show', sprintf('%s:%s', $revision, $drupal_php_path)));
  }

  /**
   * Extract Drupal version from lib/Drupal.php content.
   *
   * @param string $content
   *   Content of lib/Drupal.php.
   *
   * @return string
   *   Drupal version.
   */
  private function extractVersion(string $content): string {
    if (!preg_match('/const VERSION = \'([0-9\.]+)\'/', $content, $original_drupal_version)) {
      throw new \RuntimeException('Could not find version in lib/Drupal.php file');
    }

    return $original_drupal_version[1];
  }

}
