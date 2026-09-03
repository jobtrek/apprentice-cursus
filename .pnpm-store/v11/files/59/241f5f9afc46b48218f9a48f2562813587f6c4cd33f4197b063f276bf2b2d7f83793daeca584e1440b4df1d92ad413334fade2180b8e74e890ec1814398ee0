// src/index.ts
import fs6 from "fs";
import os from "os";
import { fileURLToPath } from "url";
import path5 from "path";
import { globSync } from "tinyglobby";
import colors from "picocolors";
import { loadEnv, createLogger, defaultAllowedOrigins } from "vite";
import fullReload from "vite-plugin-full-reload";

// src/fonts/plugin.ts
import fs5 from "fs";
import path4 from "path";

// src/fonts/cache.ts
import fs from "fs";
import path from "path";
import { createHash } from "crypto";
var DEFAULT_CACHE_DIR = "node_modules/.cache/laravel-vite-plugin/fonts";
function resolveCacheDir(projectRoot, cacheDir) {
  const dir = cacheDir ?? path.resolve(projectRoot, DEFAULT_CACHE_DIR);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  return dir;
}
function cacheKey(input) {
  return createHash("sha256").update(input).digest("hex").slice(0, 16);
}
function readCache(cacheDir, key) {
  const filePath = path.join(cacheDir, key);
  return fs.existsSync(filePath) ? fs.readFileSync(filePath) : void 0;
}
function readCacheText(cacheDir, key) {
  return readCache(cacheDir, key)?.toString("utf-8");
}
function writeCache(cacheDir, key, data) {
  fs.writeFileSync(path.join(cacheDir, key), data);
}
async function fetchOrThrow(url, headers) {
  const response = await fetch(url, { headers });
  if (!response.ok) {
    throw new Error(
      `laravel-vite-plugin: Failed to fetch "${url}": ${response.status} ${response.statusText}`
    );
  }
  return response;
}
async function fetchAndCache(url, cacheDir, headers) {
  const key = cacheKey(url);
  const cached = readCache(cacheDir, key);
  if (cached) {
    return cached;
  }
  const response = await fetchOrThrow(url, headers);
  const buffer = Buffer.from(await response.arrayBuffer());
  writeCache(cacheDir, key, buffer);
  return buffer;
}
async function fetchTextAndCache(url, cacheDir, headers) {
  const key = cacheKey(url + ":text");
  const cached = readCacheText(cacheDir, key);
  if (cached) {
    return cached;
  }
  const response = await fetchOrThrow(url, headers);
  const text = await response.text();
  writeCache(cacheDir, key, text);
  return text;
}

