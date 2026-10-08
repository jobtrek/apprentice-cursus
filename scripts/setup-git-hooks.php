<?php

if (! is_dir('.git')) {
    exit(0);
}

exec('git config core.hooksPath .githooks');
