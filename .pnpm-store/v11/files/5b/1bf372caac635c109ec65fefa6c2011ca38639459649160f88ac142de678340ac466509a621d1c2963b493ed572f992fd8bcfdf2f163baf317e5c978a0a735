const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useEmitAsProps = require('./useEmitAsProps.cjs');
const require_shared_useForwardProps = require('./useForwardProps.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/shared/useForwardPropsEmits.ts
function useForwardPropsEmits(props, emit) {
	const parsedProps = require_shared_useForwardProps.useForwardProps(props);
	const emitsAsProps = emit ? require_shared_useEmitAsProps.useEmitAsProps(emit) : {};
	return (0, vue.computed)(() => ({
		...parsedProps.value,
		...emitsAsProps
	}));
}

//#endregion
Object.defineProperty(exports, 'useForwardPropsEmits', {
  enumerable: true,
  get: function () {
    return useForwardPropsEmits;
  }
});
//# sourceMappingURL=useForwardPropsEmits.cjs.map