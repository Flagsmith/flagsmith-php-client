<?php

use FlagsmithBenchmark\SdkBenchmark;

require __DIR__ . '/../vendor/autoload.php';

const REVOLUTIONS = 1000;

[, $benchmark, $environment] = $argv + [null, null, null];
$environmentsDir = getenv('SDK_BENCHMARKS_ENVIRONMENTS_DIR');

if (
    $environmentsDir === false
    || !in_array($benchmark, SdkBenchmark::BENCHMARKS, true)
    || !in_array($environment, SdkBenchmark::ENVIRONMENTS, true)
) {
    fwrite(STDERR, sprintf(
        "Usage: SDK_BENCHMARKS_ENVIRONMENTS_DIR=<dir> php %s <%s> <%s>\n",
        $argv[0],
        implode('|', SdkBenchmark::BENCHMARKS),
        implode('|', SdkBenchmark::ENVIRONMENTS),
    ));
    exit(2);
}

$sdkBenchmark = new SdkBenchmark($environmentsDir, $environment);
for ($revolution = 0; $revolution < REVOLUTIONS; $revolution++) {
    $sdkBenchmark->$benchmark();
}
