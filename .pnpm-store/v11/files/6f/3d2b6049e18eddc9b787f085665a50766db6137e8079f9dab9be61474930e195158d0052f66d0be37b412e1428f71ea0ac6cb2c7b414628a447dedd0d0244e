import { createContext } from "../shared/createContext.js";
import { computed, defineComponent, ref, renderSlot } from "vue";

//#region src/Drawer/DrawerProvider.vue?vue&type=script&setup=true&lang.ts
const [injectDrawerProviderContext, provideDrawerProviderContext] = /*#__PURE__*/ createContext("DrawerProvider");
var DrawerProvider_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerProvider",
	setup(__props) {
		function createVisualStateStore() {
			let state = {
				swipeProgress: 0,
				frontmostHeight: 0
			};
			const listeners = /* @__PURE__ */ new Set();
			return {
				getSnapshot: () => state,
				set(next) {
					const nextProgress = next.swipeProgress !== void 0 ? Number.isFinite(next.swipeProgress) ? next.swipeProgress : 0 : state.swipeProgress;
					const nextHeight = next.frontmostHeight !== void 0 ? Number.isFinite(next.frontmostHeight) ? next.frontmostHeight : 0 : state.frontmostHeight;
					if (nextProgress === state.swipeProgress && nextHeight === state.frontmostHeight) return;
					state = {
						swipeProgress: nextProgress,
						frontmostHeight: nextHeight
					};
					listeners.forEach((l) => l());
				},
				subscribe(listener) {
					listeners.add(listener);
					return () => listeners.delete(listener);
				}
			};
		}
		const openById = ref(/* @__PURE__ */ new Map());
		const visualStateStore = createVisualStateStore();
		const active = computed(() => {
			for (const open of openById.value.values()) if (open) return true;
			return false;
		});
		provideDrawerProviderContext({
			active,
			setDrawerOpen(id, open) {
				const prev = openById.value.get(id);
				if (prev === open) return;
				const next = new Map(openById.value);
				next.set(id, open);
				openById.value = next;
			},
			removeDrawer(id) {
				if (!openById.value.has(id)) return;
				const next = new Map(openById.value);
				next.delete(id);
				openById.value = next;
			},
			visualStateStore
		});
		return (_ctx, _cache) => {
			return renderSlot(_ctx.$slots, "default");
		};
	}
});

//#endregion
//#region src/Drawer/DrawerProvider.vue
var DrawerProvider_default = DrawerProvider_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerProvider_default, injectDrawerProviderContext };
//# sourceMappingURL=DrawerProvider.js.map