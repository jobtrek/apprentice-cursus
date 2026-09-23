// eslint.config.js

import eslintPluginBetterTailwindcss from "eslint-plugin-better-tailwindcss";
import { defineConfig } from "eslint/config";
import eslintParserVue from "vue-eslint-parser";
import tsParser from "@typescript-eslint/parser";

export default defineConfig([
  // global ignores — must be its own object with ONLY the `ignores` key
  {
    ignores: ["vendor/**", "node_modules/**", "storage/**", "bootstrap/cache/**"],
  },

  {
    files: ["**/*.vue"], // .ts handled by OXLint

    extends: [
      eslintPluginBetterTailwindcss.configs.recommended
    ],

    settings: {
      "better-tailwindcss": {
        // adjust to your real entry point, e.g. "resources/css/app.css"
        entryPoint: "resources/css/app.css",
        // tailwindConfig: "tailwind.config.js" // only if you're on Tailwind v3
      }
    },

    languageOptions: {
      parser: eslintParserVue,
      parserOptions: {
        parser: tsParser,
        ecmaVersion: "latest",
        sourceType: "module",
        extraFileExtensions: [".vue"]
      }
    }
  }
]);
