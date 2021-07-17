<?php

namespace wesleydv\DrupalUpgradeAudit;

/**
 * Class FetchCode.
 *
 * Clone code to tmp folder.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class FetchCode {

  private $data;

  public function __construct(Data $data) {
    $this->data = $data;
  }

  public function run() {
    if (is_dir($this->data->getDir())) {
      $this->fetch();
    }
    else {
      $this->clone();
    }
  }

  private function fetch() {
    // pl("# You already have the code, fancy, fetching and checking out master");
    chdir($this->data->getDir());
    `git fetch --quiet`;
    `git checkout --quiet master`;
  }

  private function clone() {
    // pl("# Cloning the code, you know like Dolly");
    chdir($this->data->getBaseDir());
    $git = sprintf('git@git.dropsolid.com:project/%s.git', $this->data->getProject());
    `git clone --quiet $git`;
    chdir($this->data->getDir());
  }

}