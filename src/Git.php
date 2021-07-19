<?php

namespace wesleydv\DrupalUpgradeAudit;

use CzProject\GitPhp\Git as GitPhp;
use CzProject\GitPhp\GitRepository;
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
  private $repo;

  public function __construct(Data $data, IRunner $runner = NULL) {
    parent::__construct($runner);
    $this->data = $data;
  }

  /**
   * Prepare Git repo.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function prepare(): void {
    $dir = $this->data->getDir();

    if (is_dir($dir)) {
      $this->repo = $this->open($dir);
      $this->repo->fetch();
      $this->repo->checkout('master');
    }
    else {
      $this->repo = $this->cloneRepository($this->data->getRepo(), $dir);
    }
  }

  public function getRepo(): GitRepository {
    return $this->repo;
  }

}
