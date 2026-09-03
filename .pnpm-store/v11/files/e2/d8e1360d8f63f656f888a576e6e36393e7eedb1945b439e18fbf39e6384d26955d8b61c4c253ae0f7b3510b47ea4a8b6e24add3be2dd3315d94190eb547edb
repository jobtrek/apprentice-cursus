const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_Listbox_ListboxRoot = require('../Listbox/ListboxRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Combobox/useComboboxContentPositioning.ts
function useComboboxContentPositioning(open) {
	const contentPosition = (0, vue.ref)("inline");
	const contentPlaced = (0, vue.ref)(false);
	const currentContent = (0, vue.ref)();
	const suppressHighlightScroll = (0, vue.computed)(() => contentPosition.value === "popper" && !contentPlaced.value);
	let pendingHighlightScroll;
	require_Listbox_ListboxRoot.provideListboxHighlightScrollContext({
		suppressHighlightScroll,
		onHighlightScrollRequest(scroll) {
			pendingHighlightScroll = scroll;
		}
	});
	function onContentPositionChange(content, position) {
		if (currentContent.value !== content || contentPosition.value !== position) {
			contentPlaced.value = false;
			pendingHighlightScroll = void 0;
		}
		currentContent.value = content;
		contentPosition.value = position;
	}
	function onContentPlaced(content) {
		if (currentContent.value !== content || contentPosition.value !== "popper" || contentPlaced.value) return;
		contentPlaced.value = true;
		const scroll = pendingHighlightScroll;
		pendingHighlightScroll = void 0;
		if (open.value) scroll?.();
	}
	function onContentUnmount(content) {
		if (currentContent.value !== content) return;
		currentContent.value = void 0;
		contentPosition.value = "inline";
		contentPlaced.value = false;
		pendingHighlightScroll = void 0;
	}
	return {
		onContentPositionChange,
		onContentPlaced,
		onContentUnmount
	};
}

//#endregion
Object.defineProperty(exports, 'useComboboxContentPositioning', {
  enumerable: true,
  get: function () {
    return useComboboxContentPositioning;
  }
});
//# sourceMappingURL=useComboboxContentPositioning.cjs.map