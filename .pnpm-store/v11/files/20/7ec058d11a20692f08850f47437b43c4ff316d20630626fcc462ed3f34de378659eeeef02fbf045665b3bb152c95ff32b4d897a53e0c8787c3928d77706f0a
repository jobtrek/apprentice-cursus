import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerProviderContext } from "./DrawerProvider.js";
import { DRAWER_CSS_VARS } from "./utils.js";
import { createBlock, defineComponent, mergeProps, onMounted, onUnmounted, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerIndent.vue?vue&type=script&setup=true&lang.ts
var DrawerIndent_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerIndent",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "div"
		}
	},
	setup(__props) {
		const props = __props;
		const { forwardRef, currentElement } = useForwardExpose();
		const providerContext = injectDrawerProviderContext(null);
		let unsubscribe;
		onMounted(() => {
			const store = providerContext?.visualStateStore;
			if (!store) return;
			const el = currentElement.value;
			if (!el) return;
			const sync = () => {
				const { swipeProgress, frontmostHeight } = store.getSnapshot();
				el.style.setProperty(DRAWER_CSS_VARS.swipeProgress, swipeProgress > 0 ? `${swipeProgress}` : "0");
				if (frontmostHeight > 0) el.style.setProperty(DRAWER_CSS_VARS.height, `${frontmostHeight}px`);
				else el.style.removeProperty(DRAWER_CSS_VARS.height);
			};
			sync();
			unsubscribe = store.subscribe(sync);
		});
		onUnmounted(() => {
			unsubscribe?.();
			const el = currentElement.value;
			if (el) {
				el.style.setProperty(DRAWER_CSS_VARS.swipeProgress, "0");
				el.style.removeProperty(DRAWER_CSS_VARS.height);
			}
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				ref: unref(forwardRef),
				"data-active": unref(providerContext)?.active.value ? "" : void 0,
				"data-inactive": unref(providerContext)?.active.value === false ? "" : void 0,
				style: { [unref(DRAWER_CSS_VARS).swipeProgress]: "0" }
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"data-active",
				"data-inactive",
				"style"
			]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerIndent.vue
var DrawerIndent_default = DrawerIndent_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerIndent_default };
//# sourceMappingURL=DrawerIndent.js.map