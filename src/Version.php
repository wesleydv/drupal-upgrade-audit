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

  /**
   * Get initial Drupal version from Git history.
   *
   * @return string
   *   Initial Drupal version
   */
  public static function getInitialDrupalVersion(): string {
    $content = self::getFirstDrupalPhp();
    return self::extractVersion($content);
  }

  /**
   * Get content of the first lib/Drupal.php that was added.
   *
   * @return string
   *   Content of lib/Drupal.php.
   */
  private static function getFirstDrupalPhp(): string {
    $revision = trim(`git log --format=%H --diff-filter=A -- *lib/Drupal.php | head -n 1`);
    $ls_tree = `git ls-tree --name-only -r $revision`;
    if (!preg_match('/.*lib\/Drupal\.php$/m', $ls_tree, $drupal_php_path)) {
      throw new \RuntimeException('Could not find original lib/Drupal.php file');
    }
    $drupal_php_path = $drupal_php_path[0];
    return `git show $revision:$drupal_php_path`;
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
  private static function extractVersion(string $content): string {
    if (!preg_match('/const VERSION = \'([0-9\.]+)\'/', $content, $original_drupal_version)) {
      throw new \RuntimeException('Could not find version in lib/Drupal.php file');
    }

    return $original_drupal_version[1];
  }

}
