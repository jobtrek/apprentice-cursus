import { createContext } from "../shared/createContext.js";
import { useId } from "../shared/useId.js";
import { injectDrawerProviderContext } from "./DrawerProvider.js";
import { createNestedSwipeProgressStore } from "./utils.js";
import { computed, defineComponent, onUnmounted, ref, renderSlot, toRefs, watch } from "vue";
import { useVModel } from "@vueuse/core";

//#region src/Drawer/DrawerRoot.vue?vue&type=script&setup=true&lang.ts
const [injectDrawerRootContext, provideDrawerRootContext] = /*#__PURE__*/ createContext("DrawerRoot");
var DrawerRoot_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
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
		const uncontrolledOpen = ref(props.defaultOpen);
		const open = computed({
			get: () => props.open ?? uncontrolledOpen.value,
			set: (value) => {
				uncontrolledOpen.value = value;
			}
		});
		const activeSnapPoint = useVModel(props, "snapPoint", emit, {
			defaultValue: props.defaultSnapPoint ?? null,
			passive: props.snapPoint === void 0,
			eventName: "update:snapPoint"
		});
		const { modal, swipeDirection, snapPoints, snapToSequentialPoints } = toRefs(props);
		const triggerElement = ref();
		const contentElement = ref();
		const popupHeight = ref(0);
		const frontmostHeight = ref(0);
		const hasNestedDrawer = ref(false);
		const nestedOpenDrawerCount = ref(0);
		const nestedSwiping = ref(false);
		const isSwiping = ref(false);
		const nestedSwipeProgressStore = createNestedSwipeProgressStore();
		const parentContext = injectDrawerRootContext(null);
		const providerContext = injectDrawerProviderContext(null);
		const contentId = useId(void 0, "reka-drawer-content");
		const titleId = useId(void 0, "reka-drawer-title");
		const descriptionId = useId(void 0, "reka-drawer-description");
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
			onPopupHeightChange(h$1) {
				popupHeight.value = h$1;
				providerContext?.visualStateStore.set({ frontmostHeight: h$1 });
			},
			onNestedFrontmostHeightChange(h$1) {
				frontmostHeight.value = h$1;
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
		watch(open, (isOpen) => {
			if (isOpen) providerContext?.setDrawerOpen(contentId, true);
			else providerContext?.setDrawerOpen(contentId, false);
		}, { immediate: true });
		onUnmounted(() => {
			providerContext?.removeDrawer(contentId);
		});
		return (_ctx, _cache) => {
			return renderSlot(_ctx.$slots, "default", {
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
export { DrawerRoot_default, injectDrawerRootContext };
//# sourceMappingURL=DrawerRoot.js.map