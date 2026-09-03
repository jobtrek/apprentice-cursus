import { Plugin, UserConfig, ConfigEnv, Rolldown } from 'vite';
import { Config as FullReloadConfig } from 'vite-plugin-full-reload';
import type { FontDefinition } from './fonts/types.js';
interface PluginConfig {
    /**
     * The path or paths of the entry points to compile.
     */
    input: Rolldown.InputOption;
    /**
     * Laravel's public directory.
     *
     * @default 'public'
     */
    publicDirectory?: string;
    /**
     * The public subdirectory where compiled assets should be written.
     *
     * @default 'build'
     */
    buildDirectory?: string;
    /**
     * The path to the "hot" file.
     *
     * @default `${publicDirectory}/hot`
     */
    hotFile?: string;
    /**
     * The path of the SSR entry point.
     */
    ssr?: Rolldown.InputOption;
    /**
     * The directory where the SSR bundle should be written.
     *
     * @default 'bootstrap/ssr'
     */
    ssrOutputDirectory?: string;
    /**
     * Configuration for performing full page refresh on blade (or other) file changes.
     *
     * {@link https://github.com/ElMassimo/vite-plugin-full-reload}
     * @default false
     */
    refresh?: boolean | string | string[] | RefreshConfig | RefreshConfig[];
    /**
     * Utilise the Herd or Valet TLS certificates.
     *
     * @default null
     */
    detectTls?: string | boolean | null;
    /**
     * Utilise the Herd or Valet TLS certificates.
     *
     * @default null
     * @deprecated use "detectTls" instead
     */
    valetTls?: string | boolean | null;
    /**
     * Transform the code while serving.
     */
    transformOnServe?: (code: string, url: DevServerUrl) => string;
    /**
     * Asset file glob patterns to include in the build.
     *
     * Files matching these patterns will be processed and versioned by Vite,
     * even if they are not imported in your JavaScript. This is useful for
     * assets referenced in Blade templates via `Vite::asset()`.
     *
     * @default []
     */
    assets?: string | string[];
    /**
     * Font configurations for automatic self-hosting and optimization.
     *
     * Use provider helpers from `laravel-vite-plugin/fonts`:
     * `local()`, `google()`, `bunny()`, `fontsource()`.
     *
     * @default []
     */
    fonts?: FontDefinition[];
}
interface RefreshConfig {
    paths: string[];
    config?: FullReloadConfig;
}
interface LaravelPlugin extends Plugin {
    config: (config: UserConfig, env: ConfigEnv) => UserConfig;
}
type DevServerUrl = `${'http' | 'https'}://${string}:${number}`;
export declare const refreshPaths: string[];
/**
 * Laravel plugin for Vite.
 *
 * @param config - A config object or relative path(s) of the scripts to be compiled.
 */
export default function laravel(config: string | string[] | PluginConfig): [LaravelPlugin, ...Plugin[]];
export {};
