<?php

namespace wesleydv\DrupalUpgradeAudit;

use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Compatibility.
 *
 * Check for module compatibility.
 *
 * @package wesleydv\DrupalUpgradeAudit
 */
class Compatibility {

  const CORE = 'core';
  const SUBMODULE = 'submodule';
  const CUSTOM = 'custom';
  const COMPATIBLE = 'compatible';
  const COMPATIBLE_AFTER_UPGRADE = 'compatible_after_upgrade';
  const NOT_COMPATIBLE = 'not_compatible';
  const NOT_FOUND = 'not_found';

  private $results;
  private $enabledModules;
  private $client;

  /**
   * Compatibility constructor.
   *
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(Client $client) {
    $this->results = [
      self::CORE => [],
      self::SUBMODULE => [],
      self::CUSTOM => [],
      self::COMPATIBLE => [],
      self::COMPATIBLE_AFTER_UPGRADE => [],
      self::NOT_COMPATIBLE => [],
      self::NOT_FOUND => [],
    ];
    $this->client = $client;
  }

  /**
   * Run compatibility check.
   *
   * @return array
   *   Compatibility results.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function run(): array {
    $this->enabledModules = $this->getEnabledModules();
    foreach ($this->enabledModules as $module) {
      $status = $this->checkModule($module);
      $this->results[$status][] = $module;
    }

    return $this->results;
  }

  /**
   * Get result summary.
   *
   * @return string
   *   Result summary.
   */
  public function getSummary(): string {
    $summary = [];

    $summary[] = sprintf('There are %s enabled modules, %s are submodules: ', count($this->enabledModules), count($this->results['submodule']));
    $summary[] = sprintf(' - %s are part of core', count($this->results['core']));
    $summary[] = sprintf(' - %s are custom', count($this->results['custom']));
    $summary[] = sprintf(' - %s are compatabile with D9', count($this->results['compatible']));
    $summary[] = sprintf(' - %s are compatabile with D9 after upgrading', count($this->results['compatible_after_upgrade']));
    $summary[] = sprintf(' - %s are not compatabile with D9', count($this->results['not_compatible']));

    return implode(PHP_EOL, $summary);
  }

  /**
   * Get enabled modules.
   *
   * @return array
   *   List of enabled modules.
   */
  private function getEnabledModules(): array {
    $core_extensions_path = trim(`find . -name "core.extension.yml" -print -quit`);
    $core_extensions = Yaml::parseFile($core_extensions_path);
    if (!isset($core_extensions['module'])) {
      throw new \RuntimeException('Could not determine enabled modules');
    }
    return array_keys($core_extensions['module']);
  }

  /**
   * Check compatibility of individual module.
   *
   * @param string $module
   *   Module to check.
   *
   * @return string
   *   Compatibility status.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function checkModule(string $module): string {
    $info = $this->getModuleInfo($module);

    if (isset($info['project']) && $info['project'] !== $module) {
      return self::SUBMODULE;
    }

    if (preg_match('/core\/modules/', $info['path'])) {
      return self::CORE;
    }

    if (isset($info['core_version_requirement']) && $this->checkCoreVersionString($info['core_version_requirement'])) {
      return self::COMPATIBLE;
    }

    if (preg_match('/modules\/custom/', $info['path'])) {
      return self::CUSTOM;
    }

    return $this->checkCompatibilityOnDrupalOrg($module);
  }

  /**
   * Get module info.
   *
   * @param string $module
   *   Module to check.
   *
   * @return array
   *   Module info.
   */
  private function getModuleInfo(string $module): array {
    // ToDo: This is slow
    $yml_path = trim(`find . -name "$module.info.yml" -print -quit`);
    $info = Yaml::parseFile($yml_path);
    $info['path'] = $yml_path;
    return $info;
  }

  /**
   * Check is core version string includes Drupal 9.
   *
   * @param string $str
   *   Core version string.
   *
   * @return bool
   *   True if Drupal 9 is included.
   */
  private function checkCoreVersionString(string $str): bool {
    return (bool) preg_match('/\^9/', $str);
  }

  /**
   * Check compatibility for the module on  Drupal.org.
   *
   * @param string $module
   *   Module to check.
   *
   * @return string
   *   Compatibility status.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function checkCompatibilityOnDrupalOrg(string $module): string {
    $url = sprintf('https://updates.drupal.org/release-history/%s/current', $module);

    $response = $this->client->request('GET', $url);

    $data = new \SimpleXMLElement($response->getBody());
    if (!isset($data->title)) {
      return self::NOT_FOUND;
    }

    foreach ($data->releases->children() as $release) {
      if ($this->checkCoreVersionString($release->core_compatibility)) {
        return self::COMPATIBLE_AFTER_UPGRADE;
      }
    }

    return self::NOT_COMPATIBLE;
  }

}
