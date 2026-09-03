const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_createContext = require('../shared/createContext.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerProvider.vue?vue&type=script&setup=true&lang.ts
const [injectDrawerProviderContext, provideDrawerProviderContext] = require_shared_createContext.createContext("DrawerProvider");
var DrawerProvider_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const openById = (0, vue.ref)(/* @__PURE__ */ new Map());
		const visualStateStore = createVisualStateStore();
		const active = (0, vue.computed)(() => {
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
			return (0, vue.renderSlot)(_ctx.$slots, "default");
		};
	}
});

//#endregion
//#region src/Drawer/DrawerProvider.vue
var DrawerProvider_default = DrawerProvider_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerProvider_default', {
  enumerable: true,
  get: function () {
    return DrawerProvider_default;
  }
});
Object.defineProperty(exports, 'injectDrawerProviderContext', {
  enumerable: true,
  get: function () {
    return injectDrawerProviderContext;
  }
});
//# sourceMappingURL=DrawerProvider.cjs.map