import { useEmitAsProps } from "./useEmitAsProps.js";
import { useForwardProps } from "./useForwardProps.js";
import { computed } from "vue";

//#region src/shared/useForwardPropsEmits.ts
function useForwardPropsEmits(props, emit) {
	const parsedProps = useForwardProps(props);
	const emitsAsProps = emit ? useEmitAsProps(emit) : {};
	return computed(() => ({
		...parsedProps.value,
		...emitsAsProps
	}));
}

//#endregion
export { useForwardPropsEmits };
//# sourceMappingURL=useForwardPropsEmits.js.map