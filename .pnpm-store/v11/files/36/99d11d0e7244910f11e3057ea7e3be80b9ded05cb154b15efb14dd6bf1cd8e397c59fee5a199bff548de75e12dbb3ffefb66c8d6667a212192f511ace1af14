import { provideListboxHighlightScrollContext } from "../Listbox/ListboxRoot.js";
import { computed, ref } from "vue";

//#region src/Combobox/useComboboxContentPositioning.ts
function useComboboxContentPositioning(open) {
	const contentPosition = ref("inline");
	const contentPlaced = ref(false);
	const currentContent = ref();
	const suppressHighlightScroll = computed(() => contentPosition.value === "popper" && !contentPlaced.value);
	let pendingHighlightScroll;
	provideListboxHighlightScrollContext({
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
export { useComboboxContentPositioning };
//# sourceMappingURL=useComboboxContentPositioning.js.map