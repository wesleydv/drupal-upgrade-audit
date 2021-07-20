# drupal-upgrade-audit

A static analysis tool to give you state of both contrib and custom modules
- Custom modules are tested using [drupal-check](https://github.com/mglaman/drupal-check)
- Contrib modules are checked using the info.yml file and the release history on drupal.org

Is this not the same as the [Upgrade Status](https://www.drupal.org/project/upgrade_status) module? No Upgrade Status
has some extra features but it requires you to install on a site.

## Sponsors

<a href="https://dropsolid.com/"><img src="https://www.drupal.org/files/Dropsolid-logo-DEC-horizontal-color.png" alt="Dropsolid" width="250" /></a>

## Requirements

* PHP >=7.4

## Installation

You can also install this globally using Composer like so:

```
composer global require wesleydv/drupal-upgrade-audit
```

## Usage

  ```
  drupal-upgrade-audit [GIT_REPO]
  ```

## Caveats

- This code is quite opinionated and could also use some clean up.
