import { SelectorOptions } from 'vitest/browser';
import { BrowserProvider, TestProject, BrowserProviderOption } from 'vitest/node';
export { defineBrowserCommand } from '@vitest/browser';

declare function preview(): BrowserProviderOption;
declare class PreviewBrowserProvider implements BrowserProvider {
	name: "preview";
	supportsParallelism: boolean;
	private project;
	private open;
	distRoot: string;
	initScripts: string[];
	constructor(project: TestProject);
	isOpen(): boolean;
	getCommandsContext(): {};
	openPage(_sessionId: string, url: string): Promise<void>;
	close(): Promise<void>;
}
declare module "vitest/browser" {
	interface UserEventClickOptions extends SelectorOptions {}
	interface UserEventHoverOptions extends SelectorOptions {}
	interface UserEventFillOptions extends SelectorOptions {}
	interface UserEventSelectOptions extends SelectorOptions {}
	interface UserEventClearOptions extends SelectorOptions {}
	interface UserEventDoubleClickOptions extends SelectorOptions {}
	interface UserEventTripleClickOptions extends SelectorOptions {}
	interface UserEventUploadOptions extends SelectorOptions {}
	interface UserEventWheelBaseOptions extends SelectorOptions {}
	interface LocatorScreenshotOptions extends SelectorOptions {}
}

export { PreviewBrowserProvider, preview };
