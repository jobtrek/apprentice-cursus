const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_createContext = require('../shared/createContext.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_RadioGroup_RadioGroupRoot = require('../RadioGroup/RadioGroupRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));
const __vueuse_core = require_rolldown_runtime.__toESM(require("@vueuse/core"));

//#region src/Rating/RatingRoot.vue?vue&type=script&setup=true&lang.ts
const [injectRatingRootContext, provideRatingRootContext] = require_shared_createContext.createContext("RatingRoot");
var RatingRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const { length, disabled, clearable, hoverable, step } = (0, vue.toRefs)(props);
		require_shared_useForwardExpose.useForwardExpose();
		const modelValue = (0, __vueuse_core.useVModel)(props, "modelValue", emits, {
			defaultValue: props.defaultValue,
			passive: props.modelValue === void 0
		});
		const items = (0, vue.computed)(() => {
			return Array.from({ length: length.value }, (_, i) => i + 1);
		});
		const hoveredRating = (0, vue.ref)(0);
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
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_RadioGroup_RadioGroupRoot.RadioGroupRoot_default), (0, vue.mergeProps)((0, vue.unref)(__vueuse_core.reactiveOmit)(props, "length", "clearable", "hoverable", "step"), {
				disabled: (0, vue.unref)(disabled),
				onMouseleave: resetHoveredRating
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default", {
					items: items.value,
					modelValue: (0, vue.unref)(modelValue)
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
Object.defineProperty(exports, 'RatingRoot_default', {
  enumerable: true,
  get: function () {
    return RatingRoot_default;
  }
});
Object.defineProperty(exports, 'injectRatingRootContext', {
  enumerable: true,
  get: function () {
    return injectRatingRootContext;
  }
});
//# sourceMappingURL=RatingRoot.cjs.map