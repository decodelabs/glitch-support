# Glitch Support

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/glitch-support?style=flat)](https://packagist.org/packages/decodelabs/glitch-support)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/glitch-support.svg?style=flat)](https://packagist.org/packages/decodelabs/glitch-support)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/glitch-support.svg?style=flat)](https://packagist.org/packages/decodelabs/glitch-support)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/glitch-support/integrate.yml?branch=develop)](https://github.com/decodelabs/glitch-support/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/glitch-support?style=flat)](https://packagist.org/packages/decodelabs/glitch-support)


### Middleware support for Glitch

This repository contains shared support classes and interfaces for libraries wishing to support Glitch functionality.

_Get news and updates on the [DecodeLabs blog](https://blog.decodelabs.com)._

---

## Usage

Make use of the methods within `DecodeLabs\Glitch\Proxy` within your own libraries without the need to depend on the full Glitch library.

`Proxy` only provides a small subset of Glitch functionality and the majority of it only really does anything if the consumer of _your_ library includes Glitch in their project; however by making use of the Proxy, you can provide much better debug support for your library should they choose to do so.

Please see [DecodeLabs Glitch](https://github.com/decodelabs/glitch) for more.

## Licensing
Glitch is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
