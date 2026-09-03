import { nextTick } from 'node:process';
import { defineBrowserProvider } from '@vitest/browser';
export { defineBrowserCommand } from '@vitest/browser';
import { fileURLToPath } from 'node:url';

const _DRIVE_LETTER_START_RE = /^[A-Za-z]:\//;
function normalizeWindowsPath(input = "") {
  if (!input) {
    return input;
  }
  return input.replace(/\\/g, "/").replace(_DRIVE_LETTER_START_RE, (r) => r.toUpperCase());
}
const _IS_ABSOLUTE_RE = /^[/\\](?![/\\])|^[/\\]{2}(?!\.)|^[A-Za-z]:[/\\]/;
function cwd() {
  if (typeof process !== "undefined" && typeof process.cwd === "function") {
    return process.cwd().replace(/\\/g, "/");
  }
  return "/";
}
const resolve = function(...arguments_) {
  arguments_ = arguments_.map((argument) => normalizeWindowsPath(argument));
  let resolvedPath = "";
  let resolvedAbsolute = false;
  for (let index = arguments_.length - 1; index >= -1 && !resolvedAbsolute; index--) {
    const path = index >= 0 ? arguments_[index] : cwd();
    if (!path || path.length === 0) {
      continue;
    }
    resolvedPath = `${path}/${resolvedPath}`;
    resolvedAbsolute = isAbsolute(path);
  }
  resolvedPath = normalizeString(resolvedPath, !resolvedAbsolute);
  if (resolvedAbsolute && !isAbsolute(resolvedPath)) {
    return `/${resolvedPath}`;
  }
  return resolvedPath.length > 0 ? resolvedPath : ".";
};
function normalizeString(path, allowAboveRoot) {
  let res = "";
  let lastSegmentLength = 0;
  let lastSlash = -1;
  let dots = 0;
  let char = null;
  for (let index = 0; index <= path.length; ++index) {
    if (index < path.length) {
      char = path[index];
    } else if (char === "/") {
      break;
    } else {
      char = "/";
    }
    if (char === "/") {
      if (lastSlash === index - 1 || dots === 1) ; else if (dots === 2) {
        if (res.length < 2 || lastSegmentLength !== 2 || res[res.length - 1] !== "." || res[res.length - 2] !== ".") {
          if (res.length > 2) {
            const lastSlashIndex = res.lastIndexOf("/");
            if (lastSlashIndex === -1) {
              res = "";
              lastSegmentLength = 0;
            } else {
              res = res.slice(0, lastSlashIndex);
              lastSegmentLength = res.length - 1 - res.lastIndexOf("/");
            }
            lastSlash = index;
            dots = 0;
            continue;
          } else if (res.length > 0) {
            res = "";
            lastSegmentLength = 0;
            lastSlash = index;
            dots = 0;
            continue;
          }
        }
        if (allowAboveRoot) {
          res += res.length > 0 ? "/.." : "..";
          lastSegmentLength = 2;
        }
      } else {
        if (res.length > 0) {
          res += `/${path.slice(lastSlash + 1, index)}`;
        } else {
          res = path.slice(lastSlash + 1, index);
        }
        lastSegmentLength = index - lastSlash - 1;
      }
      lastSlash = index;
      dots = 0;
    } else if (char === "." && dots !== -1) {
      ++dots;
    } else {
      dots = -1;
    }
  }
  return res;
}
const isAbsolute = function(p) {
  return _IS_ABSOLUTE_RE.test(p);
};

const pkgRoot = resolve(fileURLToPath(import.meta.url), "../..");
const distRoot = resolve(pkgRoot, "dist");

function preview() {
	return defineBrowserProvider({
		name: "preview",
		providerFactory(project) {
			return new PreviewBrowserProvider(project);
		}
	});
}
class PreviewBrowserProvider {
	name = "preview";
	supportsParallelism = false;
	project;
	open = false;
	distRoot = distRoot;
	initScripts = [resolve(distRoot, "locators.js")];
	constructor(project) {
		this.project = project;
		this.open = false;
		if (project.config.browser.headless) {
			throw new Error("You've enabled headless mode for \"preview\" provider but it doesn't support it. Use \"playwright\" or \"webdriverio\" instead: https://vitest.dev/guide/browser/#configuration");
		}
		nextTick(() => {
			project.vitest.logger.printBrowserBanner(project);
		});
	}
	isOpen() {
		return this.open;
	}
	getCommandsContext() {
		return {};
	}
	async openPage(_sessionId, url) {
		this.open = true;
		if (!this.project.browser) {
			throw new Error("Browser is not initialized");
		}
		const options = this.project.browser.vite.config.server;
		const _open = options.open;
		options.open = url;
		this.project.browser.vite.openBrowser();
		options.open = _open;
	}
	async close() {}
}

export { PreviewBrowserProvider, preview };
