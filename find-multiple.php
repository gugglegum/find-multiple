#!/bin/php
<?php

declare(strict_types=1);

if ($_SERVER['argc'] < 4) {
	die("Usage: php " . basename($_SERVER['PHP_SELF']) . " <directory> <filename_mask> <regex1> [<regex2> ... <regexN>]\n");
}

$directory = $argv[1];
$filenameMask = $argv[2];
$regexPatterns = array_slice($argv, 3);

echo "Directory: {$directory}\n";
echo "File mask: {$filenameMask}\n";
echo "Regex patterns:\n";
foreach ($regexPatterns as $regexPattern) {
	echo "\t{$regexPattern}\n";
}
echo "\n";

set_error_handler(
    /**
     * @throws Exception
     */
    function(int $errNo, string $errStr) {
        throw new Exception($errStr, $errNo);
    },
    E_WARNING
);

try {
    scanDirectory($directory, $filenameMask, $regexPatterns);
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

/**
 * Checks if the file matches all regular expressions
 *
 * @param array $content The contents of the file as an array of strings
 * @param array $regexPatterns Array of regular expressions
 * @return array An array of strings containing matches
 * @throws Exception
 */
function checkFileAgainstPatterns(array $content, array $regexPatterns): array
{
	$allMatches = [];

	foreach ($regexPatterns as $pattern) {
		$matches = [];
		foreach ($content as $lineNumber => $line) {
            $res = preg_match_throwOnError($pattern, $line);
            if ($res === false) {
                throw new Exception("Invalid regular expression: {$pattern}");
            }
			if ($res) {
				$start = max(0, $lineNumber - 5);
				$end = min(count($content) - 1, $lineNumber + 5);
				for ($i = $start; $i <= $end; $i++) {
					if (!isset($matches[$i])) {
						$matches[$i] = [
							'line' => $content[$i],
							'matched' => $i === $lineNumber ? ' >' : '  '
						];
					} elseif ($i === $lineNumber) {
						$matches[$i]['matched'] = ' >';
					}
				}
			}
		}
		if (empty($matches)) {
			return [];
		}
		$allMatches[] = $matches;
	}

	return mergeMatches($allMatches);
}

/**
 * Combines all matches from different regular expressions
 *
 * @param array $allMatches An array of all matches
 * @return array Combined array of matches
 */
function mergeMatches(array $allMatches): array
{
	$mergedMatches = [];

	foreach ($allMatches as $matches) {
		foreach ($matches as $lineNumber => $match) {
			if (!isset($mergedMatches[$lineNumber])) {
				$mergedMatches[$lineNumber] = $match;
			} elseif ($match['matched'] === '>') {
				$mergedMatches[$lineNumber]['matched'] = '>';
			}
		}
	}

	ksort($mergedMatches);
	return $mergedMatches;
}

/**
 * Outputs the matches for the file
 *
 * @param string $filePath File Path
 * @param array $mergedMatches An array of merged matches
 */
function printMatches(string $filePath, array $mergedMatches): void
{
	echo $filePath . PHP_EOL;

	$previousLine = -1;
	foreach ($mergedMatches as $lineNumber => $match) {
		if ($previousLine !== -1 && $lineNumber > $previousLine + 1) {
			echo str_repeat('-', 80) . PHP_EOL;
		}
		printf("%5d%s %s\n", $lineNumber + 1, $match['matched'], $match['line']);
		$previousLine = $lineNumber;
	}
	echo PHP_EOL . PHP_EOL;
}

/**
 * Recursively scans the directory and processes files
 *
 * @param string $directory The directory to scan
 * @param string $filenameMask Filename mask (wildcard)
 * @param array $regexPatterns Array of regular expressions
 * @throws Exception
 */
function scanDirectory(string $directory, string $filenameMask, array $regexPatterns): void
{
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

	foreach ($iterator as $file) {
		if ($file->isFile() && fnmatch($filenameMask, $file->getFilename())) {
			$content = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
			$mergedMatches = checkFileAgainstPatterns($content, $regexPatterns);

			if (!empty($mergedMatches)) {
				printMatches($file->getPathname(), $mergedMatches);
			}
		}
	}
}

/**
 * Preg_match wrapper with exception support
 *
 * @throws Exception
 */
function preg_match_throwOnError(string $pattern, string $subject, array &$matches = null, int $flags = 0, int $offset = 0): int
{
    try {
        return (int) @preg_match($pattern, $subject, $matches, $flags, $offset);
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $trimPrefix = 'preg_match(): ';
        throw new Exception("Invalid regular expression \"{$pattern}\" - "
            . (str_starts_with($msg, $trimPrefix) ? substr($msg, strlen($trimPrefix)) : $msg),
            $e->getCode(), $e);
    }
}
