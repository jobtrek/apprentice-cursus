import { injectConfigProviderContext } from "../ConfigProvider/ConfigProvider.js";
import { Teleport, computed, createBlock, createCommentVNode, defineComponent, openBlock, renderSlot, unref } from "vue";
import { useMounted } from "@vueuse/core";

//#region src/Teleport/Teleport.vue?vue&type=script&setup=true&lang.ts
var Teleport_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "Teleport",
	props: {
		to: {
			type: null,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		defer: {
			type: Boolean,
			required: false
		},
		forceMount: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const configContext = injectConfigProviderContext({});
		const target = computed(() => props.to ?? configContext.teleportTo?.value ?? "body");
		const isMounted = useMounted();
		return (_ctx, _cache) => {
			return unref(isMounted) || _ctx.forceMount ? (openBlock(), createBlock(Teleport, {
				key: 0,
				to: target.value,
				disabled: _ctx.disabled,
				defer: _ctx.defer
			}, [renderSlot(_ctx.$slots, "default")], 8, [
				"to",
				"disabled",
				"defer"
			])) : createCommentVNode("v-if", true);
		};
	}
});

//#endregion
//#region src/Teleport/Teleport.vue
var Teleport_default = Teleport_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { Teleport_default };
//# sourceMappingURL=Teleport.js.map