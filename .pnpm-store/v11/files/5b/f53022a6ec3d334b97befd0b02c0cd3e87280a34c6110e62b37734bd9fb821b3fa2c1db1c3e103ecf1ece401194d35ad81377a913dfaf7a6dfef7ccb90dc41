import { selectorEngine, getByTitleSelector, getByTextSelector, getByPlaceholderSelector, getByAltTextSelector, getByTestIdSelector, getByRoleSelector, getByLabelSelector, Locator, convertElementToCssSelector } from '@vitest/browser/locators';
import { page, server, utils, userEvent } from 'vitest/browser';
import { __INTERNAL } from 'vitest/internal/browser';

class PreviewLocator extends Locator {
	constructor(_pwSelector, _container) {
		super();
		this._pwSelector = _pwSelector;
		this._container = _container;
	}
	get selector() {
		const selectors = this.elements().map((element) => convertElementToCssSelector(element));
		if (!selectors.length) {
			throw utils.getElementError(this._pwSelector, this._container || document.body);
		}
		return selectors.join(", ");
	}
	async click(options) {
		const element = await this.findElement(options);
		return userEvent.click(element);
	}
	async dblClick(options) {
		const element = await this.findElement(options);
		return userEvent.dblClick(element);
	}
	async tripleClick(options) {
		const element = await this.findElement(options);
		return userEvent.tripleClick(element);
	}
	async hover(options) {
		const element = await this.findElement(options);
		return userEvent.hover(element);
	}
	async unhover(options) {
		const element = await this.findElement(options);
		return userEvent.unhover(element);
	}
	async fill(text, options) {
		const element = await this.findElement(options);
		return userEvent.fill(element, text);
	}
	async upload(file, options) {
		const element = await this.findElement(options);
		return userEvent.upload(element, file);
	}
	async wheel(options) {
		const element = await this.findElement(options);
		return userEvent.wheel(element, options);
	}
	async selectOptions(options, settings) {
		const element = await this.findElement(settings);
		return userEvent.selectOptions(element, options);
	}
	async clear(options) {
		const element = await this.findElement(options);
		return userEvent.clear(element);
	}
	locator(selector) {
		return new PreviewLocator(`${this._pwSelector} >> ${selector}`, this._container);
	}
	elementLocator(element) {
		return new PreviewLocator(selectorEngine.generateSelectorSimple(element), element);
	}
}
page.extend({
	getByLabelText(text, options) {
		return new PreviewLocator(getByLabelSelector(text, options));
	},
	getByRole(role, options) {
		return new PreviewLocator(getByRoleSelector(role, options));
	},
	getByTestId(testId) {
		return new PreviewLocator(getByTestIdSelector(server.config.browser.locators.testIdAttribute, testId));
	},
	getByAltText(text, options) {
		return new PreviewLocator(getByAltTextSelector(text, options));
	},
	getByPlaceholder(text, options) {
		return new PreviewLocator(getByPlaceholderSelector(text, options));
	},
	getByText(text, options) {
		return new PreviewLocator(getByTextSelector(text, options));
	},
	getByTitle(title, options) {
		return new PreviewLocator(getByTitleSelector(title, options));
	},
	elementLocator(element) {
		return new PreviewLocator(selectorEngine.generateSelectorSimple(element), element);
	}
});
__INTERNAL._createLocator = (selector) => new PreviewLocator(selector);