// src/fonts/config.ts
import fs2 from "fs";
import path2 from "path";
import { glob } from "tinyglobby";
var FORMATS = [
  {
    type: "woff2",
    extension: ".woff2",
    keyword: "woff2"
  },
  {
    type: "woff",
    extension: ".woff",
    keyword: "woff"
  },
  {
    type: "ttf",
    extension: ".ttf",
    keyword: "truetype"
  },
  {
    type: "otf",
    extension: ".otf",
    keyword: "opentype"
  },
  {
    type: "eot",
    extension: ".eot",
    keyword: "embedded-opentype"
  }
];
var FORMAT_PREFERENCE = FORMATS.map((f) => f.type);
var FORMAT_MAP = Object.fromEntries(
  FORMATS.map((f) => [f.extension, f.type])
);
var FORMAT_KEYWORD_MAP = Object.fromEntries(
  FORMATS.map((f) => [f.type, f.keyword])
);
function formatKeyword(format) {
  return FORMAT_KEYWORD_MAP[format];
}
var SUPPORTED_EXTENSIONS = FORMATS.map((f) => f.extension);
var SUPPORTED_GLOB = `*.{${SUPPORTED_EXTENSIONS.map((ext) => ext.slice(1)).join(",")}}`;
var DEFAULT_WEIGHT = 400;
var DEFAULT_STYLE = "normal";
var WEIGHT_PATTERNS = [
  ["extrabold", 800],
  ["ultrabold", 800],
  ["semibold", 600],
  ["demibold", 600],
  ["extralight", 200],
  ["ultralight", 200],
  ["hairline", 100],
  ["thin", 100],
  ["light", 300],
  ["regular", 400],
  ["normal", 400],
  ["medium", 500],
  ["black", 900],
  ["heavy", 900],
  ["bold", 700]
];
function splitStem(stem) {
  return stem.split(/[-_]/).filter(Boolean);
}
function stripStyleSuffix(segment) {
  return segment.replace(/(?:italic|it|oblique)$/i, "");
}
function inferWeightFromFilename(filePath) {
  const stem = path2.basename(filePath, path2.extname(filePath));
  const segments = splitStem(stem);
  for (let i = segments.length - 1; i >= 0; i--) {
    const raw = segments[i];
    const stripped = stripStyleSuffix(raw);
    const candidate = stripped || raw;
    const lc = candidate.toLowerCase();
    const match = WEIGHT_PATTERNS.find(([pattern]) => lc === pattern) ?? WEIGHT_PATTERNS.find(
      ([pattern]) => lc.length > pattern.length && lc.endsWith(pattern)
    );
    if (match) {
      return match[1];
    }
    const numMatch = candidate.match(/(?:^|[^\d])([1-9]00)$/);
    if (numMatch) {
      return parseInt(numMatch[1], 10);
    }
  }
  return DEFAULT_WEIGHT;
}
function inferStyleFromFilename(filePath) {
  const stem = path2.basename(filePath, path2.extname(filePath));
  const segments = splitStem(stem);
  for (let i = segments.length - 1; i >= 0; i--) {
    const seg = segments[i];
    const lc = seg.toLowerCase();
    if (["it", "italic"].includes(lc) || /italic$/i.test(seg)) {
      return "italic";
    }
    if (lc.endsWith("it") && lc.length > 2) {
      const prefix = lc.slice(0, -2);
      if (WEIGHT_PATTERNS.some(([pattern]) => prefix === pattern || prefix.endsWith(pattern))) {
        return "italic";
      }
    }
    if (lc === "oblique" || /oblique$/i.test(seg)) {
      return "oblique";
    }
  }
  return DEFAULT_STYLE;
}
function inferLocalVariantFromFilename(filePath) {
  return {
    weight: inferWeightFromFilename(filePath),
    style: inferStyleFromFilename(filePath)
  };
}
function looksLikeVariableFontFilename(filePath) {
  const stem = path2.basename(filePath, path2.extname(filePath));
  return /\[.+\]/.test(stem);
}
async function discoverFromGlob(family, src, projectRoot) {
  const files = await glob(src, { cwd: projectRoot, absolute: true });
  const supported = files.filter((f) => SUPPORTED_EXTENSIONS.includes(path2.extname(f).toLowerCase()));
  if (supported.length === 0) {
    throw new Error(
      `laravel-vite-plugin: Local font "${family}" shorthand src "${src}" matched no supported font files.`
    );
  }
  return supported;
}
async function discoverFromDirectory(family, src, absoluteSrc) {
  const files = await glob(`**/${SUPPORTED_GLOB}`, { cwd: absoluteSrc, absolute: true });
  if (files.length === 0) {
    throw new Error(
      `laravel-vite-plugin: Local font "${family}" directory "${src}" contains no supported font files.`
    );
  }
  return files;
}
async function discoverFontFiles(family, src, projectRoot) {
  const absoluteSrc = path2.isAbsolute(src) ? src : path2.resolve(projectRoot, src);
  if (/[*?{]/.test(src)) {
    return discoverFromGlob(family, src, projectRoot);
  }
  if (fs2.existsSync(absoluteSrc) && fs2.statSync(absoluteSrc).isDirectory()) {
    return discoverFromDirectory(family, src, absoluteSrc);
  }
  if (fs2.existsSync(absoluteSrc) && fs2.statSync(absoluteSrc).isFile()) {
    return [absoluteSrc];
  }
  throw new Error(
    `laravel-vite-plugin: Local font "${family}" shorthand src "${src}" does not exist (resolved to "${absoluteSrc}").`
  );
}
function rejectVariableFontFiles(family, files) {
  for (const file of files) {
    if (looksLikeVariableFontFilename(file)) {
      throw new Error(
        `laravel-vite-plugin: Local font "${family}" shorthand discovered a variable font file "${path2.basename(file)}". Variable fonts require explicit "variants" with a weight range instead of shorthand "src".`
      );
    }
  }
}
function groupFilesByVariant(files) {
  const groups = /* @__PURE__ */ new Map();
  for (const file of files) {
    const { weight, style } = inferLocalVariantFromFilename(file);
    const key = `${weight}:${style}`;
    if (!groups.has(key)) {
      groups.set(key, { weight, style, files: [] });
    }
    groups.get(key).files.push({
      source: file,
      format: inferFormat(file)
    });
  }
  for (const group of groups.values()) {
    group.files.sort(
      (a, b) => FORMAT_PREFERENCE.indexOf(a.format) - FORMAT_PREFERENCE.indexOf(b.format)
    );
  }
  return Array.from(groups.values()).sort((a, b) => {
    const wA = typeof a.weight === "number" ? a.weight : parseInt(String(a.weight), 10);
    const wB = typeof b.weight === "number" ? b.weight : parseInt(String(b.weight), 10);
    if (wA !== wB) {
      return wA - wB;
    }
    return a.style.localeCompare(b.style);
  });
}
async function resolveLocalShorthandVariants(definition, localConfig, projectRoot) {
  const discoveredFiles = await discoverFontFiles(definition.family, localConfig.src, projectRoot);
  discoveredFiles.sort();
  rejectVariableFontFiles(definition.family, discoveredFiles);
  return groupFilesByVariant(discoveredFiles);
}
function familyToSlug(family) {
  return family.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
}
function buildResolvedFamily(definition, variants) {
  return {
    family: definition.family,
    alias: definition.alias,
    variable: definition.variable,
    display: definition.display,
    optimizedFallbacks: definition.optimizedFallbacks,
    fallbacks: definition.fallbacks,
    preload: definition.preload,
    provider: definition.provider,
    variants
  };
}
function inferFormat(filePath) {
  const ext = path2.extname(filePath).toLowerCase();
  const format = FORMAT_MAP[ext];
  if (!format) {
    throw new Error(
      `laravel-vite-plugin: Unsupported font file format "${ext}" for file "${filePath}". Supported formats: ${SUPPORTED_EXTENSIONS.join(", ")}`
    );
  }
  return format;
}
function throwIfEmptyString(value, message) {
  if (typeof value !== "string" || value.trim() === "") {
    throw new Error(message);
  }
}
function validateFontDefinition(definition) {
  throwIfEmptyString(
    definition.family,
    "laravel-vite-plugin: Font family name must be a non-empty string."
  );
  throwIfEmptyString(
    definition.alias,
    `laravel-vite-plugin: Font "${definition.family}" has an invalid or empty alias.`
  );
  if (definition.variable !== void 0) {
    throwIfEmptyString(
      definition.variable,
      `laravel-vite-plugin: Font "${definition.family}" has an invalid or empty variable name.`
    );
    if (!definition.variable.startsWith("--")) {
      throw new Error(
        `laravel-vite-plugin: Font "${definition.family}" variable "${definition.variable}" must start with "--".`
      );
    }
  }
  if (definition.provider !== "local") {
    if (definition.styles.includes("oblique")) {
      throw new Error(
        `laravel-vite-plugin: Font "${definition.family}" uses provider "${definition.provider}", which does not support the "oblique" style. Use "italic" instead, or load the family with the local() provider.`
      );
    }
    return;
  }
  const localConfig = definition._local;
  if (!localConfig) {
    throw new Error(
      `laravel-vite-plugin: Local font "${definition.family}" must specify either "src" or "variants".`
    );
  }
  if ("src" in localConfig && "variants" in localConfig) {
    throw new Error(
      `laravel-vite-plugin: Local font "${definition.family}" cannot specify both "src" and "variants".`
    );
  }
  if ("src" in localConfig) {
    if (typeof localConfig.src !== "string" || localConfig.src.trim() === "") {
      throw new Error(
        `laravel-vite-plugin: Local font "${definition.family}" has an invalid or empty "src".`
      );
    }
    return;
  }
  const variants = localConfig.variants;
  if (!variants || variants.length === 0) {
    throw new Error(
      `laravel-vite-plugin: Local font "${definition.family}" must specify at least one variant.`
    );
  }
  for (const v of variants) {
    const sources = Array.isArray(v.src) ? v.src : [v.src];
    if (sources.length === 0 || sources.some((s) => typeof s !== "string" || s.trim() === "")) {
      throw new Error(
        `laravel-vite-plugin: Local font "${definition.family}" has a variant with an invalid or empty src.`
      );
    }
  }
}
function mergeFontDefinitions(fonts) {
  const byAlias = /* @__PURE__ */ new Map();
  const result = [];
  for (const font of fonts) {
    const existing = byAlias.get(font.alias);
    if (!existing) {
      const clone = { ...font };
      if (font._local) {
        clone._local = "variants" in font._local ? { variants: [...font._local.variants] } : { ...font._local };
      }
      byAlias.set(font.alias, clone);
      result.push(clone);
      continue;
    }
    if (existing.provider !== font.provider) {
      throw new Error(
        `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": provider mismatch ("${existing.provider}" vs "${font.provider}").`
      );
    }
    if (existing.variable !== font.variable) {
      throw new Error(
        `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": variable mismatch ("${existing.variable}" vs "${font.variable}").`
      );
    }
    if (existing.display !== font.display) {
      throw new Error(
        `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": display mismatch ("${existing.display}" vs "${font.display}").`
      );
    }
    if (JSON.stringify(existing.fallbacks) !== JSON.stringify(font.fallbacks)) {
      throw new Error(
        `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": fallbacks mismatch.`
      );
    }
    if (JSON.stringify(existing.preload) !== JSON.stringify(font.preload)) {
      throw new Error(
        `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": preload mismatch.`
      );
    }
    const weightSet = new Set(existing.weights.map(String));
    for (const w of font.weights) {
      if (!weightSet.has(String(w))) {
        existing.weights.push(w);
        weightSet.add(String(w));
      }
    }
    const styleSet = new Set(existing.styles);
    for (const s of font.styles) {
      if (!styleSet.has(s)) {
        existing.styles.push(s);
        styleSet.add(s);
      }
    }
    const subsetSet = new Set(existing.subsets);
    for (const s of font.subsets) {
      if (!subsetSet.has(s)) {
        existing.subsets.push(s);
        subsetSet.add(s);
      }
    }
    if (existing._local && font._local) {
      if ("variants" in existing._local && "variants" in font._local) {
        existing._local.variants.push(...font._local.variants);
      } else {
        throw new Error(
          `laravel-vite-plugin: Cannot merge font definitions for alias "${font.alias}": incompatible local font shapes (one uses "src" and the other uses "variants").`
        );
      }
    }
  }
  return result;
}
function validateFontsConfig(fonts) {
  const merged = mergeFontDefinitions(fonts);
  const aliases = /* @__PURE__ */ new Set();
  const variables = /* @__PURE__ */ new Set();
  for (const font of merged) {
    validateFontDefinition(font);
    if (aliases.has(font.alias)) {
      throw new Error(
        `laravel-vite-plugin: Duplicate font alias "${font.alias}". Each alias must be unique. Use the "alias" option to disambiguate.`
      );
    }
    aliases.add(font.alias);
    if (variables.has(font.variable)) {
      throw new Error(
        `laravel-vite-plugin: Duplicate CSS variable "${font.variable}". Use the "variable" option to set a unique variable name.`
      );
    }
    variables.add(font.variable);
  }
  return merged;
}
function resolveLocalExplicitVariants(definition, localConfig, projectRoot) {
  const variants = [];
  for (const v of localConfig.variants) {
    const sources = Array.isArray(v.src) ? v.src : [v.src];
    const files = [];
    for (const src of sources) {
      const absolutePath = path2.isAbsolute(src) ? src : path2.resolve(projectRoot, src);
      if (!fs2.existsSync(absolutePath)) {
        throw new Error(
          `laravel-vite-plugin: Local font file not found: "${src}" (resolved to "${absolutePath}") for font "${definition.family}".`
        );
      }
      files.push({
        source: absolutePath,
        format: inferFormat(absolutePath)
      });
    }
    files.sort(
      (a, b) => FORMAT_PREFERENCE.indexOf(a.format) - FORMAT_PREFERENCE.indexOf(b.format)
    );
    const firstSrc = Array.isArray(v.src) ? v.src[0] : v.src;
    const inferred = inferLocalVariantFromFilename(firstSrc);
    variants.push({
      weight: v.weight ?? inferred.weight,
      style: v.style ?? inferred.style,
      files
    });
  }
  return variants;
}
async function resolveLocalVariants(definition, projectRoot) {
  const localConfig = definition._local;
  return "variants" in localConfig ? resolveLocalExplicitVariants(definition, localConfig, projectRoot) : resolveLocalShorthandVariants(definition, localConfig, projectRoot);
}
async function resolveLocalFont(definition, projectRoot) {
  return buildResolvedFamily(definition, await resolveLocalVariants(definition, projectRoot));
}

// src/fonts/css.ts
function generateSrc(files, filePathMap) {
  return files.map((file) => {
    const url = filePathMap.get(file.source) ?? file.source;
    return `url("${url}") format("${formatKeyword(file.format)}")`;
  }).join(",\n    ");
}
function generateFontFace(family, filePathMap) {
  const rules = [];
  for (const variant of family.variants) {
    const rangedFiles = variant.files.filter((f) => f.unicodeRange);
    const nonRangedFiles = variant.files.filter((f) => !f.unicodeRange);
    for (const file of rangedFiles) {
      const fileSrc = `url("${filePathMap.get(file.source) ?? file.source}") format("${formatKeyword(file.format)}")`;
      rules.push([
        "@font-face {",
        `  font-family: "${family.family}";`,
        `  font-style: ${variant.style};`,
        `  font-weight: ${String(variant.weight)};`,
        `  font-display: ${family.display};`,
        `  src: ${fileSrc};`,
        `  unicode-range: ${file.unicodeRange};`,
        "}"
      ].join("\n"));
    }
    if (nonRangedFiles.length > 0) {
      const src = generateSrc(nonRangedFiles, filePathMap);
      rules.push([
        "@font-face {",
        `  font-family: "${family.family}";`,
        `  font-style: ${variant.style};`,
        `  font-weight: ${String(variant.weight)};`,
        `  font-display: ${family.display};`,
        `  src: ${src};`,
        "}"
      ].join("\n"));
    }
  }
  return rules.join("\n\n");
}
function generateFallbackFontFace(fallbackFamily, metrics) {
  return [
    "@font-face {",
    `  font-family: "${fallbackFamily}";`,
    `  src: local("${metrics.localFont}");`,
    `  ascent-override: ${metrics.ascentOverride};`,
    `  descent-override: ${metrics.descentOverride};`,
    `  line-gap-override: ${metrics.lineGapOverride};`,
    `  size-adjust: ${metrics.sizeAdjust};`,
    "}"
  ].join("\n");
}
function generateFontClassForFamily(family) {
  return `.${family.variable.replace(/^--/, "")} {
  font-family: var(${family.variable});
}`;
}
function generateFontClasses(families) {
  return families.map((f) => generateFontClassForFamily(f)).join("\n\n");
}
function familyVariableDeclaration(family) {
  const parts = [`"${family.family}"`];
  if (family.optimizedFallbacks) {
    parts.push(`"${family.family} fallback"`);
  }
  if (family.fallbacks.length > 0) {
    parts.push(...family.fallbacks);
  }
  return `${family.variable}: ${parts.join(", ")};`;
}
function generateCssVariables(families) {
  const lines = families.map((f) => `  ${familyVariableDeclaration(f)}`).join("\n");
  return [":root {", lines, "}"].join("\n");
}
function generateCssVariablesMap(families) {
  const map = {};
  for (const family of families) {
    map[family.alias] = familyVariableDeclaration(family);
  }
  return map;
}
function buildFamilyCss(family, filePathMap, fallbackMap) {
  let css = generateFontFace(family, filePathMap);
  if (family.optimizedFallbacks && fallbackMap?.has(family.alias)) {
    const fb = fallbackMap.get(family.alias);
    css += "\n\n" + generateFallbackFontFace(fb.fallbackFamily, fb.metrics);
  }
  return css;
}
function generateFamilyStyles(families, filePathMap, fallbackMap) {
  const familyStyles = {};
  for (const family of families) {
    familyStyles[family.alias] = buildFamilyCss(family, filePathMap, fallbackMap) + "\n\n" + generateFontClassForFamily(family);
  }
  return {
    familyStyles,
    variables: generateCssVariablesMap(families)
  };
}
function generateFontCss(families, filePathMap, fallbackMap) {
  const parts = families.map((f) => buildFamilyCss(f, filePathMap, fallbackMap));
  parts.push(generateCssVariables(families));
  parts.push(generateFontClasses(families));
  return parts.join("\n\n") + "\n";
}

// src/fonts/dev-server.ts
import fs3 from "fs";

// src/fonts/types.ts
var FORMAT_MIME = {
  woff2: "font/woff2",
  woff: "font/woff",
  ttf: "font/ttf",
  otf: "font/otf",
  eot: "application/vnd.ms-fontobject"
};

// src/fonts/dev-server.ts
var FONT_ROUTE_PREFIX = "/__laravel_vite_plugin__/fonts";
function buildDevUrlMap(families, devServerUrl) {
  const urlMap = /* @__PURE__ */ new Map();
  for (const family of families) {
    for (const variant of family.variants) {
      for (const file of variant.files) {
        if (!urlMap.has(file.source)) {
          const hash = cacheKey(file.source);
          const ext = file.format === "woff2" ? ".woff2" : `.${file.format}`;
          urlMap.set(file.source, `${devServerUrl}${FONT_ROUTE_PREFIX}/${hash}${ext}`);
        }
      }
    }
  }
  return urlMap;
}
function createFontMiddleware() {
  let lookup = /* @__PURE__ */ new Map();
  function update(families) {
    const newLookup = /* @__PURE__ */ new Map();
    for (const family of families) {
      for (const variant of family.variants) {
        for (const file of variant.files) {
          const hash = cacheKey(file.source);
          newLookup.set(hash, { source: file.source, format: file.format });
        }
      }
    }
    lookup = newLookup;
  }
  function middleware(req, res, next) {
    if (!req.url?.startsWith(FONT_ROUTE_PREFIX + "/")) {
      return next();
    }
    const fileName = req.url.slice(FONT_ROUTE_PREFIX.length + 1);
    const hash = fileName.replace(/\.[^.]+$/, "");
    const entry = lookup.get(hash);
    if (!entry) {
      res.statusCode = 404;
      res.end("Font not found");
      return;
    }
    if (!fs3.existsSync(entry.source)) {
      res.statusCode = 404;
      res.end("Font file not found on disk");
      return;
    }
    const mime = FORMAT_MIME[entry.format] ?? "application/octet-stream";
    res.setHeader("Content-Type", mime);
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Cache-Control", "no-store");
    const stream = fs3.createReadStream(entry.source);
    stream.on("error", (err) => {
      if (!res.headersSent) {
        res.statusCode = 500;
      }
      res.destroy(err);
    });
    res.on("close", () => {
      stream.destroy();
    });
    stream.pipe(res);
  }
  return { middleware, update };
}

// src/fonts/fallback.ts
import { pathToFileURL } from "url";
var FALLBACK_METRICS = {
  "sans-serif": {
    localFont: "Arial",
    ascent: 1854,
    descent: -434,
    lineGap: 67,
    unitsPerEm: 2048,
    xWidthAvg: 904
  },
  serif: {
    localFont: "Times New Roman",
    ascent: 1825,
    descent: -443,
    lineGap: 87,
    unitsPerEm: 2048,
    xWidthAvg: 832
  },
  monospace: {
    localFont: "Courier New",
    ascent: 1705,
    descent: -615,
    lineGap: 0,
    unitsPerEm: 2048,
    xWidthAvg: 1229
  }
};
function resolveFallbackCategory(category) {
  const validCategories = ["sans-serif", "serif", "monospace"];
  return validCategories.includes(category) ? category : "sans-serif";
}
function resolveMetricsSource(fontSource) {
  return /^[a-z][a-z\d+.-]*:\/\//i.test(fontSource) ? fontSource : pathToFileURL(fontSource);
}
async function generateFallbackMetrics(fontSource, warn = () => void 0) {
  let fontaine;
  try {
    fontaine = await import("fontaine");
  } catch {
    warn('Optimized font fallbacks require the optional "fontaine" package. Install it, or set "optimizedFallbacks: false" on your fonts to disable the feature.');
    return void 0;
  }
  try {
    const metrics = await fontaine.readMetrics(resolveMetricsSource(fontSource));
    if (!metrics) {
      warn(`Unable to read font metrics from [${fontSource}]. Skipping optimized fallback generation for this font.`);
      return void 0;
    }
    const { ascent, descent, lineGap, unitsPerEm, xWidthAvg, category } = metrics;
    if (ascent == null || descent == null || lineGap == null || unitsPerEm == null) {
      warn(`Unable to read font metrics from [${fontSource}]. Skipping optimized fallback generation for this font.`);
      return void 0;
    }
    const fallback = FALLBACK_METRICS[resolveFallbackCategory(category)];
    const sizeAdjust = xWidthAvg ? xWidthAvg / unitsPerEm / (fallback.xWidthAvg / fallback.unitsPerEm) : 1;
    const adjustedEm = unitsPerEm * sizeAdjust;
    return {
      localFont: fallback.localFont,
      ascentOverride: `${(ascent / adjustedEm * 100).toFixed(2)}%`,
      descentOverride: `${(Math.abs(descent) / adjustedEm * 100).toFixed(2)}%`,
      lineGapOverride: `${(lineGap / adjustedEm * 100).toFixed(2)}%`,
      sizeAdjust: `${(sizeAdjust * 100).toFixed(2)}%`
    };
  } catch (e) {
    warn(`Unable to generate an optimized font fallback from [${fontSource}]: ${e instanceof Error ? e.message : String(e)}`);
    return void 0;
  }
}

// src/fonts/manifest.ts
function variantKey(weight, style) {
  return `${weight}:${style}`;
}
function shouldPreload(family, variant) {
  if (family.preload === false || family.preload === true) {
    return family.preload;
  }
  return family.preload.some(
    (sel) => String(sel.weight) === String(variant.weight) && (sel.style ?? "normal") === variant.style
  );
}
function resolveEntries(families, pathMap, pathKey) {
  const preloads = [];
  const familyEntries = {};
  for (const family of families) {
    const variants = {};
    for (const variant of family.variants) {
      const files = variant.files.map((f) => ({
        [pathKey]: pathMap.get(f.source),
        format: f.format,
        unicodeRange: f.unicodeRange
      }));
      const key = variantKey(variant.weight, variant.style);
      if (variants[key]) {
        variants[key].files.push(...files);
      } else {
        variants[key] = { files };
      }
      if (shouldPreload(family, variant)) {
        for (const f of variant.files) {
          if (f.format === "woff2") {
            preloads.push({
              alias: family.alias,
              family: family.family,
              weight: variant.weight,
              style: variant.style,
              [pathKey]: pathMap.get(f.source),
              as: "font",
              type: FORMAT_MIME[f.format],
              crossorigin: "anonymous"
            });
          }
        }
      }
    }
    familyEntries[family.alias] = {
      family: family.family,
      variable: family.variable,
      fallbackFamily: family.optimizedFallbacks ? `${family.family} fallback` : void 0,
      fallbacks: family.fallbacks.length > 0 ? family.fallbacks : void 0,
      variants
    };
  }
  const seen = /* @__PURE__ */ new Set();
  const deduped = preloads.filter((p) => {
    const key = p.file ?? p.url ?? "";
    if (seen.has(key)) {
      return false;
    }
    seen.add(key);
    return true;
  });
  return { preloads: deduped, familyEntries };
}
function buildManifest(families, cssFile, filePathMap, familyStyles, variables) {
  const { preloads, familyEntries } = resolveEntries(families, filePathMap, "file");
  return {
    version: 1,
    style: { file: cssFile, familyStyles, variables },
    preloads,
    families: familyEntries
  };
}
function buildDevManifest(families, inlineCss, urlMap, familyStyles, variables) {
  const { preloads, familyEntries } = resolveEntries(families, urlMap, "url");
  return {
    version: 1,
    style: { inline: inlineCss, familyStyles, variables },
    preloads,
    families: familyEntries
  };
}

// src/fonts/providers/resolve-fontsource.ts
import fs4 from "fs";
import path3 from "path";
import { createRequire } from "module";

// src/fonts/css-parser.ts
var FORMAT_ALIASES = Object.fromEntries(
  FORMATS.flatMap((format) => [
    [format.type, format.type],
    [format.keyword, format.type]
  ])
);
function parseFontFaceCss(css) {
  const results = [];
  const ruleRegex = /(?:\/\*\s*([\w-]+)\s*\*\/\s*)?@font-face\s*\{([^}]+)\}/g;
  let match;
  while ((match = ruleRegex.exec(css)) !== null) {
    const subset = match[1];
    const block = match[2];
    const face = parseFontFaceBlock(block);
    if (face) {
      results.push(subset ? { ...face, subset } : face);
    }
  }
  return results;
}
function parseFontFaceBlock(block) {
  const family = extractDescriptor(block, "font-family");
  const style = extractDescriptor(block, "font-style");
  const weight = extractDescriptor(block, "font-weight");
  const src = extractDescriptor(block, "src");
  const unicodeRange = extractDescriptor(block, "unicode-range");
  const display = extractDescriptor(block, "font-display");
  if (!family || !src) {
    return null;
  }
  const cleanFamily = family.replace(/['"]/g, "").trim();
  const parsedSrc = parseSrcDescriptor(src);
  if (parsedSrc.length === 0) {
    return null;
  }
  return {
    family: cleanFamily,
    style: style ?? "normal",
    weight: parseWeight(weight ?? "400"),
    src: parsedSrc,
    unicodeRange: unicodeRange ?? void 0,
    display: display ?? void 0
  };
}
function extractDescriptor(block, name) {
  const match = new RegExp(`${name}\\s*:\\s*([^;]+)`, "i").exec(block);
  return match ? match[1].trim() : null;
}
function parseSrcDescriptor(src) {
  const results = [];
  const urlRegex = /url\(["']?([^"')]+)["']?\)\s*format\(["']?([^"')]+)["']?\)/g;
  let match;
  while ((match = urlRegex.exec(src)) !== null) {
    const url = match[1];
    const format = normalizeFormat(match[2]);
    if (format) {
      results.push({ url, format });
    }
  }
  if (results.length === 0) {
    const simpleUrlRegex = /url\(["']?([^"')]+)["']?\)/g;
    while ((match = simpleUrlRegex.exec(src)) !== null) {
      const url = match[1];
      const format = inferFormatFromUrl(url);
      if (format) {
        results.push({ url, format });
      }
    }
  }
  return results;
}
function parseWeight(weight) {
  const trimmed = weight.trim();
  return /^\d+$/.test(trimmed) ? parseInt(trimmed, 10) : trimmed;
}
function normalizeFormat(format) {
  return FORMAT_ALIASES[format.toLowerCase()] ?? null;
}
function inferFormatFromUrl(url) {
  const ext = url.match(/\.([^.]+)$/)?.[1];
  return ext ? normalizeFormat(ext) : null;
}

// src/fonts/providers/resolve-fontsource.ts
function buildCssFilePaths(definition, packageDir, packageName) {
  const paths = [];
  for (const weight of definition.weights) {
    for (const style of definition.styles) {
      const cssFileName = style === "italic" ? `${weight}-italic.css` : `${weight}.css`;
      const cssFilePath = path3.join(packageDir, cssFileName);
      if (!fs4.existsSync(cssFilePath)) {
        throw new Error(
          `laravel-vite-plugin: Fontsource CSS file not found: "${cssFileName}" in package "${packageName}" for font "${definition.family}". Check that weight ${weight} and style "${style}" are available.`
        );
      }
      paths.push({ fileName: cssFileName, filePath: cssFilePath, weight: String(weight), style });
    }
  }
  return paths;
}
function matchesFontsourceSubset(face, subset) {
  const suffix = `-${subset}-${String(face.weight)}-${face.style}`;
  return face.src.some((src) => {
    const stem = path3.basename(src.url).replace(/\.(?:woff2?|ttf|otf|eot)$/i, "");
    return stem.endsWith(suffix);
  });
}
function resolveFontsourceVariants(definition, projectRoot) {
  const packageName = definition._fontsource?.package ?? `@fontsource/${familyToSlug(definition.family)}`;
  let packageDir;
  try {
    const require2 = createRequire(path3.join(projectRoot, "package.json"));
    packageDir = path3.dirname(
      require2.resolve(`${packageName}/package.json`)
    );
  } catch {
    throw new Error(
      `laravel-vite-plugin: Fontsource package "${packageName}" not found. Install it with: npm install ${packageName}`
    );
  }
  const variants = [];
  const cssFilePaths = buildCssFilePaths(definition, packageDir, packageName);
  for (const cssFile of cssFilePaths) {
    const faces = parseFontFaceCss(fs4.readFileSync(cssFile.filePath, "utf-8"));
    const matchedSubsets = /* @__PURE__ */ new Set();
    for (const face of faces) {
      const subset = definition.subsets.find((subset2) => matchesFontsourceSubset(face, subset2));
      if (!subset) {
        continue;
      }
      matchedSubsets.add(subset);
      const files = face.src.map((src) => {
        const absolutePath = path3.resolve(path3.dirname(cssFile.filePath), src.url);
        if (!fs4.existsSync(absolutePath)) {
          throw new Error(
            `laravel-vite-plugin: Font file referenced by Fontsource not found: "${absolutePath}" for font "${definition.family}".`
          );
        }
        return { source: absolutePath, format: src.format, unicodeRange: face.unicodeRange };
      });
      variants.push({ weight: face.weight, style: face.style, files });
    }
    for (const subset of definition.subsets) {
      if (!matchedSubsets.has(subset)) {
        throw new Error(
          `laravel-vite-plugin: Fontsource subset "${subset}" not found in "${cssFile.fileName}" in package "${packageName}" for font "${definition.family}". Check that weight ${cssFile.weight}, style "${cssFile.style}", and subset "${subset}" are available.`
        );
      }
    }
  }
  if (variants.length === 0) {
    throw new Error(
      `laravel-vite-plugin: No font variants resolved from Fontsource package "${packageName}" for font "${definition.family}".`
    );
  }
  return variants;
}
function resolveFontsourceFont(definition, projectRoot) {
  return buildResolvedFamily(definition, resolveFontsourceVariants(definition, projectRoot));
}

// src/fonts/providers/resolve-remote.ts
var WOFF2_USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";
function buildCss2Url(baseUrl, definition) {
  const family = definition.family.replace(/ /g, "+");
  const weights = definition.weights;
  const styles = definition.styles;
  const hasItalic = styles.includes("italic");
  const axes = hasItalic ? ["ital", "wght"] : ["wght"];
  const tuples = /* @__PURE__ */ new Set();
  for (const weight of weights) {
    for (const style of styles) {
      if (hasItalic) {
        const ital = style === "italic" ? "1" : "0";
        tuples.add(`${ital},${weight}`);
      } else {
        tuples.add(`${weight}`);
      }
    }
  }
  const axisStr = axes.join(",");
  const tupleStr = [...tuples].sort().join(";");
  return `${baseUrl}?family=${family}:${axisStr}@${tupleStr}&display=${definition.display}&subset=${definition.subsets.join(",")}`;
}
async function resolveRemoteVariants(definition, cacheDir, baseUrl) {
  const url = buildCss2Url(baseUrl, definition);
  const css = await fetchTextAndCache(url, cacheDir, {
    "User-Agent": WOFF2_USER_AGENT
  });
  const faces = parseFontFaceCss(css);
  if (faces.length === 0) {
    throw new Error(
      `laravel-vite-plugin: ${definition.provider} returned no @font-face rules for "${definition.family}". Check the family name and requested weights/styles.`
    );
  }
  const requestedFaces = faces.filter(
    (face) => !face.subset || definition.subsets.includes(face.subset)
  );
  if (requestedFaces.length === 0) {
    const available = [...new Set(faces.map((face) => face.subset).filter(Boolean))];
    throw new Error(
      `laravel-vite-plugin: ${definition.provider} returned no @font-face rules matching the requested subsets [${definition.subsets.join(", ")}] for "${definition.family}". Available subsets: [${available.join(", ")}].`
    );
  }
  const variants = [];
  for (const face of requestedFaces) {
    const files = [];
    for (const src of face.src) {
      await fetchAndCache(src.url, cacheDir);
      files.push({
        source: `${cacheDir}/${cacheKey(src.url)}`,
        format: src.format,
        unicodeRange: face.unicodeRange
      });
    }
    variants.push({
      weight: face.weight,
      style: face.style,
      files
    });
  }
  return variants;
}
async function resolveRemoteFont(definition, cacheDir, baseUrl) {
  const variants = await resolveRemoteVariants(definition, cacheDir, baseUrl);
  return buildResolvedFamily(definition, variants);
}

// src/fonts/plugin.ts
var REMOTE_CSS_URLS = {
  google: "https://fonts.googleapis.com/css2",
  bunny: "https://fonts.bunny.net/css2"
};
async function resolveFontFamilies(fonts, projectRoot, cacheDir) {
  const families = [];
  for (const definition of fonts) {
    const remoteUrl = REMOTE_CSS_URLS[definition.provider];
    if (remoteUrl) {
      families.push(
        await resolveRemoteFont(definition, cacheDir, remoteUrl)
      );
    }
    switch (definition.provider) {
      case "fontsource":
        families.push(resolveFontsourceFont(definition, projectRoot));
        break;
      case "local":
        families.push(await resolveLocalFont(definition, projectRoot));
        break;
    }
  }
  return families;
}
async function buildFallbackMap(families, warn) {
  const fallbackMap = /* @__PURE__ */ new Map();
  const warned = /* @__PURE__ */ new Set();
  const warnOnce = (message) => {
    if (!warned.has(message)) {
      warned.add(message);
      warn(message);
    }
  };
  for (const family of families) {
    if (!family.optimizedFallbacks) {
      continue;
    }
    const firstFile = family.variants[0]?.files[0];
    if (!firstFile) {
      continue;
    }
    const metrics = await generateFallbackMetrics(firstFile.source, warnOnce);
    if (metrics) {
      fallbackMap.set(family.alias, {
        fallbackFamily: `${family.family} fallback`,
        metrics
      });
    }
  }
  for (const family of families) {
    if (family.optimizedFallbacks && !fallbackMap.has(family.alias)) {
      family.optimizedFallbacks = false;
    }
  }
  return fallbackMap;
}
function buildSourceWeightMap(families) {
  const sourceWeightMap = /* @__PURE__ */ new Map();
  for (const family of families) {
    for (const variant of family.variants) {
      for (const file of variant.files) {
        if (!sourceWeightMap.has(file.source)) {
          sourceWeightMap.set(file.source, /* @__PURE__ */ new Map());
        }
        const styleWeights = sourceWeightMap.get(file.source);
        if (!styleWeights.has(variant.style)) {
          styleWeights.set(variant.style, /* @__PURE__ */ new Set());
        }
        styleWeights.get(variant.style).add(String(variant.weight));
      }
    }
  }
  return sourceWeightMap;
}
function buildFontAssetName(family, variant, file, sourceWeightMap) {
  const slug = familyToSlug(family.family);
  const ext = file.format === "woff2" ? ".woff2" : `.${file.format}`;
  const weight = (sourceWeightMap.get(file.source)?.get(variant.style)?.size ?? 0) > 1 ? "variable" : String(variant.weight).replace(/\s+/g, "-");
  return `${slug}-${weight}-${variant.style}${ext}`;
}
function emitFontAssets(families, emitFile) {
  const fileRefMap = /* @__PURE__ */ new Map();
  const sourceWeightMap = buildSourceWeightMap(families);
  for (const family of families) {
    for (const variant of family.variants) {
      for (const file of variant.files) {
        if (fileRefMap.has(file.source)) {
          continue;
        }
        const source = fs5.readFileSync(file.source);
        const name = buildFontAssetName(family, variant, file, sourceWeightMap);
        const ref = emitFile({ type: "asset", name, source });
        fileRefMap.set(file.source, ref);
      }
    }
  }
  return fileRefMap;
}
function resolvePublicAssetUrl(base, fileName) {
  const normalizedBase = base.endsWith("/") ? base : `${base}/`;
  return `${normalizedBase}${fileName}`;
}
function resolveCssAssetUrl(base, fileName, assetsDir) {
  if (base !== "./" && base !== "") {
    return resolvePublicAssetUrl(base, fileName);
  }
  const relativePath = path4.posix.relative(assetsDir.replace(/\/+$/, ""), fileName);
  return relativePath.startsWith(".") ? relativePath : `./${relativePath}`;
}
function assertFileRefsResolved(families, fileRefMap) {
  for (const family of families) {
    for (const variant of family.variants) {
      for (const file of variant.files) {
        if (!fileRefMap.has(file.source)) {
          throw new Error(
            `laravel-vite-plugin: Missing emitted asset for font "${family.family}" (source "${file.source}").`
          );
        }
      }
    }
  }
}
function resolveFontsPlugin(fonts, hotFile) {
  if (!fonts || fonts.length === 0) {
    return [];
  }
  const mergedFonts = validateFontsConfig(fonts);
  let resolvedConfig;
  let resolvedFamilies = [];
  let cacheDir;
  let hotManifestPath;
  let fontsFileRefMap;
  let fontsFallbackMap;
  return [{
    name: "laravel:fonts",
    enforce: "post",
    configResolved(config) {
      resolvedConfig = config;
      cacheDir = resolveCacheDir(config.root);
      hotManifestPath = path4.resolve(
        path4.dirname(hotFile),
        "fonts-manifest.dev.json"
      );
    },
    async buildStart() {
      if (resolvedConfig.command !== "build") {
        return;
      }
      resolvedFamilies = await resolveFontFamilies(mergedFonts, resolvedConfig.root, cacheDir);
      if (resolvedFamilies.length === 0) {
        return;
      }
      fontsFileRefMap = emitFontAssets(resolvedFamilies, (opts) => this.emitFile(opts));
      fontsFallbackMap = await buildFallbackMap(resolvedFamilies, (message) => this.warn(message));
    },
    generateBundle() {
      if (resolvedConfig.command !== "build" || resolvedFamilies.length === 0) {
        return;
      }
      assertFileRefsResolved(resolvedFamilies, fontsFileRefMap);
      const relativeFilePathMap = /* @__PURE__ */ new Map();
      const cssFilePathMap = /* @__PURE__ */ new Map();
      const publicFilePathMap = /* @__PURE__ */ new Map();
      for (const [source, ref] of fontsFileRefMap) {
        const fileName = this.getFileName(ref);
        relativeFilePathMap.set(source, fileName);
        cssFilePathMap.set(source, resolveCssAssetUrl(resolvedConfig.base, fileName, resolvedConfig.build.assetsDir));
        publicFilePathMap.set(source, resolvePublicAssetUrl(resolvedConfig.base, fileName));
      }
      const finalCss = generateFontCss(resolvedFamilies, cssFilePathMap, fontsFallbackMap);
      const { familyStyles, variables } = generateFamilyStyles(resolvedFamilies, publicFilePathMap, fontsFallbackMap);
      const cssRef = this.emitFile({
        type: "asset",
        name: "fonts.css",
        source: finalCss
      });
      const cssFileName = this.getFileName(cssRef);
      const manifest = buildManifest(resolvedFamilies, cssFileName, relativeFilePathMap, familyStyles, variables);
      this.emitFile({
        type: "asset",
        fileName: "fonts-manifest.json",
        source: JSON.stringify(manifest, null, 2)
      });
    },
    configureServer(server) {
      const projectRoot = resolvedConfig.root;
      const fontMiddleware = createFontMiddleware();
      server.middlewares.use(fontMiddleware.middleware);
      server.httpServer?.once("listening", async () => {
        try {
          resolvedFamilies = await resolveFontFamilies(mergedFonts, projectRoot, cacheDir);
          if (resolvedFamilies.length === 0) {
            return;
          }
          const devServerUrl = fs5.existsSync(hotFile) ? fs5.readFileSync(hotFile, "utf-8").trim() : `http://localhost:${server.config.server.port ?? 5173}`;
          fontMiddleware.update(resolvedFamilies);
          const fallbackMap = await buildFallbackMap(resolvedFamilies, (message) => server.config.logger.warn(`[laravel:fonts] ${message}`));
          const urlMap = buildDevUrlMap(resolvedFamilies, devServerUrl);
          const css = generateFontCss(resolvedFamilies, urlMap, fallbackMap);
          const { familyStyles, variables } = generateFamilyStyles(resolvedFamilies, urlMap, fallbackMap);
          const manifest = buildDevManifest(resolvedFamilies, css, urlMap, familyStyles, variables);
          const hotManifestDir = path4.dirname(hotManifestPath);
          if (!fs5.existsSync(hotManifestDir)) {
            fs5.mkdirSync(hotManifestDir, { recursive: true });
          }
          fs5.writeFileSync(hotManifestPath, JSON.stringify(manifest, null, 2));
        } catch (e) {
          server.config.logger.error(`[laravel:fonts] ${e.message}`);
        }
      });
      const onExit = () => {
        try {
          fs5.rmSync(hotManifestPath, { force: true });
        } catch {
        }
      };
      process.on("exit", onExit);
      server.httpServer?.once("close", () => {
        onExit();
        process.removeListener("exit", onExit);
      });
    }
  }];
}

// src/index.ts
var exitHandlersBound = false;
var refreshPaths = [
  "app/Livewire/**",
  "app/View/Components/**",
  "lang/**",
  "resources/lang/**",
  "resources/views/**",
  "routes/**"
].filter((path6) => fs6.existsSync(path6.replace(/\*\*$/, "")));
var logger = createLogger("info", {
  prefix: "[laravel-vite-plugin]"
});
function laravel(config) {
  const pluginConfig = resolvePluginConfig(config);
  return [
    resolveLaravelPlugin(pluginConfig),
    ...resolveAssetPlugin(pluginConfig.assets),
    ...resolveFontsPlugin(pluginConfig.fonts, pluginConfig.hotFile),
    ...resolveFullReloadConfig(pluginConfig)
  ];
}
function resolveLaravelPlugin(pluginConfig) {
  let viteDevServerUrl;
  let resolvedConfig;
  let userConfig;
  const defaultAliases = {
    "@": "/resources/js"
  };
  return {
    name: "laravel",
    enforce: "post",
    config: (config, { command, mode }) => {
      userConfig = config;
      const ssr = !!userConfig.build?.ssr;
      const env = loadEnv(mode, userConfig.envDir || process.cwd(), "");
      const assetUrl = env.ASSET_URL ?? "";
      const serverConfig = command === "serve" ? resolveDevelopmentEnvironmentServerConfig(pluginConfig.detectTls) ?? resolveEnvironmentServerConfig(env) : void 0;
      ensureCommandShouldRunInEnvironment(command, env);
      return {
        base: userConfig.base ?? (command === "build" ? resolveBase(pluginConfig, assetUrl) : ""),
        publicDir: userConfig.publicDir ?? false,
        build: {
          manifest: userConfig.build?.manifest ?? (ssr ? false : "manifest.json"),
          ssrManifest: userConfig.build?.ssrManifest ?? (ssr ? "ssr-manifest.json" : false),
          outDir: userConfig.build?.outDir ?? resolveOutDir(pluginConfig, ssr),
          rolldownOptions: {
            input: userConfig.build?.rolldownOptions?.input ?? userConfig.build?.rollupOptions?.input ?? resolveInput(pluginConfig, ssr)
          },
          assetsInlineLimit: userConfig.build?.assetsInlineLimit ?? 0
        },
        server: {
          origin: userConfig.server?.origin ?? "http://__laravel_vite_placeholder__.test",
          cors: userConfig.server?.cors ?? {
            origin: userConfig.server?.origin ?? [
              defaultAllowedOrigins,
              ...env.APP_URL ? [env.APP_URL] : [],
              // *               (APP_URL="http://my-app.tld")
              /^https?:\/\/.*\.test(:\d+)?$/
              // Valet / Herd    (SCHEME://*.test:PORT)
            ]
          },
          ...process.env.LARAVEL_SAIL ? {
            host: userConfig.server?.host ?? "0.0.0.0",
            port: userConfig.server?.port ?? (env.VITE_PORT ? parseInt(env.VITE_PORT) : 5173),
            strictPort: userConfig.server?.strictPort ?? true
          } : void 0,
          ...serverConfig ? {
            host: userConfig.server?.host ?? serverConfig.host,
            hmr: userConfig.server?.hmr === false ? false : {
              ...serverConfig.hmr,
              ...userConfig.server?.hmr === true ? {} : userConfig.server?.hmr
            },
            https: userConfig.server?.https ?? serverConfig.https
          } : void 0
        },
        resolve: {
          alias: Array.isArray(userConfig.resolve?.alias) ? [
            ...userConfig.resolve?.alias ?? [],
            ...Object.keys(defaultAliases).map((alias) => ({
              find: alias,
              replacement: defaultAliases[alias]
            }))
          ] : {
            ...defaultAliases,
            ...userConfig.resolve?.alias
          }
        },
        ssr: {
          noExternal: noExternalInertiaHelpers(userConfig)
        }
      };
    },
    configResolved(config) {
      resolvedConfig = config;
    },
    transform(code) {
      if (resolvedConfig.command === "serve") {
        code = code.replace(/http:\/\/__laravel_vite_placeholder__\.test/g, viteDevServerUrl);
        return pluginConfig.transformOnServe(code, viteDevServerUrl);
      }
    },
    configureServer(server) {
      if (process.env.VITEST !== void 0) {
        return;
      }
      const envDir = resolvedConfig.envDir || process.cwd();
      const appUrl = loadEnv(resolvedConfig.mode, envDir, "APP_URL").APP_URL ?? "undefined";
      server.httpServer?.once("listening", () => {
        const address = server.httpServer?.address();
        const isAddressInfo = (x) => typeof x === "object" && x !== null;
        if (isAddressInfo(address)) {
          viteDevServerUrl = userConfig.server?.origin ? userConfig.server.origin : resolveDevServerUrl(address, server.config, userConfig);
          const hotFileParentDirectory = path5.dirname(pluginConfig.hotFile);
          if (!fs6.existsSync(hotFileParentDirectory)) {
            fs6.mkdirSync(hotFileParentDirectory, { recursive: true });
            setTimeout(() => {
              logger.info(`Hot file directory created ${colors.dim(fs6.realpathSync(hotFileParentDirectory))}`, { clear: true, timestamp: true });
            }, 200);
          }
          fs6.writeFileSync(pluginConfig.hotFile, `${viteDevServerUrl}${server.config.base.replace(/\/$/, "")}`);
          setTimeout(() => {
            server.config.logger.info(`
  ${colors.red(`${colors.bold("LARAVEL")} ${laravelVersion()}`)}  ${colors.dim("plugin")} ${colors.bold(`v${pluginVersion()}`)}`);
            server.config.logger.info("");
            server.config.logger.info(`  ${colors.green("\u279C")}  ${colors.bold("APP_URL")}: ${colors.cyan(appUrl.replace(/:(\d+)/, (_, port) => `:${colors.bold(port)}`))}`);
            if (typeof resolvedConfig.server.https === "object" && typeof resolvedConfig.server.https.key === "string") {
              if (resolvedConfig.server.https.key.startsWith(herdMacConfigPath()) || resolvedConfig.server.https.key.startsWith(herdWindowsConfigPath())) {
                server.config.logger.info(`  ${colors.green("\u279C")}  Using Herd certificate to secure Vite.`);
              }
              if (resolvedConfig.server.https.key.startsWith(valetMacConfigPath()) || resolvedConfig.server.https.key.startsWith(valetLinuxConfigPath())) {
                server.config.logger.info(`  ${colors.green("\u279C")}  Using Valet certificate to secure Vite.`);
              }
            }
          }, 100);
        }
      });
      if (!exitHandlersBound) {
        const clean = () => {
          if (fs6.existsSync(pluginConfig.hotFile)) {
            fs6.rmSync(pluginConfig.hotFile);
          }
        };
        process.on("exit", clean);
        process.on("SIGINT", () => process.exit());
        process.on("SIGTERM", () => process.exit());
        process.on("SIGHUP", () => process.exit());
        exitHandlersBound = true;
      }
      return () => server.middlewares.use((req, res, next) => {
        if (req.url === "/index.html") {
          res.statusCode = 404;
          res.end(
            fs6.readFileSync(path5.join(dirname(), "dev-server-index.html")).toString().replace(/{{ APP_URL }}/g, appUrl)
          );
        }
        next();
      });
    }
  };
}
function ensureCommandShouldRunInEnvironment(command, env) {
  if (command === "build" || env.LARAVEL_BYPASS_ENV_CHECK === "1") {
    return;
  }
  if (typeof env.LARAVEL_VAPOR !== "undefined") {
    throw Error("You should not run the Vite HMR server on Vapor. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1");
  }
  if (typeof env.LARAVEL_FORGE !== "undefined") {
    throw Error("You should not run the Vite HMR server in your Forge deployment script. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1");
  }
  if (typeof env.LARAVEL_ENVOYER !== "undefined") {
    throw Error("You should not run the Vite HMR server in your Envoyer hook. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1");
  }
  if (typeof env.CI !== "undefined") {
    throw Error("You should not run the Vite HMR server in CI environments. You should build your assets for production instead. To disable this ENV check you may set LARAVEL_BYPASS_ENV_CHECK=1");
  }
}
function laravelVersion() {
  try {
    const composer = JSON.parse(fs6.readFileSync("composer.lock").toString());
    return composer.packages?.find((composerPackage) => composerPackage.name === "laravel/framework")?.version ?? "";
  } catch {
    return "";
  }
}
function pluginVersion() {
  try {
    return JSON.parse(fs6.readFileSync(path5.join(dirname(), "../package.json")).toString())?.version;
  } catch {
    return "";
  }
}
function resolvePluginConfig(config) {
  if (typeof config === "undefined") {
    throw new Error("laravel-vite-plugin: missing configuration.");
  }
  if (typeof config === "string" || Array.isArray(config)) {
    config = { input: config, ssr: config };
  }
  if (typeof config.input === "undefined") {
    throw new Error('laravel-vite-plugin: missing configuration for "input".');
  }
  if (typeof config.publicDirectory === "string") {
    config.publicDirectory = config.publicDirectory.trim().replace(/^\/+/, "");
    if (config.publicDirectory === "") {
      throw new Error("laravel-vite-plugin: publicDirectory must be a subdirectory. E.g. 'public'.");
    }
  }
  if (typeof config.buildDirectory === "string") {
    config.buildDirectory = config.buildDirectory.trim().replace(/^\/+/, "").replace(/\/+$/, "");
    if (config.buildDirectory === "") {
      throw new Error("laravel-vite-plugin: buildDirectory must be a subdirectory. E.g. 'build'.");
    }
  }
  if (typeof config.ssrOutputDirectory === "string") {
    config.ssrOutputDirectory = config.ssrOutputDirectory.trim().replace(/^\/+/, "").replace(/\/+$/, "");
  }
  if (config.refresh === true) {
    config.refresh = [{ paths: refreshPaths }];
  }
  return {
    input: config.input,
    publicDirectory: config.publicDirectory ?? "public",
    buildDirectory: config.buildDirectory ?? "build",
    ssr: config.ssr ?? config.input,
    ssrOutputDirectory: config.ssrOutputDirectory ?? "bootstrap/ssr",
    refresh: config.refresh ?? false,
    hotFile: config.hotFile ?? path5.join(config.publicDirectory ?? "public", "hot"),
    valetTls: config.valetTls ?? null,
    detectTls: config.detectTls ?? config.valetTls ?? null,
    transformOnServe: config.transformOnServe ?? ((code) => code),
    assets: typeof config.assets === "string" ? [config.assets] : config.assets ?? [],
    fonts: config.fonts ?? []
  };
}
function resolveBase(config, assetUrl) {
  return assetUrl + (!assetUrl.endsWith("/") ? "/" : "") + config.buildDirectory + "/";
}
function resolveInput(config, ssr) {
  if (ssr) {
    return config.ssr;
  }
  return config.input;
}
function resolveOutDir(config, ssr) {
  if (ssr) {
    return config.ssrOutputDirectory;
  }
  return path5.join(config.publicDirectory, config.buildDirectory);
}
function resolveAssetPlugin(assets) {
  if (assets.length === 0) {
    return [];
  }
  return [{
    name: "laravel:assets",
    apply: "build",
    buildStart() {
      for (const file of globSync(assets)) {
        if (fs6.statSync(file).isFile()) {
          this.emitFile({ type: "asset", name: path5.basename(file), originalFileName: file, source: fs6.readFileSync(file) });
        }
      }
    }
  }];
}
function resolveFullReloadConfig({ refresh: config }) {
  if (typeof config === "boolean") {
    return [];
  }
  if (typeof config === "string") {
    config = [{ paths: [config] }];
  }
  if (!Array.isArray(config)) {
    config = [config];
  }
  if (config.some((c) => typeof c === "string")) {
    config = [{ paths: config }];
  }
  return config.flatMap((c) => {
    const plugin = fullReload(c.paths, c.config);
    plugin.__laravel_plugin_config = c;
    return plugin;
  });
}
function resolveDevServerUrl(address, config, userConfig) {
  const configHmrProtocol = typeof config.server.hmr === "object" ? config.server.hmr.protocol : null;
  const clientProtocol = configHmrProtocol ? configHmrProtocol === "wss" ? "https" : "http" : null;
  const serverProtocol = config.server.https ? "https" : "http";
  const protocol = clientProtocol ?? serverProtocol;
  const configHmrHost = typeof config.server.hmr === "object" ? config.server.hmr.host : null;
  const configHost = typeof config.server.host === "string" ? config.server.host : null;
  const sailHost = process.env.LARAVEL_SAIL && !userConfig.server?.host ? "localhost" : null;
  const serverAddress = isIpv6(address) ? `[${address.address}]` : address.address;
  const host = configHmrHost ?? sailHost ?? configHost ?? serverAddress;
  const configHmrClientPort = typeof config.server.hmr === "object" ? config.server.hmr.clientPort : null;
  const port = configHmrClientPort ?? address.port;
  return `${protocol}://${host}:${port}`;
}
function isIpv6(address) {
  return address.family === "IPv6" || address.family === 6;
}
function noExternalInertiaHelpers(config) {
  const userNoExternal = config.ssr?.noExternal;
  const pluginNoExternal = ["laravel-vite-plugin"];
  if (userNoExternal === true) {
    return true;
  }
  if (typeof userNoExternal === "undefined") {
    return pluginNoExternal;
  }
  return [
    ...Array.isArray(userNoExternal) ? userNoExternal : [userNoExternal],
    ...pluginNoExternal
  ];
}
function resolveEnvironmentServerConfig(env) {
  if (!env.VITE_DEV_SERVER_KEY && !env.VITE_DEV_SERVER_CERT) {
    return;
  }
  if (!fs6.existsSync(env.VITE_DEV_SERVER_KEY) || !fs6.existsSync(env.VITE_DEV_SERVER_CERT)) {
    throw Error(`Unable to find the certificate files specified in your environment. Ensure you have correctly configured VITE_DEV_SERVER_KEY: [${env.VITE_DEV_SERVER_KEY}] and VITE_DEV_SERVER_CERT: [${env.VITE_DEV_SERVER_CERT}].`);
  }
  const host = resolveHostFromEnv(env);
  if (!host) {
    throw Error(`Unable to determine the host from the environment's APP_URL: [${env.APP_URL}].`);
  }
  return {
    hmr: { host },
    host,
    https: {
      key: fs6.readFileSync(env.VITE_DEV_SERVER_KEY),
      cert: fs6.readFileSync(env.VITE_DEV_SERVER_CERT)
    }
  };
}
function resolveHostFromEnv(env) {
  try {
    return new URL(env.APP_URL).host;
  } catch {
    return;
  }
}
function resolveDevelopmentEnvironmentServerConfig(host) {
  if (host === false) {
    return;
  }
  const configPath = determineDevelopmentEnvironmentConfigPath();
  if (typeof configPath === "undefined" && host === null) {
    return;
  }
  if (typeof configPath === "undefined") {
    throw Error(`Unable to find the Herd or Valet configuration directory. Please check they are correctly installed.`);
  }
  const resolvedHost = host === true || host === null ? path5.basename(process.cwd()) + "." + resolveDevelopmentEnvironmentTld(configPath) : host;
  const keyPath = path5.resolve(configPath, "Certificates", `${resolvedHost}.key`);
  const certPath = path5.resolve(configPath, "Certificates", `${resolvedHost}.crt`);
  if (!fs6.existsSync(keyPath) || !fs6.existsSync(certPath)) {
    if (host === null) {
      return;
    }
    if (configPath === herdMacConfigPath() || configPath === herdWindowsConfigPath()) {
      throw Error(`Unable to find certificate files for your host [${resolvedHost}] in the [${configPath}/Certificates] directory. Ensure you have secured the site via the Herd UI.`);
    } else if (typeof host === "string") {
      throw Error(`Unable to find certificate files for your host [${resolvedHost}] in the [${configPath}/Certificates] directory. Ensure you have secured the site by running \`valet secure ${host}\`.`);
    } else {
      throw Error(`Unable to find certificate files for your host [${resolvedHost}] in the [${configPath}/Certificates] directory. Ensure you have secured the site by running \`valet secure\`.`);
    }
  }
  return {
    hmr: { host: resolvedHost },
    host: resolvedHost,
    https: {
      key: keyPath,
      cert: certPath
    }
  };
}
function determineDevelopmentEnvironmentConfigPath() {
  if (fs6.existsSync(herdMacConfigPath())) {
    return herdMacConfigPath();
  }
  if (fs6.existsSync(herdWindowsConfigPath())) {
    return herdWindowsConfigPath();
  }
  if (fs6.existsSync(valetMacConfigPath())) {
    return valetMacConfigPath();
  }
  if (fs6.existsSync(valetLinuxConfigPath())) {
    return valetLinuxConfigPath();
  }
}
function resolveDevelopmentEnvironmentTld(configPath) {
  const configFile = path5.resolve(configPath, "config.json");
  if (!fs6.existsSync(configFile)) {
    throw Error(`Unable to find the configuration file [${configFile}].`);
  }
  const config = JSON.parse(fs6.readFileSync(configFile, "utf-8"));
  return config.tld;
}
function dirname() {
  return fileURLToPath(new URL(".", import.meta.url));
}
function herdMacConfigPath() {
  return path5.resolve(os.homedir(), "Library", "Application Support", "Herd", "config", "valet");
}
function herdWindowsConfigPath() {
  return path5.resolve(os.homedir(), ".config", "herd", "config", "valet");
}
function valetMacConfigPath() {
  return path5.resolve(os.homedir(), ".config", "valet");
}
function valetLinuxConfigPath() {
  return path5.resolve(os.homedir(), ".valet");
}
export {
  laravel as default,
  refreshPaths
};
