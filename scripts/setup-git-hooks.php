<?php

exec('git rev-parse --is-inside-work-tree 2>/dev/null', $output, $status);

if ($status !== 0 || ($output[0] ?? '') !== 'true') {
    exit(0);
}

exec('git config core.hooksPath .githooks', $output, $status);

if ($status !== 0) {
    fwrite(STDERR, "Unable to configure the Git hooks path.\n");
    exit($status);
}
