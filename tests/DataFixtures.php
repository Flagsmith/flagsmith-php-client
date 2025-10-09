<?php

namespace FlagsmithTest;

class DataFixtures
{
    private const DATA_DIR = __DIR__ . \DIRECTORY_SEPARATOR . 'Data' . \DIRECTORY_SEPARATOR;

    /**
     * Read the contents of a data fixture
     * @param string $file
     * @return string
     * @throws \ValueError
     */
    public static function loadFileContents($file): string
    {
        $result = file_get_contents(self::DATA_DIR . $file);

        if ($result === false) {
            throw new \ValueError("Failed to read {$file} from " . self::DATA_DIR);
        }

        return $result;
    }
}
