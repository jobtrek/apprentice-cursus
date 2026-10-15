<?php

return [
    /*
     * Off until the OCR is validated: hides the dialog's switch and makes
     * the read-scan route answer 404.
     */
    'enabled' => (bool) env('OCR_ENABLED', false),

    /*
     * USD per 1M tokens, the prices notar measured with
     * (docs/ocr_specs/final_bench_mark.md). Each scan stores its cost at the
     * time it ran, so changing these does not rewrite past scans.
     */
    'prices' => [
        'gemini' => ['input' => 0.25, 'output' => 1.50],
        'jev' => ['input' => 0.042],
    ],
];
