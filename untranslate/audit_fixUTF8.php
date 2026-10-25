<?php
// Usage: php audit_fixUTF8.php file1.txt [file2.txt ...]   (globs work: *.txt)
// For files that are believed to be clean UTF-8 already: reports every line
// fixUTF8() would change, so false positives can't slip through unnoticed.
require __DIR__ . '/aion_common_encoding_claude.php';

$total = 0;
foreach (array_slice($argv, 1) as $file) {
    $fp = fopen($file, 'rb');
    if ($fp === false) { fwrite(STDERR, "cannot open $file\n"); continue; }
    $n = 0; $changed = 0;
    while (($line = fgets($fp)) !== false) {
        $n++;
        $fixed = fixUTF8($line);
        if ($fixed !== $line) {
            $changed++;
            if ($changed <= 5) {
                echo "$file:$n\n  < ", rtrim($line), "\n  > ", rtrim($fixed), "\n";
            }
        }
    }
    fclose($fp);
    $total += $changed;
    printf("%-60s %7d lines  %5d changed\n", basename($file), $n, $changed);
}
echo "TOTAL CHANGED: $total\n";
