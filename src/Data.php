<?php

namespace wesleydv\DrupalUpgradeAudit;

use CzProject\GitPhp\Helpers;
use DrupalFinder\DrupalFinder;

/**
 * Class Data.
 *
 * Simple class to store data.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Data {

  private $repo;
  private $project;
  private $baseDir = '/tmp/drupal-upgrade-audit';
  private $result = [];
  private $dir;
  private $drupalFinder;

  public function setRepo(string $repo) {
    $this->repo = $repo;
    $this->project = Helpers::extractRepositoryNameFromUrl($repo);
    $this->dir = sprintf('%s/%s', $this->baseDir, $this->project);
  }

  public function getRepo(): string {
    return $this->repo;
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

  public function getDrupalFinder(): DrupalFinder {
    if (empty($this->drupalFinder)) {
      $this->drupalFinder = new DrupalFinder();

      if (!$this->drupalFinder->locateRoot($this->dir)) {
        throw new \RuntimeException(sprintf('Unable to locate the Drupal root in %s', $this->dir));
      }
    }
    return $this->drupalFinder;
  }

  public function getCustomFolder(): string {
    return sprintf('%s/modules/custom', $this->getDrupalFinder()->getDrupalRoot());
  }

}
