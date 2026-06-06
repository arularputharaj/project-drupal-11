# Config Distro

Config Distro is a developer framework that provides an event-driven
architecture for managing configuration updates from distributions. It
requires integration with companion modules like
[Configuration Synchronizer](https://www.drupal.org/project/config_sync)
(`config_sync`) to provide end-user functionality.

Built on the same architecture as
[Configuration Split](https://www.drupal.org/project/config_split)
(`config_split`), it
provides the infrastructure (events, storage management, UI forms, and Drush
commands) that other modules can use to discover and apply extension
configuration updates.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/config_distro).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/config_distro).


## Table of contents

- Requirements
- Installation
- Configuration
- Submodules
- Usage
- Maintainers


## Requirements

This module requires the following modules:

[Config Filter](https://www.drupal.org/project/config_filter) (`config_filter`)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

- Install Config Distro.
- Install one or more modules that provide Config Filter plugins that
  integrate with this module, such as
  [Configuration Synchronizer](https://www.drupal.org/project/config_sync)
  (`config_sync`).
- Update your installed modules or themes to a new versions that include
  configuration changes or updates. Run database updates and clear caches.
- If using the UI, navigate to Configuration > Development > Distribution Update
  (`/admin/config/development/distro`). Review and run updates.
- If using Drush, use the Drush command config-distro-update to run imports.


## Submodules

Config Distro includes the following submodules:

### Config Distro Filter (`config_distro_filter`) - Deprecated

A bridge module between [Config Filter](https://www.drupal.org/project/config_filter)
(`config_filter`) and Config Distro's transform API. This module is deprecated
and maintained only for backwards compatibility. See the
[deprecation issue](https://www.drupal.org/project/config_distro/issues/3466112)
for more information.

### Config Distro Ignore (`config_distro_ignore`)

Provides functionality to ignore specific configuration from being imported
via Config Distro. Useful for excluding site-specific configuration that
should not be updated from extensions.


## Usage

The `config_distro` module provides a framework (event system, form, and
Drush commands) for managing configuration updates from extensions
(modules, themes, or install profiles). It requires a compatible module
like [Configuration Synchronizer](https://www.drupal.org/project/config_sync)
(`config_sync`) to discover extension configuration and provide update
functionality.

For details on usage, update modes, and merge strategies, see the
[Configuration Synchronizer documentation](https://www.drupal.org/project/config_sync).

## Maintainers

- Antonio De Marco - [ademarco](https://www.drupal.org/u/ademarco)
- Fabian Bircher - [bircher](https://www.drupal.org/u/bircher)
- Joe Parsons - [joegraduate](https://www.drupal.org/u/joegraduate)
- Andrea Pescetti - [pescetti](https://www.drupal.org/u/pescetti)
- Pieter Frenssen - [pfrenssen](https://www.drupal.org/u/pfrenssen)
