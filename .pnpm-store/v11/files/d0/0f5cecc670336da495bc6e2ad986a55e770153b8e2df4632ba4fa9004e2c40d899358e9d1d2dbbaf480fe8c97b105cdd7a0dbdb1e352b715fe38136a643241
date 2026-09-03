const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useEmitAsProps = require('../shared/useEmitAsProps.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_shared_useHideOthers = require('../shared/useHideOthers.cjs');
const require_Presence_Presence = require('../Presence/Presence.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const require_Drawer_DrawerContentImpl = require('./DrawerContentImpl.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerContent.vue?vue&type=script&setup=true&lang.ts
var DrawerContent_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "DrawerContent",
	props: {
		forceMount: {
			type: Boolean,
			required: false
		},
		initialFocus: {
			type: [Boolean, null],
			required: false,
			skipCheck: true
		},
		finalFocus: {
			type: [Boolean, null],
			required: false,
			skipCheck: true
		},
		disableOutsidePointerEvents: {
			type: Boolean,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	emits: [
		"escapeKeyDown",
		"pointerDownOutside",
		"focusOutside",
		"interactOutside",
		"openAutoFocus",
		"closeAutoFocus"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const emitsAsProps = require_shared_useEmitAsProps.useEmitAsProps(emits);
		const { forwardRef, currentElement } = require_shared_useForwardExpose.useForwardExpose();
		const hasInteractedOutside = (0, vue.ref)(false);
		const hasPointerDownOutside = (0, vue.ref)(false);
		const isFullModal = (0, vue.computed)(() => rootContext.modal.value === true);
		const isTrapFocusOnly = (0, vue.computed)(() => rootContext.modal.value === "trap-focus");
		const shouldTrapFocus = (0, vue.computed)(() => (isFullModal.value || isTrapFocusOnly.value) && rootContext.open.value);
		const shouldHideOthers = (0, vue.computed)(() => isFullModal.value ? currentElement.value : void 0);
		require_shared_useHideOthers.useHideOthers(shouldHideOthers);
		function onCloseAutoFocusNonModal(e) {
			if (!e.defaultPrevented) {
				if (!hasInteractedOutside.value) rootContext.triggerElement.value?.focus();
				e.preventDefault();
			}
			hasInteractedOutside.value = false;
			hasPointerDownOutside.value = false;
		}
		function onInteractOutsideNonModal(e) {
			if (!e.defaultPrevented) {
				hasInteractedOutside.value = true;
				if (e.detail.originalEvent.type === "pointerdown") hasPointerDownOutside.value = true;
			}
			const target = e.target;
			if (rootContext.triggerElement.value?.contains(target)) e.preventDefault();
			if (e.detail.originalEvent.type === "focusin" && hasPointerDownOutside.value) e.preventDefault();
		}
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Presence_Presence.Presence_default), { present: _ctx.forceMount || (0, vue.unref)(rootContext).open.value }, {
				default: (0, vue.withCtx)(() => [isFullModal.value ? ((0, vue.openBlock)(), (0, vue.createBlock)(require_Drawer_DrawerContentImpl.DrawerContentImpl_default, (0, vue.mergeProps)({
					key: 0,
					ref: (0, vue.unref)(forwardRef)
				}, {
					...props,
					...(0, vue.unref)(emitsAsProps),
					..._ctx.$attrs
				}, {
					"trap-focus": shouldTrapFocus.value,
					"disable-outside-pointer-events": true,
					onCloseAutoFocus: _cache[0] || (_cache[0] = (e) => {
						if (!e.defaultPrevented) {
							e.preventDefault();
							(0, vue.unref)(rootContext).triggerElement.value?.focus();
						}
					}),
					onPointerDownOutside: _cache[1] || (_cache[1] = (e) => {
						const orig = e.detail.originalEvent;
						const isRightClick = orig.button === 2 || orig.button === 0 && orig.ctrlKey;
						if (isRightClick) e.preventDefault();
					}),
					onFocusOutside: _cache[2] || (_cache[2] = (e) => e.preventDefault())
				}), {
					default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["trap-focus"])) : ((0, vue.openBlock)(), (0, vue.createBlock)(require_Drawer_DrawerContentImpl.DrawerContentImpl_default, (0, vue.mergeProps)({
					key: 1,
					ref: (0, vue.unref)(forwardRef)
				}, {
					...props,
					...(0, vue.unref)(emitsAsProps),
					..._ctx.$attrs
				}, {
					"trap-focus": shouldTrapFocus.value,
					"disable-outside-pointer-events": false,
					onCloseAutoFocus: onCloseAutoFocusNonModal,
					onInteractOutside: onInteractOutsideNonModal
				}), {
					default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["trap-focus"]))]),
				_: 3
			}, 8, ["present"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerContent.vue
var DrawerContent_default = DrawerContent_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerContent_default', {
  enumerable: true,
  get: function () {
    return DrawerContent_default;
  }
});
//# sourceMappingURL=DrawerContent.cjs.map