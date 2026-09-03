import { createContext } from "../shared/createContext.js";
import { isNullish } from "../shared/nullish.js";
import { isValueEqualOrExist } from "../shared/isValueEqualOrExist.js";
import { useFormControl } from "../shared/useFormControl.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { useForwardScopeId } from "../shared/useForwardScopeId.js";
import { Primitive } from "../Primitive/Primitive.js";
import { VisuallyHiddenInput_default } from "../VisuallyHidden/VisuallyHiddenInput.js";
import { RovingFocusItem_default } from "../RovingFocus/RovingFocusItem.js";
import { injectCheckboxGroupRootContext } from "./CheckboxGroupRoot.js";
import { getState, isIndeterminate } from "./utils.js";
import { Fragment, computed, createBlock, createCommentVNode, createElementBlock, defineComponent, mergeProps, openBlock, renderSlot, resolveDynamicComponent, unref, useAttrs, withCtx, withKeys, withModifiers } from "vue";
import { isEqual } from "ohash";
import { useVModel } from "@vueuse/core";

//#region src/Checkbox/CheckboxRoot.vue?vue&type=script&setup=true&lang.ts
const [injectCheckboxRootContext, provideCheckboxRootContext] = /*#__PURE__*/ createContext("CheckboxRoot");
var CheckboxRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "CheckboxRoot",
	props: {
		defaultValue: {
			type: null,
			required: false
		},
		modelValue: {
			type: null,
			required: false,
			default: void 0
		},
		disabled: {
			type: Boolean,
			required: false
		},
		value: {
			type: null,
			required: false,
			default: "on"
		},
		id: {
			type: String,
			required: false
		},
		trueValue: {
			type: null,
			required: false,
			default: () => true
		},
		falseValue: {
			type: null,
			required: false,
			default: () => false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "button"
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
		const { forwardRef, currentElement } = useForwardExpose();
		const checkboxGroupContext = injectCheckboxGroupRootContext(null);
		const modelValue = useVModel(props, "modelValue", emits, {
			defaultValue: props.defaultValue ?? props.falseValue,
			passive: props.modelValue === void 0
		});
		const disabled = computed(() => checkboxGroupContext?.disabled.value || props.disabled);
		const isChecked = computed(() => isEqual(modelValue.value, props.trueValue));
		const checkboxState = computed(() => {
			if (!isNullish(checkboxGroupContext?.modelValue.value)) return isValueEqualOrExist(checkboxGroupContext.modelValue.value, props.value);
			else {
				if (modelValue.value === "indeterminate") return "indeterminate";
				return isChecked.value;
			}
		});
		function handleClick() {
			if (!isNullish(checkboxGroupContext?.modelValue.value)) {
				const modelValueArray = [...checkboxGroupContext.modelValue.value || []];
				if (isValueEqualOrExist(modelValueArray, props.value)) {
					const index = modelValueArray.findIndex((i) => isEqual(i, props.value));
					modelValueArray.splice(index, 1);
				} else modelValueArray.push(props.value);
				checkboxGroupContext.modelValue.value = modelValueArray;
			} else if (modelValue.value === "indeterminate") modelValue.value = props.trueValue;
			else modelValue.value = isChecked.value ? props.falseValue : props.trueValue;
		}
		const isFormControl = useFormControl(currentElement);
		const scopeIdAttrs = useForwardScopeId();
		const attrs = useAttrs();
		const ariaLabel = computed(() => {
			if (attrs["aria-label"]) return void 0;
			return props.id && currentElement.value ? document.querySelector(`[for="${props.id}"]`)?.innerText : void 0;
		});
		provideCheckboxRootContext({
			disabled,
			state: checkboxState
		});
		return (_ctx, _cache) => {
			return openBlock(), createElementBlock(Fragment, null, [(openBlock(), createBlock(resolveDynamicComponent(unref(checkboxGroupContext)?.rovingFocus.value ? unref(RovingFocusItem_default) : unref(Primitive)), mergeProps({
				..._ctx.$attrs,
				...unref(scopeIdAttrs)
			}, {
				id: _ctx.id,
				ref: unref(forwardRef),
				role: "checkbox",
				"as-child": _ctx.asChild,
				as: _ctx.as,
				type: _ctx.as === "button" ? "button" : void 0,
				"aria-checked": unref(isIndeterminate)(checkboxState.value) ? "mixed" : checkboxState.value,
				"aria-required": _ctx.required,
				"aria-label": _ctx.$attrs["aria-label"] || ariaLabel.value,
				"data-state": unref(getState)(checkboxState.value),
				"data-disabled": disabled.value ? "" : void 0,
				disabled: disabled.value,
				focusable: unref(checkboxGroupContext)?.rovingFocus.value ? !disabled.value : void 0,
				onKeydown: withKeys(withModifiers(() => {}, ["prevent"]), ["enter"]),
				onClick: handleClick
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
					modelValue: unref(modelValue),
					state: checkboxState.value
				})]),
				_: 3
			}, 16, [
				"id",
				"as-child",
				"as",
				"type",
				"aria-checked",
				"aria-required",
				"aria-label",
				"data-state",
				"data-disabled",
				"disabled",
				"focusable",
				"onKeydown"
			])), unref(isFormControl) && _ctx.name && !unref(checkboxGroupContext) ? (openBlock(), createBlock(unref(VisuallyHiddenInput_default), mergeProps({
				key: 0,
				type: "checkbox",
				checked: !!checkboxState.value,
				name: _ctx.name,
				value: _ctx.value,
				disabled: disabled.value,
				required: _ctx.required
			}, unref(scopeIdAttrs)), null, 16, [
				"checked",
				"name",
				"value",
				"disabled",
				"required"
			])) : createCommentVNode("v-if", true)], 64);
		};
	}
});

//#endregion
//#region src/Checkbox/CheckboxRoot.vue
var CheckboxRoot_default = CheckboxRoot_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { CheckboxRoot_default, injectCheckboxRootContext };
//# sourceMappingURL=CheckboxRoot.js.map