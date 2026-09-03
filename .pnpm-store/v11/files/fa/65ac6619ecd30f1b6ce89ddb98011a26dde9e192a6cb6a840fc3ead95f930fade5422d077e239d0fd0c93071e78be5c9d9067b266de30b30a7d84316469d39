import { useForwardExpose } from "../shared/useForwardExpose.js";
import { RadioGroupItem_default } from "../RadioGroup/RadioGroupItem.js";
import { RadioGroupIndicator_default } from "../RadioGroup/RadioGroupIndicator.js";
import { injectRatingRootContext } from "./RatingRoot.js";
import { injectRatingItemContext } from "./RatingItem.js";
import { computed, createBlock, createVNode, defineComponent, normalizeStyle, openBlock, renderSlot, unref, withCtx } from "vue";
import { useActiveElement } from "@vueuse/core";

//#region src/Rating/RatingItemIndicator.vue?vue&type=script&setup=true&lang.ts
var RatingItemIndicator_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "RatingItemIndicator",
	props: {
		step: {
			type: Number,
			required: true
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = injectRatingRootContext();
		const { currentElement, forwardRef } = useForwardExpose();
		const activeElement = useActiveElement();
		const itemContext = injectRatingItemContext();
		const isActive = computed(() => {
			return rootContext.hoveredRating.value > 0 && props.step <= rootContext.hoveredRating.value || rootContext.hoveredRating.value === 0 && props.step <= rootContext.modelValue.value;
		});
		const isVisible = computed(() => {
			return activeElement.value === currentElement.value || rootContext.step.value === 1 || props.step % 1 === 0 || props.step === rootContext.hoveredRating.value || props.step === rootContext.modelValue.value;
		});
		function handleMouseEnter() {
			rootContext.changeHoveredRating(props.step);
		}
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(RadioGroupItem_default), {
				ref: unref(forwardRef),
				as: _ctx.as,
				"as-child": _ctx.asChild,
				style: normalizeStyle({
					["--reka-rating-item-step-width"]: `${(_ctx.step % 1 || 1) * 100}%`,
					["--reka-rating-item-step-opacity"]: isVisible.value ? 1 : 0,
					["--reka-rating-item-step-z-index"]: unref(itemContext).steps.value.length - unref(itemContext).steps.value.indexOf(_ctx.step)
				}),
				value: _ctx.step,
				"data-state": isActive.value ? "active" : void 0,
				disabled: unref(rootContext).disabled.value,
				onSelect: _cache[0] || (_cache[0] = ($event) => unref(rootContext).changeModelValue(_ctx.step)),
				onMouseenter: handleMouseEnter
			}, {
				default: withCtx(() => [createVNode(unref(RadioGroupIndicator_default), {
					"force-mount": "",
					"as-child": ""
				}, {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				})]),
				_: 3
			}, 8, [
				"as",
				"as-child",
				"style",
				"value",
				"data-state",
				"disabled"
			]);
		};
	}
});

//#endregion
//#region src/Rating/RatingItemIndicator.vue
var RatingItemIndicator_default = RatingItemIndicator_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { RatingItemIndicator_default };
//# sourceMappingURL=RatingItemIndicator.js.map