<?php

use Symfony\Component\Process\Process;

// Laravel snapshots these source dates during config:cache at deploy time.
$process = new Process(['git', 'log', '--format=%cI', '--name-only', '--', 'docs/'], base_path());
$process->run();

$dates = [];
$date = null;

foreach (explode("\n", $process->isSuccessful() ? $process->getOutput() : '') as $line) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $line)) {
        $date = $line;
    } elseif ($line !== '' && $date !== null) {
        $dates[$line] ??= $date;
    }
}

return $dates;
