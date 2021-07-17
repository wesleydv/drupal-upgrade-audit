<?php

namespace wesleydv\DrupalUpgradeAudit;

use CzProject\GitPhp\Git as GitPhp;
use CzProject\GitPhp\IRunner;

/**
 * Class Git.
 *
 * Clone code to tmp folder.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Git extends GitPhp {

  private $data;

  public function __construct(Data $data, IRunner $runner = NULL) {
    parent::__construct($runner);
    $this->data = $data;
    $this->prepare();
  }

  /**
   * Prepare Git repo.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  private function prepare(): void {
    $dir = $this->data->getDir();

    if (is_dir($dir)) {
      $repo = $this->open($dir);
      $repo->fetch();
      $repo->checkout('master');
    }
    else {
      $this->cloneRepository($this->data->getRepo(), $dir);
    }

    chdir($dir);
  }



}
