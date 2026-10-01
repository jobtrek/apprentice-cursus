<template>
    <!-- Hachures des barres insuffisantes : l'échec ne repose jamais sur la seule couleur. -->
    <svg width="0" height="0" aria-hidden="true" style="position: absolute">
        <defs>
            <pattern
                id="c-hatch"
                width="6"
                height="6"
                patternUnits="userSpaceOnUse"
                patternTransform="rotate(45)"
            >
                <rect width="6" height="6" class="c-hatch-bg" />
                <line x1="0" y1="0" x2="0" y2="6" class="c-hatch-line" />
            </pattern>
        </defs>
    </svg>
</template>
