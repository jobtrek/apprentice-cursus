const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/shared/useComposing.ts
const imeScriptRE = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Hangul}\p{Script=Bopomofo}]/u;
const androidRE = /android/i;
function isAndroid() {
	return typeof navigator !== "undefined" && androidRE.test(navigator.userAgent);
}
function useComposing(onEnd) {
	const isComposing = (0, vue.ref)(false);
	const isImeComposition = (0, vue.ref)(true);
	const sawImeScript = (0, vue.ref)(false);
	const shouldDeferInput = (0, vue.computed)(() => isComposing.value && isImeComposition.value);
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
		(0, vue.nextTick)(() => {
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
Object.defineProperty(exports, 'useComposing', {
  enumerable: true,
  get: function () {
    return useComposing;
  }
});
//# sourceMappingURL=useComposing.cjs.map