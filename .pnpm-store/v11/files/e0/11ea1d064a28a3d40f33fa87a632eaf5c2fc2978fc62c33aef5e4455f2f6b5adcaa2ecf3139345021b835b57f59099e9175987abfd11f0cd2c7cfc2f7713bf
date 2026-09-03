const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_RadioGroup_RadioGroupItem = require('../RadioGroup/RadioGroupItem.cjs');
const require_RadioGroup_RadioGroupIndicator = require('../RadioGroup/RadioGroupIndicator.cjs');
const require_Rating_RatingRoot = require('./RatingRoot.cjs');
const require_Rating_RatingItem = require('./RatingItem.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));
const __vueuse_core = require_rolldown_runtime.__toESM(require("@vueuse/core"));

//#region src/Rating/RatingItemIndicator.vue?vue&type=script&setup=true&lang.ts
var RatingItemIndicator_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const rootContext = require_Rating_RatingRoot.injectRatingRootContext();
		const { currentElement, forwardRef } = require_shared_useForwardExpose.useForwardExpose();
		const activeElement = (0, __vueuse_core.useActiveElement)();
		const itemContext = require_Rating_RatingItem.injectRatingItemContext();
		const isActive = (0, vue.computed)(() => {
			return rootContext.hoveredRating.value > 0 && props.step <= rootContext.hoveredRating.value || rootContext.hoveredRating.value === 0 && props.step <= rootContext.modelValue.value;
		});
		const isVisible = (0, vue.computed)(() => {
			return activeElement.value === currentElement.value || rootContext.step.value === 1 || props.step % 1 === 0 || props.step === rootContext.hoveredRating.value || props.step === rootContext.modelValue.value;
		});
		function handleMouseEnter() {
			rootContext.changeHoveredRating(props.step);
		}
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_RadioGroup_RadioGroupItem.RadioGroupItem_default), {
				ref: (0, vue.unref)(forwardRef),
				as: _ctx.as,
				"as-child": _ctx.asChild,
				style: (0, vue.normalizeStyle)({
					["--reka-rating-item-step-width"]: `${(_ctx.step % 1 || 1) * 100}%`,
					["--reka-rating-item-step-opacity"]: isVisible.value ? 1 : 0,
					["--reka-rating-item-step-z-index"]: (0, vue.unref)(itemContext).steps.value.length - (0, vue.unref)(itemContext).steps.value.indexOf(_ctx.step)
				}),
				value: _ctx.step,
				"data-state": isActive.value ? "active" : void 0,
				disabled: (0, vue.unref)(rootContext).disabled.value,
				onSelect: _cache[0] || (_cache[0] = ($event) => (0, vue.unref)(rootContext).changeModelValue(_ctx.step)),
				onMouseenter: handleMouseEnter
			}, {
				default: (0, vue.withCtx)(() => [(0, vue.createVNode)((0, vue.unref)(require_RadioGroup_RadioGroupIndicator.RadioGroupIndicator_default), {
					"force-mount": "",
					"as-child": ""
				}, {
					default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
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
Object.defineProperty(exports, 'RatingItemIndicator_default', {
  enumerable: true,
  get: function () {
    return RatingItemIndicator_default;
  }
});
//# sourceMappingURL=RatingItemIndicator.cjs.map