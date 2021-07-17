<?php

namespace wesleydv\DrupalUpgradeAudit;

/**
 * Class Data.
 *
 * Simple class to store data.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Data {

  private $project;
  private $baseDir = '/tmp';
  private $result = [];
  private $dir;

  public function __construct(string $project) {
    $this->project = $project;
    $this->dir = sprintf('%s/%s', $this->baseDir, $project);
  }

  public function getProject(): string {
    return $this->project;
  }

  public function getDir(): string {
    return $this->dir;
  }

  public function addResult(string $str): void {
    $this->result[] = $str;
  }

  public function getResult(): array {
    return $this->result;
  }

  public function getBaseDir(): string {
    return $this->baseDir;
  }

}
