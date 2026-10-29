SDK_BENCHMARKS := tests/Engine/EngineTests/SdkBenchmarks

$(SDK_BENCHMARKS)/environments:
	git submodule update --init -- $(SDK_BENCHMARKS)

vendor/autoload.php: composer.json
	composer install --prefer-dist --no-interaction
	touch $@

.PHONY: benchmark
benchmark: vendor/autoload.php $(SDK_BENCHMARKS)/environments
	SDK_BENCHMARKS_ENVIRONMENTS_DIR=$(abspath $(SDK_BENCHMARKS)/environments) codspeed run --mode walltime
