# Changelog

## [5.1.0](https://github.com/Flagsmith/flagsmith-php-client/compare/v5.0.0...v5.1.0) (2026-01-23)


### Features

* Support for PHP 8.5 ([#128](https://github.com/Flagsmith/flagsmith-php-client/issues/128)) ([241e9c5](https://github.com/Flagsmith/flagsmith-php-client/commit/241e9c5d58586cfbd4c71d5b1b9c34228b818e59))

## [5.0.0](https://github.com/Flagsmith/flagsmith-php-client/compare/v4.5.1...v5.0.0) (2025-12-04)


### ⚠ BREAKING CHANGES

* **Engine:** Use context-based evaluation engine ([#126](https://github.com/Flagsmith/flagsmith-php-client/issues/126))

### Features

* **Engine:** Evaluate from EvaluationContext ([#108](https://github.com/Flagsmith/flagsmith-php-client/issues/108)) ([4cb3481](https://github.com/Flagsmith/flagsmith-php-client/commit/4cb34819912d132c0b252e2369ab8c04fe5e245f))
* **Engine:** Use context-based evaluation engine ([#126](https://github.com/Flagsmith/flagsmith-php-client/issues/126)) ([91b0a3b](https://github.com/Flagsmith/flagsmith-php-client/commit/91b0a3b74495247f7aec5baa3fee2ffd0c825624))
* get-version-from-composer-and-pass-it-in-headers ([#112](https://github.com/Flagsmith/flagsmith-php-client/issues/112)) ([ae35794](https://github.com/Flagsmith/flagsmith-php-client/commit/ae35794e1df4b2f5b490727e7cbbeb033f6167be))
* **Local evaluation:** Use the new context-based engine ([#109](https://github.com/Flagsmith/flagsmith-php-client/issues/109)) ([66435a6](https://github.com/Flagsmith/flagsmith-php-client/commit/66435a6234f90cb5c0c816bf94ef57347ab23424))


### Bug Fixes

* **Engine:** Evaluate multivariates from segment overrides ([#120](https://github.com/Flagsmith/flagsmith-php-client/issues/120)) ([88fcf5f](https://github.com/Flagsmith/flagsmith-php-client/commit/88fcf5f35f17883b46848331c39d5d74c9a91113))
* Implicit identity key not supported for % Split operator ([#115](https://github.com/Flagsmith/flagsmith-php-client/issues/115)) ([a9d1e23](https://github.com/Flagsmith/flagsmith-php-client/commit/a9d1e23abac97fe50798aef276c51fd6c0c803b6))


### Docs

* removing hero image from SDK readme ([#95](https://github.com/Flagsmith/flagsmith-php-client/issues/95)) ([de30e67](https://github.com/Flagsmith/flagsmith-php-client/commit/de30e67bae9f8799eceafcd0c02ee6ce1e370b27))


### Refactoring

* **Engine:** nits from context work review ([#117](https://github.com/Flagsmith/flagsmith-php-client/issues/117)) ([1eaa8e4](https://github.com/Flagsmith/flagsmith-php-client/commit/1eaa8e4ff7e942dc3147b52b66e88d8e6f8552d9))


### Other

* add root CODEOWNERS ([#106](https://github.com/Flagsmith/flagsmith-php-client/issues/106)) ([28a1063](https://github.com/Flagsmith/flagsmith-php-client/commit/28a10639a6db2056a37fda602b9d44130e66dffe))
* versioned test data ([#97](https://github.com/Flagsmith/flagsmith-php-client/issues/97)) ([ce56815](https://github.com/Flagsmith/flagsmith-php-client/commit/ce5681527304c81089019809bc8905a686e3f92a))

<a id="v4.5.1"></a>
## [v4.5.1](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.5.1) - 2025-03-31

## What's Changed
* ci: update actions versions by [@matthewelwell](https://github.com/matthewelwell) in [#91](https://github.com/Flagsmith/flagsmith-php-client/pull/91)
* fix: Fix generateIdentitiesCacheKey by [@fgiova](https://github.com/fgiova) in [#90](https://github.com/Flagsmith/flagsmith-php-client/pull/90)
* Fix nullable parameter by [@VincentLanglet](https://github.com/VincentLanglet) in [#88](https://github.com/Flagsmith/flagsmith-php-client/pull/88)

## New Contributors
* [@fgiova](https://github.com/fgiova) made their first contribution in [#90](https://github.com/Flagsmith/flagsmith-php-client/pull/90)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.5.0...v4.5.1

[Changes][v4.5.1]


<a id="v4.5.0"></a>
## [v4.5.0](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.5.0) - 2024-10-02

## What's Changed
* Add ext- requirements to composer.json by [@misakstvanu](https://github.com/misakstvanu) in [#76](https://github.com/Flagsmith/flagsmith-php-client/pull/76)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.4.0...v4.5.0

[Changes][v4.5.0]


<a id="v4.4.0"></a>
## [v4.4.0](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.4.0) - 2024-08-28

## What's Changed
* feat: Support transient identities and traits by [@khvn26](https://github.com/khvn26) in [#78](https://github.com/Flagsmith/flagsmith-php-client/pull/78)
* ci: stop testing against <8.1 by [@matthewelwell](https://github.com/matthewelwell) in [#80](https://github.com/Flagsmith/flagsmith-php-client/pull/80)
* ci: remove pr filters by [@matthewelwell](https://github.com/matthewelwell) in [#81](https://github.com/Flagsmith/flagsmith-php-client/pull/81)
* Flagsmith instance clonning by [@misakstvanu](https://github.com/misakstvanu) in [#75](https://github.com/Flagsmith/flagsmith-php-client/pull/75)
* feat: offline mode by [@matthewelwell](https://github.com/matthewelwell) in [#79](https://github.com/Flagsmith/flagsmith-php-client/pull/79)
* deps: drop support for php<8.1 by [@matthewelwell](https://github.com/matthewelwell) in [#82](https://github.com/Flagsmith/flagsmith-php-client/pull/82)

## New Contributors
* [@misakstvanu](https://github.com/misakstvanu) made their first contribution in [#75](https://github.com/Flagsmith/flagsmith-php-client/pull/75)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.3.1...v4.4.0

[Changes][v4.4.0]


<a id="v4.3.1"></a>
## [v4.3.1](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.3.1) - 2024-05-24

## What's Changed
* Regex segment local evaluation with int trait by [@floranpagliai](https://github.com/floranpagliai) in [#72](https://github.com/Flagsmith/flagsmith-php-client/pull/72)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.3.0...v4.3.1

[Changes][v4.3.1]


<a id="v4.3.0"></a>
## [v4.3.0](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.3.0) - 2024-04-19

## What's Changed
* feat: Identity overrides in local evaluation mode by [@khvn26](https://github.com/khvn26) in [#69](https://github.com/Flagsmith/flagsmith-php-client/pull/69)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.2.1...v4.3.0

[Changes][v4.3.0]


<a id="v4.2.1"></a>
## [v4.2.1](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.2.1) - 2024-03-19

## What's Changed
* fix(identity-cache-key): Use hashed identifier by [@gagantrivedi](https://github.com/gagantrivedi) in [#68](https://github.com/Flagsmith/flagsmith-php-client/pull/68)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.2.0...v4.2.1

[Changes][v4.2.1]


<a id="v4.2.0"></a>
## [v4.2.0](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.2.0) - 2024-03-15

## What's Changed
* Fix method getEnvironment return type by [@tm1000](https://github.com/tm1000) in [#63](https://github.com/Flagsmith/flagsmith-php-client/pull/63)
* (feat): Add back withUseCacheAsFailover by [@tm1000](https://github.com/tm1000) in [#61](https://github.com/Flagsmith/flagsmith-php-client/pull/61)
* chore: remove examples by [@dabeeeenster](https://github.com/dabeeeenster) in [#65](https://github.com/Flagsmith/flagsmith-php-client/pull/65)
* Change cache key for getIdentityFlagsFromApi by [@tm1000](https://github.com/tm1000) in [#62](https://github.com/Flagsmith/flagsmith-php-client/pull/62)
* build: release 4.2.0 by [@dabeeeenster](https://github.com/dabeeeenster) in [#66](https://github.com/Flagsmith/flagsmith-php-client/pull/66)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.2...v4.2.0

[Changes][v4.2.0]


<a id="v4.1.2"></a>
## [v4.1.2](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.1.2) - 2023-11-07

## What's Changed
* Fix invalid cache value by [@VincentLanglet](https://github.com/VincentLanglet) in [#58](https://github.com/Flagsmith/flagsmith-php-client/pull/58)
* chore: bump version  by [@gagantrivedi](https://github.com/gagantrivedi) in [#59](https://github.com/Flagsmith/flagsmith-php-client/pull/59)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.1...v4.1.2

[Changes][v4.1.2]


<a id="v4.1.1"></a>
## [v4.1.1](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.1.1) - 2023-09-01

## What's Changed
* Avoid deprecation about dynamic properties by [@VincentLanglet](https://github.com/VincentLanglet) in [#55](https://github.com/Flagsmith/flagsmith-php-client/pull/55)
* Fix workflow php version by [@VincentLanglet](https://github.com/VincentLanglet) in [#56](https://github.com/Flagsmith/flagsmith-php-client/pull/56)
* Fix local evaluation when values are null by [@floranpagliai](https://github.com/floranpagliai) in [#53](https://github.com/Flagsmith/flagsmith-php-client/pull/53)

## New Contributors
* [@floranpagliai](https://github.com/floranpagliai) made their first contribution in [#53](https://github.com/Flagsmith/flagsmith-php-client/pull/53)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.0...v4.1.1

[Changes][v4.1.1]


<a id="v4.1.0"></a>
## [Version 4.1.0 (v4.1.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.1.0) - 2023-07-25

## What's Changed
* Ensure example application works by [@matthewelwell](https://github.com/matthewelwell) in [#47](https://github.com/Flagsmith/flagsmith-php-client/pull/47)
* feat: add segment `IN` operator by [@khvn26](https://github.com/khvn26) in [#51](https://github.com/Flagsmith/flagsmith-php-client/pull/51)

## New Contributors
* [@khvn26](https://github.com/khvn26) made their first contribution in [#51](https://github.com/Flagsmith/flagsmith-php-client/pull/51)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.0.0...v4.1.0

[Changes][v4.1.0]


<a id="v4.0.0"></a>
## [Version 4.0.0 (v4.0.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v4.0.0) - 2023-06-15

## What's Changed
 * BREAKING CHANGE: Use django id if present for percentage split evaluations by [@matthewelwell](https://github.com/matthewelwell) in [#48](https://github.com/Flagsmith/flagsmith-php-client/pull/48)

WARNING: We modified the local evaluation behaviour. You may see different flags returned to identities attributed to your percentage split-based segments after upgrading to this version.


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.4...v4.0.0

[Changes][v4.0.0]


<a id="v3.1.4"></a>
## [Version 3.1.4 (v3.1.4)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.1.4) - 2023-06-08

## What's Changed
* Add [@throws](https://github.com/throws) annotation by [@VincentLanglet](https://github.com/VincentLanglet) in [#39](https://github.com/Flagsmith/flagsmith-php-client/pull/39)
* Add PHP version 8.2 to CI tests by [@matthewelwell](https://github.com/matthewelwell) in [#45](https://github.com/Flagsmith/flagsmith-php-client/pull/45)
* Bump version v3.1.4 by [@matthewelwell](https://github.com/matthewelwell) in [#46](https://github.com/Flagsmith/flagsmith-php-client/pull/46)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.3...v3.1.4

[Changes][v3.1.4]


<a id="v3.1.3"></a>
## [Version 3.1.3 (v3.1.3)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.1.3) - 2023-05-24

## What's Changed
* chore/bump version by [@dabeeeenster](https://github.com/dabeeeenster) in [#42](https://github.com/Flagsmith/flagsmith-php-client/pull/42)
* fix/correct example by [@dabeeeenster](https://github.com/dabeeeenster) in [#40](https://github.com/Flagsmith/flagsmith-php-client/pull/40)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.2...v3.1.3

[Changes][v3.1.3]


<a id="v3.1.2"></a>
## [Version 3.1.2 (v3.1.2)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.1.2) - 2023-04-14

## What's Changed
* Create .gitattributes to ignore test folder by [@VincentLanglet](https://github.com/VincentLanglet) in [#38](https://github.com/Flagsmith/flagsmith-php-client/pull/38)
* PSR/simple-cache upgrade by [@BartoszBartniczak](https://github.com/BartoszBartniczak) in [#37](https://github.com/Flagsmith/flagsmith-php-client/pull/37)

## New Contributors
* [@BartoszBartniczak](https://github.com/BartoszBartniczak) made their first contribution in [#37](https://github.com/Flagsmith/flagsmith-php-client/pull/37)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.1...v3.1.2

[Changes][v3.1.2]


<a id="v3.1.1"></a>
## [Version 3.1.1 (v3.1.1)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.1.1) - 2023-03-14

## What's Changed
* Solve php 8.2 deprecation by [@VincentLanglet](https://github.com/VincentLanglet) in [#34](https://github.com/Flagsmith/flagsmith-php-client/pull/34)
* Release 3.1.1 by [@matthewelwell](https://github.com/matthewelwell) in [#35](https://github.com/Flagsmith/flagsmith-php-client/pull/35)

## New Contributors
* [@VincentLanglet](https://github.com/VincentLanglet) made their first contribution in [#34](https://github.com/Flagsmith/flagsmith-php-client/pull/34)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.0...v3.1.1

[Changes][v3.1.1]


<a id="v3.1.0"></a>
## [Version 3.1.0 (v3.1.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.1.0) - 2022-11-01

## What's Changed
* Add modulo operator by [@matthewelwell](https://github.com/matthewelwell) in [#32](https://github.com/Flagsmith/flagsmith-php-client/pull/32)
* Add IS_SET and IS_NOT_SET operators by [@matthewelwell](https://github.com/matthewelwell) in [#33](https://github.com/Flagsmith/flagsmith-php-client/pull/33)
* Release 3.1.0 by [@matthewelwell](https://github.com/matthewelwell) in [#31](https://github.com/Flagsmith/flagsmith-php-client/pull/31)


**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.0.2...v3.1.0

[Changes][v3.1.0]


<a id="v3.0.2"></a>
## [Version 3.0.2 (v3.0.2)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.0.2) - 2022-07-13

## What's Changed
* Mocking routes directly from file by [@doppiogancio](https://github.com/doppiogancio) in [#26](https://github.com/Flagsmith/flagsmith-php-client/pull/26)
* Fix random styling issues by [@matthewelwell](https://github.com/matthewelwell) in [#29](https://github.com/Flagsmith/flagsmith-php-client/pull/29)
* Use feature name for analytics by [@matthewelwell](https://github.com/matthewelwell) in [#28](https://github.com/Flagsmith/flagsmith-php-client/pull/28)
* Release 3.0.2 by [@matthewelwell](https://github.com/matthewelwell) in [#27](https://github.com/Flagsmith/flagsmith-php-client/pull/27)

## New Contributors
* [@doppiogancio](https://github.com/doppiogancio) made their first contribution in [#26](https://github.com/Flagsmith/flagsmith-php-client/pull/26)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.0.1...v3.0.2

[Changes][v3.0.2]


<a id="v3.0.1"></a>
## [Version 3.0.1 (v3.0.1)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.0.1) - 2022-06-07



[Changes][v3.0.1]


<a id="v3.0.0"></a>
## [Version 3.0.0 (v3.0.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v3.0.0) - 2022-06-07

## What's Changed
* Pass third declared param on set through by [@tm1000](https://github.com/tm1000) in [#13](https://github.com/Flagsmith/flagsmith-php-client/pull/13)
* Added example directory with docker environment and a README by [@fzia](https://github.com/fzia) in [#14](https://github.com/Flagsmith/flagsmith-php-client/pull/14)
* Github Actions Workflow  by [@fzia](https://github.com/fzia) in [#16](https://github.com/Flagsmith/flagsmith-php-client/pull/16)
* Feature - Flag Engine by [@fzia](https://github.com/fzia) in [#17](https://github.com/Flagsmith/flagsmith-php-client/pull/17)
* Feature - Client side Re-evaluation by [@fzia](https://github.com/fzia) in [#18](https://github.com/Flagsmith/flagsmith-php-client/pull/18)
* Prevent initialisation with local evaluation without server key by [@fzia](https://github.com/fzia) in [#20](https://github.com/Flagsmith/flagsmith-php-client/pull/20)
* Expose segments by [@fzia](https://github.com/fzia) in [#22](https://github.com/Flagsmith/flagsmith-php-client/pull/22)
* feat(semver): Add semver support to segment/traits by [@fzia](https://github.com/fzia) in [#21](https://github.com/Flagsmith/flagsmith-php-client/pull/21)
* Fix segment priorities by [@matthewelwell](https://github.com/matthewelwell) in [#24](https://github.com/Flagsmith/flagsmith-php-client/pull/24)
* Release 3.0.0 by [@matthewelwell](https://github.com/matthewelwell) in [#19](https://github.com/Flagsmith/flagsmith-php-client/pull/19)

## New Contributors
* [@fzia](https://github.com/fzia) made their first contribution in [#14](https://github.com/Flagsmith/flagsmith-php-client/pull/14)
* [@matthewelwell](https://github.com/matthewelwell) made their first contribution in [#24](https://github.com/Flagsmith/flagsmith-php-client/pull/24)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.1.1...v3.0.0

[Changes][v3.0.0]


<a id="v2.1.1"></a>
## [Version 2.1.1 (v2.1.1)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v2.1.1) - 2022-02-01

## What's Changed
* Remove Guzzle from require by [@tm1000](https://github.com/tm1000) in [#12](https://github.com/Flagsmith/flagsmith-php-client/pull/12)




[Changes][v2.1.1]


<a id="v2.1.0"></a>
## [Version 2.1.0 (v2.1.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v2.1.0) - 2022-01-31

## What's Changed
* Fix for PHP v8.0+ by [@dabeeeenster](https://github.com/dabeeeenster) in [#11](https://github.com/Flagsmith/flagsmith-php-client/pull/11)


[Changes][v2.1.0]


<a id="v2.0.2"></a>
## [Version 2.0.2 (v2.0.2)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v2.0.2) - 2021-11-18

## What's Changed
* FIX: Make initialValue nullable [#8](https://github.com/Flagsmith/flagsmith-php-client/issues/8) by [@JustinBack](https://github.com/JustinBack) in [#9](https://github.com/Flagsmith/flagsmith-php-client/pull/9)

## New Contributors
* [@JustinBack](https://github.com/JustinBack) made their first contribution in [#9](https://github.com/Flagsmith/flagsmith-php-client/pull/9)

**Full Changelog**: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.0.1...v2.0.2

[Changes][v2.0.2]


<a id="v2.0.1"></a>
## [Version 2.0.1 (v2.0.1)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v2.0.1) - 2021-10-29



[Changes][v2.0.1]


<a id="v2.0.0"></a>
## [Version 2.0 (v2.0.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v2.0.0) - 2021-10-21

Implements PSRs as per [#4](https://github.com/Flagsmith/flagsmith-php-client/issues/4) 

[Changes][v2.0.0]


<a id="v1.0.0"></a>
## [Flagsmith Rename (v1.0.0)](https://github.com/Flagsmith/flagsmith-php-client/releases/tag/v1.0.0) - 2021-05-04

- Refactored Bullet Train > Flagsmith
- New Packagist name
- You can now override the API URL for self hosted API installations. 

[Changes][v1.0.0]


[v4.5.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.5.0...v4.5.1
[v4.5.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.4.0...v4.5.0
[v4.4.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.3.1...v4.4.0
[v4.3.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.3.0...v4.3.1
[v4.3.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.2.1...v4.3.0
[v4.2.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.2.0...v4.2.1
[v4.2.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.2...v4.2.0
[v4.1.2]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.1...v4.1.2
[v4.1.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.1.0...v4.1.1
[v4.1.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v4.0.0...v4.1.0
[v4.0.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.4...v4.0.0
[v3.1.4]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.3...v3.1.4
[v3.1.3]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.2...v3.1.3
[v3.1.2]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.1...v3.1.2
[v3.1.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.1.0...v3.1.1
[v3.1.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.0.2...v3.1.0
[v3.0.2]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.0.1...v3.0.2
[v3.0.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v3.0.0...v3.0.1
[v3.0.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.1.1...v3.0.0
[v2.1.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.0.2...v2.1.0
[v2.0.2]: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.0.1...v2.0.2
[v2.0.1]: https://github.com/Flagsmith/flagsmith-php-client/compare/v2.0.0...v2.0.1
[v2.0.0]: https://github.com/Flagsmith/flagsmith-php-client/compare/v1.0.0...v2.0.0
[v1.0.0]: https://github.com/Flagsmith/flagsmith-php-client/tree/v1.0.0

<!-- Generated by https://github.com/rhysd/changelog-from-release v3.9.0 -->
