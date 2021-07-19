<?php

namespace wesleydv\DrupalUpgradeAudit;

use Nette\Utils\Finder;

/**
 * Class Complexity.
 *
 * Estimate site complexity versions.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Complexity {

  private $data;

  public function __construct(Data $data) {
    $this->data = $data;
  }

  /**
   * Get the number of custom modules.
   *
   * @return int
   *   Number of custom modules.
   */
  public function getCustomModules() {
    return Finder::findFiles('**modules/custom/**.info.yml')->from($this->data->getDir())->count();
  }

  /**
   * Get the number of custom code lines.
   *
   * @return int
   *   Number of custom code lines.
   */
  public function getCustomCodeLines() {
    $search = [
      '**modules/custom/**.php',
      '**modules/custom/**.module',
    ];

    $lines = 0;
    foreach (Finder::findFiles($search)->from($this->data->getDir()) as $fileInfo) {
      $file = $fileInfo->openFile();
      $file->seek($file->getSize());
      $lines += $file->key();
    } 

    return $lines;
  }

}
