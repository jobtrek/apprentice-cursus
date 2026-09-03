import { createContext } from "../shared/createContext.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { RadioGroupRoot_default } from "../RadioGroup/RadioGroupRoot.js";
import { computed, createBlock, defineComponent, mergeProps, openBlock, ref, renderSlot, toRefs, unref, withCtx } from "vue";
import { reactiveOmit, useVModel } from "@vueuse/core";

//#region src/Rating/RatingRoot.vue?vue&type=script&setup=true&lang.ts
const [injectRatingRootContext, provideRatingRootContext] = /*#__PURE__*/ createContext("RatingRoot");
var RatingRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "RatingRoot",
	props: {
		defaultValue: {
			type: Number,
			required: false
		},
		modelValue: {
			type: Number,
			required: false
		},
		length: {
			type: Number,
			required: false,
			default: 5
		},
		clearable: {
			type: Boolean,
			required: false
		},
		hoverable: {
			type: Boolean,
			required: false
		},
		step: {
			type: Number,
			required: false,
			default: 1
		},
		disabled: {
			type: Boolean,
			required: false
		},
		orientation: {
			type: String,
			required: false,
			default: "horizontal"
		},
		dir: {
			type: String,
			required: false
		},
		loop: {
			type: Boolean,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		},
		name: {
			type: String,
			required: false
		},
		required: {
			type: Boolean,
			required: false
		}
	},
	emits: ["update:modelValue"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const { length, disabled, clearable, hoverable, step } = toRefs(props);
		useForwardExpose();
		const modelValue = useVModel(props, "modelValue", emits, {
			defaultValue: props.defaultValue,
			passive: props.modelValue === void 0
		});
		const items = computed(() => {
			return Array.from({ length: length.value }, (_, i) => i + 1);
		});
		const hoveredRating = ref(0);
		function changeModelValue(rating) {
			if (disabled.value) return;
			if (clearable.value && modelValue.value === rating) {
				hoveredRating.value = 0;
				modelValue.value = 0;
			} else modelValue.value = rating;
		}
		function changeHoveredRating(rating) {
			if (disabled.value || !hoverable.value) return;
			hoveredRating.value = rating;
		}
		function resetHoveredRating() {
			hoveredRating.value = 0;
		}
		provideRatingRootContext({
			modelValue,
			items,
			hoveredRating,
			disabled,
			step,
			changeModelValue,
			changeHoveredRating
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(RadioGroupRoot_default), mergeProps(unref(reactiveOmit)(props, "length", "clearable", "hoverable", "step"), {
				disabled: unref(disabled),
				onMouseleave: resetHoveredRating
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
					items: items.value,
					modelValue: unref(modelValue)
				})]),
				_: 3
			}, 16, ["disabled"]);
		};
	}
});

//#endregion
//#region src/Rating/RatingRoot.vue
var RatingRoot_default = RatingRoot_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { RatingRoot_default, injectRatingRootContext };
//# sourceMappingURL=RatingRoot.js.map