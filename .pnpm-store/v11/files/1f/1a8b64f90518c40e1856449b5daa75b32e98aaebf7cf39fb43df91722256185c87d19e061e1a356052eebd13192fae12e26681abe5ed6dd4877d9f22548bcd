import { getCurrentInstance } from "vue";

//#region src/shared/useForwardScopeId.ts
/**
* Returns the parent component's `<style scoped>` id (e.g. `data-v-xxxxxxx`) as a
* bindable attribute object, so it can be manually forwarded onto the chosen root
* element of a multi-root component.
*
* Vue only auto-applies the parent's scope id to a **single-root** component's root.
* When a component renders multiple root nodes (e.g. an interactive control plus a
* sibling hidden form input), that fallthrough is dropped and the parent's scoped
* styles can no longer reach the component. Spread the returned object onto the
* element that should stay styleable by the parent.
*
* @example
* ```ts
* const scopeIdAttrs = useForwardScopeId()
* // <Primitive v-bind="{ ...$attrs, ...scopeIdAttrs }" />
* ```
*/
function useForwardScopeId() {
	const scopeId = (getCurrentInstance()?.vnode)?.scopeId;
	return scopeId ? { [scopeId]: "" } : {};
}

//#endregion
export { useForwardScopeId };
//# sourceMappingURL=useForwardScopeId.js.map