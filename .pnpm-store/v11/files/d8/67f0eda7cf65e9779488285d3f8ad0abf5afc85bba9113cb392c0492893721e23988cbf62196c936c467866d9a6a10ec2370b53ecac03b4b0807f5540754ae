import { createContext } from "../shared/createContext.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectRatingRootContext } from "./RatingRoot.js";
import { computed, createBlock, defineComponent, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Rating/RatingItem.vue?vue&type=script&setup=true&lang.ts
const [injectRatingItemContext, provideRatingItemContext] = /*#__PURE__*/ createContext("RatingItem");
var RatingItem_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
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
		const rootContext = injectRatingRootContext();
		const steps = computed(() => {
			const groupStartValue = props.item - 1;
			const groupEndValue = props.item;
			const stepSize = rootContext.step.value;
			const numberOfSteps = Math.ceil((groupEndValue - groupStartValue) / stepSize);
			return Array.from({ length: numberOfSteps }, (_, index) => Number((groupStartValue + (index + 1) * stepSize).toFixed(2)));
		});
		provideRatingItemContext({ steps });
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), {
				as: _ctx.as,
				"as-child": _ctx.asChild
			}, {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default", { steps: steps.value })]),
				_: 3
			}, 8, ["as", "as-child"]);
		};
	}
});

//#endregion
//#region src/Rating/RatingItem.vue
var RatingItem_default = RatingItem_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { RatingItem_default, injectRatingItemContext };
//# sourceMappingURL=RatingItem.js.map