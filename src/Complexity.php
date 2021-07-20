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
  private $customFolder;

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
    return Finder::findFiles('**.info.yml')->from($this->data->getCustomFolder())->count();
  }

  /**
   * Get the number of custom code lines.
   *
   * @return int
   *   Number of custom code lines.
   */
  public function getCustomCodeLines() {
    $lines = 0;
    foreach (Finder::findFiles('**.php', '**.module')->from($this->data->getCustomFolder()) as $fileInfo) {
      $file = $fileInfo->openFile();
      $file->seek($file->getSize());
      $lines += $file->key();
    } 

    return $lines;
  }

}
