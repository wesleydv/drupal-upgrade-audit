<?php

namespace wesleydv\DrupalUpgradeAudit;

/**
 * Class Complexity.
 *
 * Estimate site complexity versions.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Complexity {

  /**
   * Get the number of custom modules.
   *
   * @return int
   *   Number of custom modules.
   */
  public static function getCustomModules() {
    // ToDo: Don't include docroot.
    return (int) trim(`find docroot/modules/custom -type f -name *.info.yml | wc -l`);
  }

  /**
   * Get the number of custom code lines.
   *
   * @return int
   *   Number of custom code lines.
   */
  public static function getCustomCodeLines() {
    // ToDo: Don't include docroot.
    return (int) trim(`find docroot/modules/custom -type f \( -name "*.php" -o -name "*.module" \) -print0 | xargs -0 cat | wc -l`);
  }

}
