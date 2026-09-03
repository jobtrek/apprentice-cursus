const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_createContext = require('../shared/createContext.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Rating_RatingRoot = require('./RatingRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Rating/RatingItem.vue?vue&type=script&setup=true&lang.ts
const [injectRatingItemContext, provideRatingItemContext] = require_shared_createContext.createContext("RatingItem");
var RatingItem_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "RatingItem",
	props: {
		item: {
			type: Number,
			required: true
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "label"
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = require_Rating_RatingRoot.injectRatingRootContext();
		const steps = (0, vue.computed)(() => {
			const groupStartValue = props.item - 1;
			const groupEndValue = props.item;
			const stepSize = rootContext.step.value;
			const numberOfSteps = Math.ceil((groupEndValue - groupStartValue) / stepSize);
			return Array.from({ length: numberOfSteps }, (_, index) => Number((groupStartValue + (index + 1) * stepSize).toFixed(2)));
		});
		provideRatingItemContext({ steps });
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), {
				as: _ctx.as,
				"as-child": _ctx.asChild
			}, {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default", { steps: steps.value })]),
				_: 3
			}, 8, ["as", "as-child"]);
		};
	}
});

//#endregion
//#region src/Rating/RatingItem.vue
var RatingItem_default = RatingItem_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'RatingItem_default', {
  enumerable: true,
  get: function () {
    return RatingItem_default;
  }
});
Object.defineProperty(exports, 'injectRatingItemContext', {
  enumerable: true,
  get: function () {
    return injectRatingItemContext;
  }
});
//# sourceMappingURL=RatingItem.cjs.map