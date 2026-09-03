import { computed, nextTick, ref } from "vue";

//#region src/shared/useComposing.ts
const imeScriptRE = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Hangul}\p{Script=Bopomofo}]/u;
const androidRE = /android/i;
function isAndroid() {
	return typeof navigator !== "undefined" && androidRE.test(navigator.userAgent);
}
function useComposing(onEnd) {
	const isComposing = ref(false);
	const isImeComposition = ref(true);
	const sawImeScript = ref(false);
	const shouldDeferInput = computed(() => isComposing.value && isImeComposition.value);
	function handleCompositionStart() {
		isComposing.value = true;
		isImeComposition.value = true;
		sawImeScript.value = false;
	}
	function handleCompositionUpdate(event) {
		if (!event.data) return;
		if (imeScriptRE.test(event.data)) {
			isImeComposition.value = true;
			sawImeScript.value = true;
		} else if (isAndroid() && !sawImeScript.value) isImeComposition.value = false;
	}
	function handleCompositionEnd(event) {
		nextTick(() => {
			isComposing.value = false;
			onEnd?.(event);
		});
	}
	return {
		isComposing,
		shouldDeferInput,
		handleCompositionStart,
		handleCompositionUpdate,
		handleCompositionEnd
	};
}

//#endregion
export { useComposing };
//# sourceMappingURL=useComposing.js.map