import { createContext } from "../shared/createContext.js";
import { useFormControl } from "../shared/useFormControl.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { useForwardScopeId } from "../shared/useForwardScopeId.js";
import { Primitive } from "../Primitive/Primitive.js";
import { VisuallyHiddenInput_default } from "../VisuallyHidden/VisuallyHiddenInput.js";
import { Fragment, computed, createBlock, createCommentVNode, createElementBlock, createVNode, defineComponent, mergeProps, openBlock, renderSlot, toRefs, unref, withCtx, withKeys, withModifiers } from "vue";
import { useVModel } from "@vueuse/core";

//#region src/Switch/SwitchRoot.vue?vue&type=script&setup=true&lang.ts
const [injectSwitchRootContext, provideSwitchRootContext] = /*#__PURE__*/ createContext("SwitchRoot");
var SwitchRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "SwitchRoot",
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
		id: {
			type: String,
			required: false
		},
		value: {
			type: String,
			required: false,
			default: "on"
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
		const emit = __emit;
		const { disabled } = toRefs(props);
		const modelValue = useVModel(props, "modelValue", emit, {
			defaultValue: props.defaultValue ?? props.falseValue,
			passive: props.modelValue === void 0
		});
		const checked = computed(() => modelValue.value === props.trueValue);
		function toggleCheck() {
			if (disabled.value) return;
			modelValue.value = checked.value ? props.falseValue : props.trueValue;
		}
		const { forwardRef, currentElement } = useForwardExpose();
		const isFormControl = useFormControl(currentElement);
		const scopeIdAttrs = useForwardScopeId();
		const ariaLabel = computed(() => props.id && currentElement.value ? document.querySelector(`[for="${props.id}"]`)?.innerText : void 0);
		provideSwitchRootContext({
			checked,
			toggleCheck,
			disabled
		});
		return (_ctx, _cache) => {
			return openBlock(), createElementBlock(Fragment, null, [createVNode(unref(Primitive), mergeProps({
				id: _ctx.id,
				ref: unref(forwardRef),
				role: "switch",
				type: _ctx.as === "button" ? "button" : void 0,
				value: _ctx.value,
				"aria-label": _ctx.$attrs["aria-label"] || ariaLabel.value,
				"aria-checked": checked.value,
				"aria-required": _ctx.required,
				"data-state": checked.value ? "checked" : "unchecked",
				"data-disabled": unref(disabled) ? "" : void 0,
				"as-child": _ctx.asChild,
				as: _ctx.as,
				disabled: unref(disabled)
			}, {
				...unref(scopeIdAttrs),
				..._ctx.$attrs
			}, {
				onClick: toggleCheck,
				onKeydown: withKeys(withModifiers(toggleCheck, ["prevent"]), ["enter"])
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
					modelValue: unref(modelValue),
					checked: checked.value
				})]),
				_: 3
			}, 16, [
				"id",
				"type",
				"value",
				"aria-label",
				"aria-checked",
				"aria-required",
				"data-state",
				"data-disabled",
				"as-child",
				"as",
				"disabled",
				"onKeydown"
			]), unref(isFormControl) && _ctx.name ? (openBlock(), createBlock(unref(VisuallyHiddenInput_default), mergeProps({
				key: 0,
				type: "checkbox",
				name: _ctx.name,
				disabled: unref(disabled),
				required: _ctx.required,
				value: _ctx.value,
				checked: checked.value
			}, unref(scopeIdAttrs)), null, 16, [
				"name",
				"disabled",
				"required",
				"value",
				"checked"
			])) : createCommentVNode("v-if", true)], 64);
		};
	}
});

//#endregion
//#region src/Switch/SwitchRoot.vue
var SwitchRoot_default = SwitchRoot_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { SwitchRoot_default, injectSwitchRootContext };
//# sourceMappingURL=SwitchRoot.js.map