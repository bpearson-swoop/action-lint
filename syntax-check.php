<?php

require_once __DIR__.'/src/defines.php';
require_once __DIR__.'/src/environment.php';
require_once __DIR__.'/src/files.php';

// Defaults.
$phpextensions = ['php'];
$msgLevel      = INFO;

// Environment variables.
$phpextensions = environment('INPUT_PHP_FILE_EXTENSIONS', $phpextensions);
$msgLevel      = environment('MSGLEVEL', $msgLevel);

$phpextensions = array_map('strtolower', array_map('trim', $phpextensions));

logmsg("PHP File extensions: " . implode(', ', $phpextensions), DEBUG);

$counts = [
    'check' => 0,
    'skip'  => 0,
];
$exit   = 0;

// Get all files in the current directory.
$files = getFiles();
foreach ($files as $file => $info) {
    if (in_array($info->getExtension(), $phpextensions)) {
        logmsg("Checking file: $file", DEBUG);
        $command = sprintf('php -l %s 2>&1', escapeshellarg($file));
        $output  = [];
        $retVal  = exec($command, $output, $exitCode);

        $counts['check']++;
        if ($exitCode === 0) {
            // Lint passed.
            logmsg("No syntax error in file: $file", DEBUG);
            continue;
        }//end if

        $exit    = 1;
        $echoed  = false;
        $lines   = getLines($file);
        foreach ($output as $line) {
            // Error should read: PHP Parse error:  syntax error, unexpected '}' in /path/to/file.php on line 1
            $matched = preg_match("/Parse error:\s+(?P<error>.*) in (?P<file>.*) on line (?P<line>\d+)/", $line, $matches);
            if ($matched) {
                $relativePath = substr($file, 2);
                $line         = min($matches['line'], $lines);
                $echoed       = true;
                logmsg("Syntax error on {$relativePath}:{$line} - {$matches['error']}", ERROR);
                break;
            }//end if
        }//end foreach

        if ($echoed === false) {
            // Fallback if anything goes wrong.
            logmsg("Syntax error in {$file}", ERROR);
        }//end if
    } else {
        logmsg("Skipping file: {$file}", DEBUG);
        $counts['skip']++;
        continue;
    }//end if
}//end foreach

exit($exit);

/**
 * Get a line count from a file.
 *
 * @param string $file The file to get the line count from.
 *
 * @return int
 */
function getLines($file)
{
    $lines = 0;
    exec("wc -l $file", $output);
    if (!empty($output)) {
        $lines = (int) trim(explode(' ', $output[0])[0]);
    }//end if

    return $lines;

}//end getLines()


/**
 * Log a message based on the level.
 *
 * @param string $message The message to log.
 * @param int    $level   The level of the message.
 *
 * @return void
 */
function logmsg($message, $level)
{
    global $msgLevel;

    if ($level <= $msgLevel) {
        echo $message . "\n";
    }//end if

}//end logmsg()
