const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_createContext = require('../shared/createContext.cjs');
const require_shared_useId = require('../shared/useId.cjs');
const require_Drawer_DrawerProvider = require('./DrawerProvider.cjs');
const require_Drawer_utils = require('./utils.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));
const __vueuse_core = require_rolldown_runtime.__toESM(require("@vueuse/core"));

//#region src/Drawer/DrawerRoot.vue?vue&type=script&setup=true&lang.ts
const [injectDrawerRootContext, provideDrawerRootContext] = require_shared_createContext.createContext("DrawerRoot");
var DrawerRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "DrawerRoot",
	props: {
		open: {
			type: Boolean,
			required: false,
			default: void 0
		},
		defaultOpen: {
			type: Boolean,
			required: false,
			default: false
		},
		modal: {
			type: [Boolean, String],
			required: false,
			default: true
		},
		swipeDirection: {
			type: String,
			required: false,
			default: "down"
		},
		snapPoints: {
			type: Array,
			required: false,
			default: void 0
		},
		snapPoint: {
			type: [
				Number,
				String,
				null
			],
			required: false,
			default: void 0
		},
		defaultSnapPoint: {
			type: [
				Number,
				String,
				null
			],
			required: false,
			default: void 0
		},
		snapToSequentialPoints: {
			type: Boolean,
			required: false,
			default: false
		}
	},
	emits: [
		"update:open",
		"update:openComplete",
		"update:snapPoint"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		const uncontrolledOpen = (0, vue.ref)(props.defaultOpen);
		const open = (0, vue.computed)({
			get: () => props.open ?? uncontrolledOpen.value,
			set: (value) => {
				uncontrolledOpen.value = value;
			}
		});
		const activeSnapPoint = (0, __vueuse_core.useVModel)(props, "snapPoint", emit, {
			defaultValue: props.defaultSnapPoint ?? null,
			passive: props.snapPoint === void 0,
			eventName: "update:snapPoint"
		});
		const { modal, swipeDirection, snapPoints, snapToSequentialPoints } = (0, vue.toRefs)(props);
		const triggerElement = (0, vue.ref)();
		const contentElement = (0, vue.ref)();
		const popupHeight = (0, vue.ref)(0);
		const frontmostHeight = (0, vue.ref)(0);
		const hasNestedDrawer = (0, vue.ref)(false);
		const nestedOpenDrawerCount = (0, vue.ref)(0);
		const nestedSwiping = (0, vue.ref)(false);
		const isSwiping = (0, vue.ref)(false);
		const nestedSwipeProgressStore = require_Drawer_utils.createNestedSwipeProgressStore();
		const parentContext = injectDrawerRootContext(null);
		const providerContext = require_Drawer_DrawerProvider.injectDrawerProviderContext(null);
		const contentId = require_shared_useId.useId(void 0, "reka-drawer-content");
		const titleId = require_shared_useId.useId(void 0, "reka-drawer-title");
		const descriptionId = require_shared_useId.useId(void 0, "reka-drawer-description");
		provideDrawerRootContext({
			open,
			modal,
			swipeDirection,
			snapPoints,
			activeSnapPoint,
			snapToSequentialPoints,
			popupHeight,
			frontmostHeight,
			hasNestedDrawer,
			nestedOpenDrawerCount,
			nestedSwiping,
			isSwiping,
			nestedSwipeProgressStore,
			onOpenChange: handleOpenChange,
			onOpenToggle: handleOpenToggle,
			notifyOpenComplete(value) {
				emit("update:openComplete", value);
			},
			setActiveSnapPoint(point) {
				activeSnapPoint.value = point;
			},
			onPopupHeightChange(h) {
				popupHeight.value = h;
				providerContext?.visualStateStore.set({ frontmostHeight: h });
			},
			onNestedFrontmostHeightChange(h) {
				frontmostHeight.value = h;
			},
			onNestedDrawerPresenceChange(present) {
				if (present) nestedOpenDrawerCount.value++;
				else nestedOpenDrawerCount.value = Math.max(0, nestedOpenDrawerCount.value - 1);
				hasNestedDrawer.value = nestedOpenDrawerCount.value > 0;
				parentContext?.notifyParentHasNestedDrawer?.(present);
			},
			onNestedSwipingChange(swiping) {
				nestedSwiping.value = swiping;
				parentContext?.notifyParentSwipingChange?.(swiping);
			},
			onNestedSwipeProgressChange(progress) {
				nestedSwipeProgressStore.set(progress);
				parentContext?.notifyParentSwipeProgressChange?.(progress);
				providerContext?.visualStateStore.set({ swipeProgress: progress });
			},
			onSwipingChange(swiping) {
				isSwiping.value = swiping;
			},
			notifyParentFrontmostHeight: parentContext?.onNestedFrontmostHeightChange,
			notifyParentSwipingChange: parentContext?.onNestedSwipingChange,
			notifyParentSwipeProgressChange: parentContext?.onNestedSwipeProgressChange,
			notifyParentHasNestedDrawer: parentContext?.onNestedDrawerPresenceChange,
			triggerElement,
			contentElement,
			contentId,
			titleId,
			descriptionId
		});
		function handleClose() {
			handleOpenChange(false, "close-press");
		}
		function handleOpenChange(value, reason) {
			if (open.value === value) return;
			const details = reason ? { reason } : void 0;
			uncontrolledOpen.value = value;
			emit("update:open", value, details);
		}
		function handleOpenToggle() {
			handleOpenChange(!open.value, "trigger-press");
		}
		(0, vue.watch)(open, (isOpen) => {
			if (isOpen) providerContext?.setDrawerOpen(contentId, true);
			else providerContext?.setDrawerOpen(contentId, false);
		}, { immediate: true });
		(0, vue.onUnmounted)(() => {
			providerContext?.removeDrawer(contentId);
		});
		return (_ctx, _cache) => {
			return (0, vue.renderSlot)(_ctx.$slots, "default", {
				open: open.value,
				close: handleClose
			});
		};
	}
});

//#endregion
//#region src/Drawer/DrawerRoot.vue
var DrawerRoot_default = DrawerRoot_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerRoot_default', {
  enumerable: true,
  get: function () {
    return DrawerRoot_default;
  }
});
Object.defineProperty(exports, 'injectDrawerRootContext', {
  enumerable: true,
  get: function () {
    return injectDrawerRootContext;
  }
});
//# sourceMappingURL=DrawerRoot.cjs.map