// src/fonts/config.ts
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
var SUPPORTED_EXTENSIONS = FORMATS.map((f) => f.extension);
var SUPPORTED_GLOB = `*.{${SUPPORTED_EXTENSIONS.map((ext) => ext.slice(1)).join(",")}}`;
function familyToSlug(family) {
  return family.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
}
function aliasToVariable(alias) {
  return "--font-" + alias;
}
function buildFontDefinition(family, provider, options, extra) {
  const alias = options?.alias ?? familyToSlug(family);
  return {
    family,
    alias,
    provider,
    variable: options?.variable ?? aliasToVariable(alias),
    weights: options?.weights ? [...options.weights] : [400],
    styles: options?.styles ?? ["normal"],
    subsets: options?.subsets ?? ["latin"],
    display: options?.display ?? "swap",
    preload: options?.preload ?? true,
    fallbacks: options?.fallbacks ?? [],
    optimizedFallbacks: options?.optimizedFallbacks ?? true,
    ...extra
  };
}

// src/fonts/providers/providers.ts
function google(family, options) {
  return buildFontDefinition(family, "google", options);
}
function bunny(family, options) {
  return buildFontDefinition(family, "bunny", options);
}
function fontsource(family, options) {
  return buildFontDefinition(family, "fontsource", options, {
    _fontsource: { package: options?.package }
  });
}
function local(family, options) {
  const _local = "src" in options && options.src !== void 0 ? { src: options.src } : { variants: options.variants };
  return buildFontDefinition(family, "local", options, {
    weights: [],
    styles: [],
    subsets: [],
    _local
  });
}
export {
  bunny,
  fontsource,
  google,
  local
};
