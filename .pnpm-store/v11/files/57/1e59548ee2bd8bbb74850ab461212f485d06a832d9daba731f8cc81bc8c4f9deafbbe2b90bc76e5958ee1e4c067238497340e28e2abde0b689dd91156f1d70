// src/index.ts
import { config as coreConfig } from "@inertiajs/core";
import { http as http2, progress, router as router12 } from "@inertiajs/core";

// src/app.ts
import {
  createHeadManager,
  isPropsObject,
  isPropsObjectOrCallback,
  normalizeLayouts,
  resolveServerHead,
  router as router4
} from "@inertiajs/core";
import {
  computed,
  defineComponent,
  h,
  markRaw,
  reactive as reactive2,
  ref as ref2,
  shallowRef
} from "vue";

// src/layoutProps.ts
import { createLayoutPropsStore } from "@inertiajs/core";
import { ref } from "vue";
var store = createLayoutPropsStore();
var state = ref(store.get());
store.subscribe(() => {
  state.value = store.get();
});
function setLayoutProps(nameOrProps, props) {
  if (typeof nameOrProps === "string") {
    store.setFor(nameOrProps, props);
  } else {
    store.set(nameOrProps);
  }
}
function resetLayoutProps() {
  store.reset();
  state.value = store.get();
}

// src/remember.ts
import { router } from "@inertiajs/core";
import { cloneDeep } from "es-toolkit";
var remember = {
  created() {
    if (!this.$options.remember) {
      return;
    }
    if (Array.isArray(this.$options.remember)) {
      this.$options.remember = { data: this.$options.remember };
    }
    if (typeof this.$options.remember === "string") {
      this.$options.remember = { data: [this.$options.remember] };
    }
    if (typeof this.$options.remember.data === "string") {
      this.$options.remember = { data: [this.$options.remember.data] };
    }
    const rememberKey = this.$options.remember.key instanceof Function ? this.$options.remember.key.call(this) : this.$options.remember.key;
    const restored = router.restore(rememberKey);
    const rememberable = this.$options.remember.data.filter((key2) => {
      return !(this[key2] !== null && typeof this[key2] === "object" && this[key2].__rememberable === false);
    });
    const hasCallbacks = (key2) => {
      return this[key2] !== null && typeof this[key2] === "object" && typeof this[key2].__remember === "function" && typeof this[key2].__restore === "function";
    };
    rememberable.forEach((key2) => {
      if (this[key2] !== void 0 && restored !== void 0 && restored[key2] !== void 0) {
        hasCallbacks(key2) ? this[key2].__restore(restored[key2]) : this[key2] = restored[key2];
      }
      this.$watch(
        key2,
        () => {
          router.remember(
            rememberable.reduce(
              (data, key3) => ({
                ...data,
                [key3]: cloneDeep(hasCallbacks(key3) ? this[key3].__remember() : this[key3])
              }),
              {}
            ),
            rememberKey
          );
        },
        { immediate: true, deep: true }
      );
    });
  }
};
var remember_default = remember;

// src/useForm.ts
import {
  router as router3,
  UseFormUtils as UseFormUtils2
} from "@inertiajs/core";
import { cloneDeep as cloneDeep3 } from "es-toolkit";

// src/useFormState.ts
import {
  router as router2,
  UseFormUtils
} from "@inertiajs/core";
import { cloneDeep as cloneDeep2, isEqual } from "es-toolkit";
import { get, has, set } from "es-toolkit/compat";
import {
  createValidator,
  resolveName,
  toSimpleValidationErrors
} from "laravel-precognition";
import { reactive, watch } from "vue";
function useFormState(options) {
  const { data: dataOption, rememberKey } = options;
  let { precognitionEndpoint } = options;
  const isDataFunction = typeof dataOption === "function";
  const resolveData = () => isDataFunction ? dataOption() : dataOption;
  const restored = rememberKey ? router2.restore(rememberKey) : null;
  const initialData = restored?.data ?? cloneDeep2(resolveData());
  let defaults = cloneDeep2(initialData);
  let transform = (data) => data;
  let validatorRef = null;
  let withAllErrors = null;
  const withAllErrorsEnabled = () => withAllErrors ?? config.get("form.withAllErrors");
  let recentlySuccessfulTimeoutId;
  let defaultsCalledInOnSuccess = false;
  let rememberExcludeKeys = [];
  const form = reactive({
    ...cloneDeep2(defaults),
    isDirty: false,
    errors: {},
    hasErrors: false,
    processing: false,
    progress: null,
    wasSuccessful: false,
    recentlySuccessful: false,
    withPrecognition(...args) {
      precognitionEndpoint = UseFormUtils.createWayfinderCallback(...args);
      const formWithPrecognition = this;
      const validator = createValidator(
        (client) => {
          const { method, url } = precognitionEndpoint();
          const transformedData = cloneDeep2(transform(this.data()));
          return client[method](url, transformedData);
        },
        cloneDeep2(defaults)
      );
      validatorRef = validator;
      validator.on("validatingChanged", () => {
        formWithPrecognition.validating = validator.validating();
      }).on("validatedChanged", () => {
        formWithPrecognition.__valid = validator.valid();
      }).on("touchedChanged", () => {
        formWithPrecognition.__touched = validator.touched();
      }).on("errorsChanged", () => {
        const validationErrors = withAllErrorsEnabled() ? validator.errors() : toSimpleValidationErrors(validator.errors());
        this.errors = {};
        this.setError(validationErrors);
        formWithPrecognition.__valid = validator.valid();
      });
      const tap = (value, callback) => {
        callback(value);
        return value;
      };
      Object.assign(formWithPrecognition, {
        __touched: [],
        __valid: [],
        validating: false,
        validator: () => validator,
        withAllErrors: () => tap(formWithPrecognition, () => withAllErrors = true),
        valid: (field) => formWithPrecognition.__valid.includes(field),
        invalid: (field) => field in this.errors,
        setValidationTimeout: (duration) => tap(formWithPrecognition, () => validator.setTimeout(duration)),
        validateFiles: () => tap(formWithPrecognition, () => validator.validateFiles()),
        withoutFileValidation: () => tap(formWithPrecognition, () => validator.withoutFileValidation()),
        touch: (field, ...fields) => {
          if (Array.isArray(field)) {
            validator.touch(field);
          } else if (typeof field === "string") {
            validator.touch([field, ...fields]);
          } else {
            validator.touch(field);
          }
          return formWithPrecognition;
        },
        touched: (field) => typeof field === "string" ? formWithPrecognition.__touched.includes(field) : formWithPrecognition.__touched.length > 0,
        validate: (field, config3) => {
          if (typeof field === "object" && !("target" in field)) {
            config3 = field;
            field = void 0;
          }
          if (field === void 0) {
            validator.validate(config3);
          } else {
            const fieldName = resolveName(field);
            const transformedData = transform(this.data());
            validator.validate(fieldName, get(transformedData, fieldName), config3);
          }
          return formWithPrecognition;
        },
        setErrors: (errors) => tap(formWithPrecognition, () => this.setError(errors)),
        forgetError: (field) => tap(
          formWithPrecognition,
          () => this.clearErrors(resolveName(field))
        )
      });
      return formWithPrecognition;
    },
    data() {
      return Object.keys(defaults).reduce((carry, key2) => {
        return set(carry, key2, get(this, key2));
      }, {});
    },
    transform(callback) {
      transform = callback;
      return this;
    },
    defaults(fieldOrFields, maybeValue) {
      if (isDataFunction) {
        throw new Error("You cannot call `defaults()` when using a function to define your form data.");
      }
      defaultsCalledInOnSuccess = true;
      if (typeof fieldOrFields === "undefined") {
        defaults = cloneDeep2(this.data());
        this.isDirty = false;
      } else {
        defaults = typeof fieldOrFields === "string" ? set(cloneDeep2(defaults), fieldOrFields, maybeValue) : Object.assign({}, cloneDeep2(defaults), fieldOrFields);
      }
      validatorRef?.defaults(defaults);
      return this;
    },
    reset(...fields) {
      const resolvedData = isDataFunction ? cloneDeep2(resolveData()) : defaults;
      const clonedData = cloneDeep2(resolvedData);
      if (fields.length === 0) {
        if (isDataFunction) {
          defaults = clonedData;
        }
        Object.assign(this, clonedData);
      } else {
        ;
        fields.filter((key2) => has(clonedData, key2)).forEach((key2) => {
          if (isDataFunction) {
            set(defaults, key2, get(clonedData, key2));
          }
          set(this, key2, get(clonedData, key2));
        });
      }
      validatorRef?.reset(...fields);
      return this;
    },
    setError(fieldOrFields, maybeValue) {
      const errors = typeof fieldOrFields === "string" ? { [fieldOrFields]: maybeValue } : fieldOrFields;
      Object.assign(this.errors, errors);
      this.hasErrors = Object.keys(this.errors).length > 0;
      validatorRef?.setErrors(errors);
      return this;
    },
    clearErrors(...fields) {
      this.errors = Object.keys(this.errors).reduce(
        (carry, field) => ({
          ...carry,
          ...fields.length > 0 && !fields.includes(field) ? { [field]: this.errors[field] } : {}
        }),
        {}
      );
      this.hasErrors = Object.keys(this.errors).length > 0;
      if (validatorRef) {
        if (fields.length === 0) {
          validatorRef.setErrors({});
        } else {
          fields.forEach(validatorRef.forgetError);
        }
      }
      return this;
    },
    resetAndClearErrors(...fields) {
      this.reset(...fields);
      this.clearErrors(...fields);
      return this;
    },
    __rememberable: rememberKey === null,
    __remember() {
      const formData = this.data();
      if (rememberExcludeKeys.length > 0) {
        const filtered = { ...formData };
        rememberExcludeKeys.forEach((k) => delete filtered[k]);
        return { data: filtered, errors: this.errors };
      }
      return { data: formData, errors: this.errors };
    },
    __restore(restored2) {
      Object.assign(this, restored2.data);
      this.setError(restored2.errors);
    }
  });
  const typedForm = form;
  if (restored?.errors) {
    typedForm.setError(restored.errors);
  }
  watch(
    typedForm,
    () => {
      typedForm.isDirty = !isEqual(typedForm.data(), defaults);
    },
    { immediate: true, deep: true }
  );
  watch(
    typedForm,
    (newValue) => {
      if (!rememberKey) {
        return;
      }
      const storedData = router2.restore(rememberKey);
      const newData = cloneDeep2(newValue.__remember());
      if (!isEqual(storedData, newData)) {
        router2.remember(newData, rememberKey);
      }
    },
    { immediate: true, deep: true }
  );
  if (precognitionEndpoint) {
    typedForm.withPrecognition(precognitionEndpoint);
  }
  return {
    form: typedForm,
    setDefaults: (newDefaults) => {
      defaults = newDefaults;
    },
    getTransform: () => transform,
    getPrecognitionEndpoint: () => precognitionEndpoint ?? null,
    markAsSuccessful: () => {
      typedForm.clearErrors();
      typedForm.wasSuccessful = true;
      typedForm.recentlySuccessful = true;
      recentlySuccessfulTimeoutId = setTimeout(
        () => typedForm.recentlySuccessful = false,
        config.get("form.recentlySuccessfulDuration")
      );
    },
    wasDefaultsCalledInOnSuccess: () => defaultsCalledInOnSuccess,
    resetDefaultsCalledInOnSuccess: () => {
      defaultsCalledInOnSuccess = false;
    },
    setRememberExcludeKeys: (keys) => {
      rememberExcludeKeys = keys;
    },
    resetBeforeSubmit: () => {
      typedForm.wasSuccessful = false;
      typedForm.recentlySuccessful = false;
      clearTimeout(recentlySuccessfulTimeoutId);
    },
    finishProcessing: () => {
      typedForm.processing = false;
      typedForm.progress = null;
    },
    withAllErrors: {
      enabled: withAllErrorsEnabled,
      enable: () => {
        withAllErrors = true;
      }
    }
  };
}

// src/useForm.ts
var reservedFormKeys = null;
var bootstrapping = false;
function validateFormDataKeys(data) {
  if (bootstrapping) {
    return;
  }
  if (reservedFormKeys === null) {
    bootstrapping = true;
    reservedFormKeys = new Set(Object.keys(useForm({})));
    bootstrapping = false;
  }
  const conflicts = Object.keys(data).filter((key2) => reservedFormKeys.has(key2));
  if (conflicts.length > 0) {
    console.error(
      `[Inertia] useForm() data contains field(s) that conflict with form properties: ${conflicts.map((k) => `"${k}"`).join(", ")}. These fields will be overwritten by form methods/properties. Please rename these fields.`
    );
  }
}
function useForm(...args) {
  const { rememberKey, data, precognitionEndpoint } = UseFormUtils2.parseUseFormArguments(...args);
  const initialDefaults = typeof data === "function" ? cloneDeep3(data()) : cloneDeep3(data);
  validateFormDataKeys(initialDefaults);
  let cancelToken = null;
  let pendingOptimisticCallback = null;
  const {
    form: baseForm,
    setDefaults,
    getTransform,
    getPrecognitionEndpoint,
    markAsSuccessful,
    wasDefaultsCalledInOnSuccess,
    resetDefaultsCalledInOnSuccess,
    setRememberExcludeKeys,
    resetBeforeSubmit,
    finishProcessing
  } = useFormState({
    data,
    rememberKey,
    precognitionEndpoint
  });
  const form = baseForm;
  const createSubmitMethod = (method) => (url, options = {}) => {
    form.submit(method, url, options);
  };
  Object.assign(form, {
    submit(...args2) {
      const { method, url, options } = UseFormUtils2.parseSubmitArguments(args2, getPrecognitionEndpoint());
      resetDefaultsCalledInOnSuccess();
      const _options = {
        ...options,
        onCancelToken: (token) => {
          cancelToken = token;
          return options.onCancelToken?.(token);
        },
        onBefore: (visit) => {
          resetBeforeSubmit();
          return options.onBefore?.(visit);
        },
        onStart: (visit) => {
          form.processing = true;
          return options.onStart?.(visit);
        },
        onProgress: (event) => {
          form.progress = event ?? null;
          return options.onProgress?.(event);
        },
        onSuccess: async (page2) => {
          markAsSuccessful();
          const onSuccess = options.onSuccess ? await options.onSuccess(page2) : null;
          if (!wasDefaultsCalledInOnSuccess()) {
            setDefaults(cloneDeep3(form.data()));
            form.isDirty = false;
          }
          return onSuccess;
        },
        onError: (errors) => {
          form.clearErrors().setError(errors);
          return options.onError?.(errors);
        },
        onCancel: () => {
          return options.onCancel?.();
        },
        onFinish: (visit) => {
          finishProcessing();
          cancelToken = null;
          return options.onFinish?.(visit);
        }
      };
      _options.optimistic = _options.optimistic ?? pendingOptimisticCallback ?? void 0;
      pendingOptimisticCallback = null;
      const transformedData = getTransform()(form.data());
      if (method === "delete") {
        router3.delete(url, { ..._options, data: transformedData });
      } else {
        router3[method](url, transformedData, _options);
      }
    },
    get: createSubmitMethod("get"),
    post: createSubmitMethod("post"),
    put: createSubmitMethod("put"),
    patch: createSubmitMethod("patch"),
    delete: createSubmitMethod("delete"),
    cancel() {
      if (cancelToken) {
        cancelToken.cancel();
      }
    },
    dontRemember(...keys) {
      setRememberExcludeKeys(keys);
      return form;
    },
    optimistic(callback) {
      pendingOptimisticCallback = callback;
      return form;
    }
  });
  return getPrecognitionEndpoint() ? form : form;
}

// src/app.ts
function isComponent(value) {
  if (!value) {
    return false;
  }
  if (typeof value === "function") {
    return true;
  }
  if (typeof value === "object") {
    const obj = value;
    return typeof obj.render === "function" || typeof obj.setup === "function" || typeof obj.template === "string" || "__file" in obj || "__name" in obj;
  }
  return false;
}
function isRenderFunction(value) {
  if (typeof value !== "function") {
    return false;
  }
  const fn = value;
  return fn.length === 2 && typeof fn.prototype === "undefined";
}
var component = ref2(void 0);
var page = ref2();
var pageAccessor = null;
var layout = shallowRef(null);
var key = ref2(void 0);
var headManager;
var App = defineComponent({
  name: "Inertia",
  props: {
    initialPage: {
      type: Object,
      required: true
    },
    initialComponent: {
      type: Object,
      required: false
    },
    resolveComponent: {
      type: Function,
      required: false
    },
    titleCallback: {
      type: Function,
      required: false,
      default: (title) => title
    },
    onHeadUpdate: {
      type: Function,
      required: false,
      default: () => () => {
      }
    },
    defaultLayout: {
      type: Function,
      required: false
    },
    serverHead: {
      type: [Boolean, String, Function],
      required: false
    }
  },
  setup({
    initialPage,
    initialComponent,
    resolveComponent,
    titleCallback,
    onHeadUpdate,
    defaultLayout,
    serverHead
  }) {
    component.value = initialComponent ? markRaw(initialComponent) : void 0;
    page.value = { ...initialPage, flash: initialPage.flash ?? {} };
    key.value = void 0;
    const isServer = typeof window === "undefined";
    headManager = createHeadManager(
      isServer,
      (title) => titleCallback ? titleCallback(title, page.value) : title,
      onHeadUpdate || (() => {
      }),
      resolveServerHead(initialPage, serverHead)
    );
    if (!isServer) {
      router4.init({
        initialPage,
        resolveComponent,
        swapComponent: async (options) => {
          if (!options.preserveState) {
            resetLayoutProps();
          }
          component.value = markRaw(options.component);
          page.value = options.page;
          key.value = options.preserveState ? key.value : Date.now();
        },
        onFlash: (flash) => {
          page.value = { ...page.value, flash };
        }
      });
      const syncServerHead = (event) => {
        headManager.updateServerHead(resolveServerHead(event.detail.page, serverHead));
      };
      router4.on("navigate", syncServerHead);
      router4.on("clientVisit", syncServerHead);
    }
    return () => {
      if (component.value) {
        component.value.inheritAttrs = !!component.value.inheritAttrs;
        const child = h(component.value, {
          ...page.value.props,
          key: key.value
        });
        if (layout.value) {
          component.value.layout = layout.value;
          layout.value = null;
        }
        if (component.value.layout && isRenderFunction(component.value.layout)) {
          return component.value.layout(h, child);
        }
        let effectiveLayout;
        let callbackProps = null;
        const layoutValue = component.value.layout;
        if (typeof layoutValue === "function" && layoutValue.length <= 1 && typeof layoutValue.prototype === "undefined") {
          const result = layoutValue(page.value.props);
          if (isPropsObjectOrCallback(result, isComponent)) {
            effectiveLayout = defaultLayout?.(page.value.component, page.value);
            callbackProps = result;
          } else {
            effectiveLayout = result;
          }
        } else if (isPropsObject(layoutValue, isComponent)) {
          effectiveLayout = defaultLayout?.(page.value.component, page.value);
          callbackProps = layoutValue;
        } else {
          effectiveLayout = layoutValue ?? defaultLayout?.(page.value.component, page.value);
        }
        if (effectiveLayout) {
          let layouts = normalizeLayouts(
            effectiveLayout,
            isComponent,
            component.value.layout && !callbackProps ? isRenderFunction : void 0
          );
          if (callbackProps) {
            layouts = layouts.map((l) => ({ ...l, props: { ...l.props, ...callbackProps } }));
          }
          if (layouts.length > 0) {
            const dynamicProps = isServer ? { shared: {}, named: {} } : state.value;
            return layouts.reduceRight((childNode, layout2) => {
              const layoutComponent = layout2.component;
              layoutComponent.inheritAttrs = !!layoutComponent.inheritAttrs;
              return h(
                layoutComponent,
                {
                  ...page.value.props,
                  ...layout2.props,
                  ...dynamicProps.shared,
                  ...layout2.name ? dynamicProps.named[layout2.name] || {} : {}
                },
                () => childNode
              );
            }, child);
          }
        }
        return child;
      }
    };
  }
});
var app_default = App;
var plugin = {
  install(app) {
    router4.form = useForm;
    Object.defineProperty(app.config.globalProperties, "$inertia", { get: () => router4 });
    Object.defineProperty(app.config.globalProperties, "$page", { get: () => page.value });
    Object.defineProperty(app.config.globalProperties, "$headManager", { get: () => headManager });
    app.mixin(remember_default);
  }
};
function usePage() {
  if (!pageAccessor) {
    pageAccessor = reactive2({
      props: computed(() => page.value?.props),
      url: computed(() => page.value?.url),
      component: computed(() => page.value?.component),
      version: computed(() => page.value?.version),
      clearHistory: computed(() => page.value?.clearHistory),
      deferredProps: computed(() => page.value?.deferredProps),
      rescuedProps: computed(() => page.value?.rescuedProps),
      mergeProps: computed(() => page.value?.mergeProps),
      prependProps: computed(() => page.value?.prependProps),
      deepMergeProps: computed(() => page.value?.deepMergeProps),
      matchPropsOn: computed(() => page.value?.matchPropsOn),
      rememberedState: computed(() => page.value?.rememberedState),
      encryptHistory: computed(() => page.value?.encryptHistory),
      scrollProps: computed(() => page.value?.scrollProps),
      flash: computed(() => page.value?.flash)
    });
  }
  return pageAccessor;
}

// src/createInertiaApp.ts
import {
  buildSSRBody,
  exposeInterceptors,
  getInitialPageFromDOM,
  http as httpModule,
  router as router5,
  setupProgress
} from "@inertiajs/core";
import { createApp, createSSRApp, h as h2 } from "vue";
async function createInertiaApp({
  id = "app",
  resolve,
  setup,
  title,
  progress: progress2 = {},
  page: page2,
  render,
  defaults = {},
  nonce,
  http: http3,
  layout: layout2,
  serverHead,
  withApp,
  dev = !!import.meta.env?.DEV
} = {}) {
  config.replace(defaults);
  if (nonce) {
    config.set("nonce", nonce);
  }
  if (http3) {
    httpModule.setClient(http3);
  }
  if (dev) {
    exposeInterceptors();
  }
  const isServer = typeof window === "undefined";
  const resolveComponent = (name, page3) => Promise.resolve(resolve(name, page3)).then((module) => module.default || module);
  if (isServer && !page2 && !render) {
    return async (page3, renderToString) => {
      let head2 = [];
      const initialComponent = await resolveComponent(page3.component, page3);
      const props = {
        initialPage: page3,
        initialComponent,
        resolveComponent,
        titleCallback: title,
        onHeadUpdate: (elements) => head2 = elements,
        defaultLayout: layout2,
        serverHead
      };
      let vueApp2;
      if (setup) {
        vueApp2 = setup({
          el: null,
          App: app_default,
          props,
          plugin
        });
      } else {
        vueApp2 = createSSRApp({ render: () => h2(app_default, props) });
        vueApp2.use(plugin);
        if (withApp) {
          withApp(vueApp2, { ssr: true, page: page3 });
        }
      }
      const html = await renderToString(vueApp2);
      const body = buildSSRBody(id, page3, html);
      return { head: head2, body };
    };
  }
  const initialPage = page2 || getInitialPageFromDOM(id);
  let head = [];
  const vueApp = await Promise.all([
    resolveComponent(initialPage.component, initialPage),
    router5.decryptHistory().catch(() => {
    })
  ]).then(([initialComponent]) => {
    const props = {
      initialPage,
      initialComponent,
      resolveComponent,
      titleCallback: title,
      onHeadUpdate: isServer ? (elements) => head = elements : void 0,
      defaultLayout: layout2,
      serverHead
    };
    if (isServer) {
      return setup({
        el: null,
        App: app_default,
        props,
        plugin
      });
    }
    const el = document.getElementById(id);
    if (setup) {
      return setup({
        el,
        App: app_default,
        props,
        plugin
      });
    }
    if (el.hasAttribute("data-server-rendered")) {
      const app = createSSRApp({ render: () => h2(app_default, props) });
      app.use(plugin);
      if (withApp) {
        withApp(app, { ssr: false, page: initialPage });
      }
      app.mount(el);
    } else {
      const app = createApp({ render: () => h2(app_default, props) });
      app.use(plugin);
      if (withApp) {
        withApp(app, { ssr: false, page: initialPage });
      }
      app.mount(el);
    }
  });
  if (!isServer && progress2) {
    setupProgress(progress2);
  }
  if (isServer && render && vueApp) {
    const html = await render(vueApp);
    const body = buildSSRBody(id, initialPage, html);
    return { head, body };
  }
}

// src/deferred.ts
import { isSameUrlWithoutQueryOrHash, partialReloadRequestsSomeProps, router as router6 } from "@inertiajs/core";
import { get as get2 } from "es-toolkit/compat";
import { computed as computed2, defineComponent as defineComponent2, Fragment, h as h3, onMounted, onUnmounted, ref as ref3 } from "vue";
var deferred_default = defineComponent2({
  name: "Deferred",
  props: {
    data: {
      type: [String, Array],
      required: true
    }
  },
  slots: Object,
  setup(props, { slots }) {
    const reloading = ref3(false);
    const activeReloads = /* @__PURE__ */ new Set();
    const page2 = usePage();
    const keys = computed2(() => Array.isArray(props.data) ? props.data : [props.data]);
    const rescuedKeys = computed2(() => new Set(page2.rescuedProps));
    let removeStartListener = null;
    let removeFinishListener = null;
    onMounted(() => {
      removeStartListener = router6.on("start", (e) => {
        const visit = e.detail.visit;
        if (visit.preserveState === true && isSameUrlWithoutQueryOrHash(visit.url, window.location) && partialReloadRequestsSomeProps(visit, keys.value)) {
          activeReloads.add(visit);
          reloading.value = true;
        }
      });
      removeFinishListener = router6.on("finish", (e) => {
        const visit = e.detail.visit;
        if (activeReloads.has(visit)) {
          activeReloads.delete(visit);
          reloading.value = activeReloads.size > 0;
        }
      });
    });
    onUnmounted(() => {
      removeStartListener?.();
      removeFinishListener?.();
      activeReloads.clear();
    });
    return () => {
      if (!slots.fallback) {
        throw new Error("`<Deferred>` requires a `<template #fallback>` slot");
      }
      const propsAreDefined = keys.value.every((key2) => get2(page2.props, key2) !== void 0);
      const hasRescuedProps = keys.value.some((key2) => rescuedKeys.value.has(key2));
      const slotProps = { reloading: reloading.value };
      if (propsAreDefined && !hasRescuedProps) {
        return h3(Fragment, { key: "default" }, slots.default?.(slotProps) ?? []);
      }
      if (hasRescuedProps && slots.rescue) {
        return h3(Fragment, { key: "rescue" }, slots.rescue(slotProps));
      }
      return h3(Fragment, { key: "fallback" }, slots.fallback({}));
    };
  }
});

// src/form.ts
import {
  config as config2,
  FormComponentResetSymbol,
  formDataToObject,
  isUrlMethodPair,
  mergeDataIntoQueryString,
  resetFormFields,
  resolveUrlMethodPairComponent,
  UseFormUtils as UseFormUtils3
} from "@inertiajs/core";
import { isEqual as isEqual2 } from "es-toolkit";
import {
  computed as computed3,
  defineComponent as defineComponent3,
  h as h4,
  inject,
  onBeforeUnmount,
  onMounted as onMounted2,
  provide,
  ref as ref4,
  watch as watch2
} from "vue";
var noop = () => void 0;
var FormContextKey = /* @__PURE__ */ Symbol("InertiaFormContext");
var Form = defineComponent3({
  name: "Form",
  slots: Object,
  props: {
    action: {
      type: [String, Object],
      default: ""
    },
    method: {
      type: String,
      default: "get"
    },
    headers: {
      type: Object,
      default: () => ({})
    },
    queryStringArrayFormat: {
      type: String,
      default: "brackets"
    },
    errorBag: {
      type: [String, null],
      default: null
    },
    showProgress: {
      type: Boolean,
      default: true
    },
    transform: {
      type: Function,
      default: (data) => data
    },
    options: {
      type: Object,
      default: () => ({})
    },
    resetOnError: {
      type: [Boolean, Array],
      default: false
    },
    resetOnSuccess: {
      type: [Boolean, Array],
      default: false
    },
    setDefaultsOnSuccess: {
      type: Boolean,
      default: false
    },
    onCancelToken: {
      type: Function,
      default: noop
    },
    onBefore: {
      type: Function,
      default: noop
    },
    onStart: {
      type: Function,
      default: noop
    },
    onProgress: {
      type: Function,
      default: noop
    },
    onFinish: {
      type: Function,
      default: noop
    },
    onCancel: {
      type: Function,
      default: noop
    },
    onSuccess: {
      type: Function,
      default: noop
    },
    onError: {
      type: Function,
      default: noop
    },
    onSubmitComplete: {
      type: Function,
      default: noop
    },
    disableWhileProcessing: {
      type: Boolean,
      default: false
    },
    cancelOnUnmount: {
      type: Boolean,
      default: false
    },
    invalidateCacheTags: {
      type: [String, Array],
      default: () => []
    },
    validateFiles: {
      type: Boolean,
      default: false
    },
    validationTimeout: {
      type: Number,
      default: 1500
    },
    optimistic: {
      type: Function,
      default: void 0
    },
    withAllErrors: {
      type: Boolean,
      default: null
    },
    component: {
      type: String,
      default: null
    },
    instant: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { slots, attrs, expose }) {
    const getTransformedData = () => {
      const [_url, data] = getUrlAndData();
      return props.transform(data);
    };
    const form = useForm({}).withPrecognition(
      () => method.value,
      () => getUrlAndData()[0]
    ).transform(getTransformedData).setValidationTimeout(props.validationTimeout);
    if (props.validateFiles) {
      form.validateFiles();
    }
    if (props.withAllErrors ?? config2.get("form.withAllErrors")) {
      form.withAllErrors();
    }
    const formElement = ref4();
    const method = computed3(
      () => isUrlMethodPair(props.action) ? props.action.method : props.method.toLowerCase()
    );
    const resolvedComponent = computed3(() => {
      if (props.component) {
        return props.component;
      }
      if (props.instant && isUrlMethodPair(props.action)) {
        return resolveUrlMethodPairComponent(props.action);
      }
      return null;
    });
    const isDirty = ref4(false);
    const defaultData = ref4(new FormData());
    const onFormUpdate = (event) => {
      if (event.type === "reset" && event.detail?.[FormComponentResetSymbol]) {
        event.preventDefault();
      }
      isDirty.value = event.type === "reset" ? false : !isEqual2(getData(), formDataToObject(defaultData.value));
    };
    const formEvents = ["input", "change", "reset"];
    onMounted2(() => {
      defaultData.value = getFormData();
      form.defaults(getData());
      formEvents.forEach((e) => formElement.value.addEventListener(e, onFormUpdate));
    });
    watch2(
      () => props.validateFiles,
      (value) => value ? form.validateFiles() : form.withoutFileValidation()
    );
    watch2(
      () => props.validationTimeout,
      (value) => form.setValidationTimeout(value)
    );
    onBeforeUnmount(() => {
      formEvents.forEach((e) => formElement.value?.removeEventListener(e, onFormUpdate));
      if (props.cancelOnUnmount) {
        form.cancel();
      }
    });
    const getFormData = (submitter) => new FormData(formElement.value, submitter);
    const getData = (submitter) => formDataToObject(getFormData(submitter));
    const getUrlAndData = (submitter) => {
      return mergeDataIntoQueryString(
        method.value,
        isUrlMethodPair(props.action) ? props.action.url : props.action,
        getData(submitter),
        props.queryStringArrayFormat
      );
    };
    const submit = (submitter) => {
      const [url, data] = getUrlAndData(submitter);
      const formTarget = submitter?.getAttribute("formtarget");
      if (formTarget === "_blank" && method.value === "get") {
        window.open(url, "_blank");
        return;
      }
      const maybeReset = (resetOption) => {
        if (!resetOption) {
          return;
        }
        if (resetOption === true) {
          reset();
        } else if (resetOption.length > 0) {
          reset(...resetOption);
        }
      };
      const submitOptions = {
        headers: props.headers,
        queryStringArrayFormat: props.queryStringArrayFormat,
        errorBag: props.errorBag,
        showProgress: props.showProgress,
        invalidateCacheTags: props.invalidateCacheTags,
        component: resolvedComponent.value,
        optimistic: props.optimistic ? (pageProps) => props.optimistic(pageProps, data) : void 0,
        onCancelToken: props.onCancelToken,
        onBefore: props.onBefore,
        onStart: props.onStart,
        onProgress: props.onProgress,
        onFinish: props.onFinish,
        onCancel: props.onCancel,
        onSuccess: async (...args) => {
          const result = await props.onSuccess?.(...args);
          props.onSubmitComplete?.(exposed);
          maybeReset(props.resetOnSuccess);
          if (props.setDefaultsOnSuccess === true) {
            defaults();
          }
          return result;
        },
        onError: (...args) => {
          props.onError?.(...args);
          maybeReset(props.resetOnError);
        },
        ...props.options
      };
      form.transform(() => props.transform(data)).submit(method.value, url, submitOptions);
      form.transform(getTransformedData);
    };
    const reset = (...fields) => {
      resetFormFields(formElement.value, defaultData.value, fields);
      form.reset(...fields);
    };
    const clearErrors = (...fields) => {
      form.clearErrors(...fields);
    };
    const resetAndClearErrors = (...fields) => {
      clearErrors(...fields);
      reset(...fields);
    };
    const defaults = () => {
      defaultData.value = getFormData();
      isDirty.value = false;
    };
    const exposed = {
      get errors() {
        return form.errors;
      },
      get hasErrors() {
        return form.hasErrors;
      },
      get processing() {
        return form.processing;
      },
      get progress() {
        return form.progress;
      },
      get wasSuccessful() {
        return form.wasSuccessful;
      },
      get recentlySuccessful() {
        return form.recentlySuccessful;
      },
      get validating() {
        return form.validating;
      },
      clearErrors,
      resetAndClearErrors,
      setError: (fieldOrFields, maybeValue) => form.setError(typeof fieldOrFields === "string" ? { [fieldOrFields]: maybeValue } : fieldOrFields),
      get isDirty() {
        return isDirty.value;
      },
      reset,
      submit,
      cancel: () => form.cancel(),
      defaults,
      getData,
      getFormData,
      // Precognition
      touch: form.touch,
      valid: form.valid,
      invalid: form.invalid,
      touched: form.touched,
      validate: (field, config3) => form.validate(...UseFormUtils3.mergeHeadersForValidation(field, config3, props.headers)),
      validator: () => form.validator()
    };
    expose(exposed);
    provide(FormContextKey, exposed);
    return () => {
      return h4(
        "form",
        {
          ...attrs,
          ref: formElement,
          action: isUrlMethodPair(props.action) ? props.action.url : props.action,
          method: method.value,
          onSubmit: (event) => {
            event.preventDefault();
            submit(event.submitter);
          },
          inert: props.disableWhileProcessing && form.processing
        },
        slots.default ? slots.default(exposed) : []
      );
    };
  }
});
function useFormContext() {
  return inject(FormContextKey);
}
function createForm() {
  return Form;
}
var form_default = Form;

// src/head.ts
import { escape } from "es-toolkit/compat";
import { defineComponent as defineComponent4, onBeforeUnmount as onBeforeUnmount2 } from "vue";
function isUnaryTag(node) {
  return typeof node.type === "string" && [
    "area",
    "base",
    "br",
    "col",
    "embed",
    "hr",
    "img",
    "input",
    "keygen",
    "link",
    "meta",
    "param",
    "source",
    "track",
    "wbr"
  ].indexOf(node.type) > -1;
}
function renderTagStart(node) {
  node.props = node.props || {};
  node.props["data-inertia"] = node.props["head-key"] !== void 0 ? node.props["head-key"] : "";
  const attrs = Object.keys(node.props).reduce((carry, name) => {
    const value = String(node.props[name]);
    if (["key", "head-key"].includes(name)) {
      return carry;
    } else if (value === "") {
      return carry + ` ${name}`;
    } else {
      return carry + ` ${name}="${escape(value)}"`;
    }
  }, "");
  return `<${String(node.type)}${attrs}>`;
}
function renderTagChildren(node) {
  const { children } = node;
  if (typeof children === "string") {
    return children;
  }
  if (Array.isArray(children)) {
    return children.reduce((html, child) => {
      return html + renderTag(child);
    }, "");
  }
  return "";
}
function isFunctionNode(node) {
  return typeof node.type === "function";
}
function isComponentNode(node) {
  return typeof node.type === "object";
}
function isCommentNode(node) {
  return /(comment|cmt)/i.test(node.type.toString());
}
function isFragmentNode(node) {
  return /(fragment|fgt|symbol\(\))/i.test(node.type.toString());
}
function isTextNode(node) {
  return /(text|txt)/i.test(node.type.toString());
}
function renderTag(node) {
  if (isTextNode(node)) {
    return String(node.children);
  } else if (isFragmentNode(node)) {
    return "";
  } else if (isCommentNode(node)) {
    return "";
  }
  let html = renderTagStart(node);
  if (node.children) {
    html += renderTagChildren(node);
  }
  if (!isUnaryTag(node)) {
    html += `</${String(node.type)}>`;
  }
  return html;
}
function addTitleElement(elements, title) {
  if (title && !elements.find((tag) => tag.startsWith("<title"))) {
    elements.push(`<title data-inertia="">${escape(title)}</title>`);
  }
  return elements;
}
function renderNodes(nodes, title) {
  const elements = nodes.flatMap((node) => resolveNode(node)).map((node) => renderTag(node)).filter((node) => node);
  return addTitleElement(elements, title);
}
function resolveNode(node) {
  if (isFunctionNode(node)) {
    return resolveNode(node.type());
  } else if (isComponentNode(node)) {
    console.warn(`Using components in the <Head> component is not supported.`);
    return [];
  } else if (isTextNode(node) && node.children) {
    return node;
  } else if (isFragmentNode(node) && node.children) {
    return node.children.flatMap((child) => resolveNode(child));
  } else if (isCommentNode(node)) {
    return [];
  } else {
    return node;
  }
}
var Head = defineComponent4({
  props: {
    title: {
      type: String,
      required: false
    }
  },
  setup(props, { slots }) {
    const provider = headManager.createProvider();
    onBeforeUnmount2(() => {
      provider.disconnect();
    });
    return () => {
      provider.update(renderNodes(slots.default ? slots.default() : [], props.title));
    };
  }
});
var head_default = Head;

// src/infiniteScroll.ts
import {
  getScrollableParent,
  useInfiniteScroll
} from "@inertiajs/core";
import { computed as computed4, defineComponent as defineComponent5, Fragment as Fragment2, h as h5, onMounted as onMounted3, onUnmounted as onUnmounted2, ref as ref5, watch as watch3 } from "vue";
var resolveHTMLElement = (value, fallback) => {
  if (!value) {
    return fallback;
  }
  if (typeof value === "string") {
    return document.querySelector(value);
  }
  if (typeof value === "function") {
    return value() || null;
  }
  return fallback;
};
var InfiniteScroll = defineComponent5({
  name: "InfiniteScroll",
  slots: Object,
  props: {
    data: {
      type: String,
      required: true
    },
    buffer: {
      type: Number,
      default: 0
    },
    onlyNext: {
      type: Boolean,
      default: false
    },
    onlyPrevious: {
      type: Boolean,
      default: false
    },
    as: {
      type: String,
      default: "div"
    },
    manual: {
      type: Boolean,
      default: false
    },
    manualAfter: {
      type: Number,
      default: 0
    },
    preserveUrl: {
      type: Boolean,
      default: false
    },
    reverse: {
      type: Boolean,
      default: false
    },
    autoScroll: {
      type: Boolean,
      default: void 0
    },
    itemsElement: {
      type: [String, Function, Object],
      default: null
    },
    startElement: {
      type: [String, Function, Object],
      default: null
    },
    endElement: {
      type: [String, Function, Object],
      default: null
    },
    params: {
      type: Object,
      default: () => ({})
    }
  },
  inheritAttrs: false,
  setup(props, { slots, attrs, expose }) {
    const itemsElementRef = ref5(null);
    const startElementRef = ref5(null);
    const endElementRef = ref5(null);
    const itemsElement = computed4(
      () => resolveHTMLElement(props.itemsElement, itemsElementRef.value)
    );
    const scrollableParent = computed4(() => getScrollableParent(itemsElement.value));
    const startElement = computed4(
      () => resolveHTMLElement(props.startElement, startElementRef.value)
    );
    const endElement = computed4(() => resolveHTMLElement(props.endElement, endElementRef.value));
    const loadingPrevious = ref5(false);
    const loadingNext = ref5(false);
    const requestCount = ref5(0);
    const hasPreviousPage = ref5(false);
    const hasNextPage = ref5(false);
    const syncStateFromDataManager = () => {
      requestCount.value = dataManager.getRequestCount();
      hasPreviousPage.value = dataManager.hasPrevious();
      hasNextPage.value = dataManager.hasNext();
    };
    const {
      dataManager,
      elementManager,
      flush: flushInfiniteScroll
    } = useInfiniteScroll({
      // Data
      getPropName: () => props.data,
      inReverseMode: () => props.reverse,
      shouldFetchNext: () => !props.onlyPrevious,
      shouldFetchPrevious: () => !props.onlyNext,
      shouldPreserveUrl: () => props.preserveUrl,
      getReloadOptions: () => props.params,
      // Elements
      getTriggerMargin: () => props.buffer,
      getStartElement: () => startElement.value,
      getEndElement: () => endElement.value,
      getItemsElement: () => itemsElement.value,
      getScrollableParent: () => scrollableParent.value,
      // Request callbacks
      onBeforePreviousRequest: () => loadingPrevious.value = true,
      onBeforeNextRequest: () => loadingNext.value = true,
      onCompletePreviousRequest: ({ completed }) => {
        loadingPrevious.value = false;
        if (completed) {
          syncStateFromDataManager();
        }
      },
      onCompleteNextRequest: ({ completed }) => {
        loadingNext.value = false;
        if (completed) {
          syncStateFromDataManager();
        }
      },
      onDataReset: syncStateFromDataManager
    });
    syncStateFromDataManager();
    if (typeof window === "undefined") {
      const scrollProp = usePage().scrollProps?.[props.data];
      if (scrollProp) {
        hasPreviousPage.value = !!scrollProp.previousPage;
        hasNextPage.value = !!scrollProp.nextPage;
      }
    }
    const autoLoad = computed4(() => !manualMode.value);
    const manualMode = computed4(
      () => props.manual || props.manualAfter > 0 && requestCount.value >= props.manualAfter
    );
    const scrollToBottom = () => {
      if (scrollableParent.value) {
        scrollableParent.value.scrollTo({
          top: scrollableParent.value.scrollHeight,
          behavior: "instant"
        });
      } else {
        window.scrollTo({
          top: document.body.scrollHeight,
          behavior: "instant"
        });
      }
    };
    onMounted3(() => {
      elementManager.setupObservers();
      elementManager.processServerLoadedElements(dataManager.getLastLoadedPage());
      const shouldAutoScroll = props.autoScroll !== void 0 ? props.autoScroll : props.reverse;
      if (shouldAutoScroll) {
        scrollToBottom();
      }
      if (autoLoad.value) {
        elementManager.enableTriggers();
      }
    });
    onUnmounted2(flushInfiniteScroll);
    watch3(
      () => [autoLoad.value, props.onlyNext, props.onlyPrevious],
      ([enabled]) => {
        enabled ? elementManager.enableTriggers() : elementManager.disableTriggers();
      }
    );
    expose({
      fetchNext: dataManager.fetchNext,
      fetchPrevious: dataManager.fetchPrevious,
      hasPrevious: dataManager.hasPrevious,
      hasNext: dataManager.hasNext
    });
    return () => {
      const renderElements = [];
      const sharedExposed = {
        loadingPrevious: loadingPrevious.value,
        loadingNext: loadingNext.value,
        hasPrevious: hasPreviousPage.value,
        hasNext: hasNextPage.value
      };
      if (!props.startElement) {
        const headerAutoMode = autoLoad.value && !props.onlyNext;
        const exposedPrevious = {
          loading: loadingPrevious.value,
          fetch: dataManager.fetchPrevious,
          autoMode: headerAutoMode,
          manualMode: !headerAutoMode,
          hasMore: hasPreviousPage.value,
          ...sharedExposed
        };
        renderElements.push(
          h5(
            "div",
            { ref: startElementRef },
            slots.previous ? slots.previous(exposedPrevious) : loadingPrevious.value ? slots.loading?.(exposedPrevious) : void 0
          )
        );
      }
      renderElements.push(
        h5(
          props.as,
          { ...attrs, ref: itemsElementRef },
          slots.default?.({
            loading: loadingPrevious.value || loadingNext.value,
            loadingPrevious: loadingPrevious.value,
            loadingNext: loadingNext.value
          })
        )
      );
      if (!props.endElement) {
        const footerAutoMode = autoLoad.value && !props.onlyPrevious;
        const exposedNext = {
          loading: loadingNext.value,
          fetch: dataManager.fetchNext,
          autoMode: footerAutoMode,
          manualMode: !footerAutoMode,
          hasMore: hasNextPage.value,
          ...sharedExposed
        };
        renderElements.push(
          h5(
            "div",
            { ref: endElementRef },
            slots.next ? slots.next(exposedNext) : loadingNext.value ? slots.loading?.(exposedNext) : void 0
          )
        );
      }
      return h5(Fragment2, {}, props.reverse ? [...renderElements].reverse() : renderElements);
    };
  }
});
var infiniteScroll_default = InfiniteScroll;

// src/link.ts
import {
  isUrlMethodPair as isUrlMethodPair2,
  mergeDataIntoQueryString as mergeDataIntoQueryString2,
  resolveUrlMethodPairComponent as resolveUrlMethodPairComponent2,
  router as router7,
  shouldIntercept,
  shouldNavigate
} from "@inertiajs/core";
import { computed as computed5, defineComponent as defineComponent6, h as h6, onMounted as onMounted4, onUnmounted as onUnmounted3, ref as ref6 } from "vue";
var noop2 = () => {
};
var Link = defineComponent6({
  name: "Link",
  props: {
    as: {
      type: [String, Object],
      default: "a"
    },
    data: {
      type: Object,
      default: () => ({})
    },
    href: {
      type: [String, Object],
      default: ""
    },
    method: {
      type: String,
      default: "get"
    },
    replace: {
      type: Boolean,
      default: false
    },
    preserveScroll: {
      type: [Boolean, String, Function],
      default: false
    },
    preserveState: {
      type: [Boolean, String, Function],
      default: null
    },
    preserveUrl: {
      type: Boolean,
      default: false
    },
    only: {
      type: Array,
      default: () => []
    },
    except: {
      type: Array,
      default: () => []
    },
    headers: {
      type: Object,
      default: () => ({})
    },
    queryStringArrayFormat: {
      type: String,
      default: "brackets"
    },
    async: {
      type: Boolean,
      default: false
    },
    prefetch: {
      type: [Boolean, String, Array],
      default: false
    },
    cacheFor: {
      type: [Number, String, Array],
      default: 0
    },
    onStart: {
      type: Function,
      default: noop2
    },
    onProgress: {
      type: Function,
      default: noop2
    },
    onFinish: {
      type: Function,
      default: noop2
    },
    onBefore: {
      type: Function,
      default: noop2
    },
    onCancel: {
      type: Function,
      default: noop2
    },
    onSuccess: {
      type: Function,
      default: noop2
    },
    onError: {
      type: Function,
      default: noop2
    },
    onCancelToken: {
      type: Function,
      default: noop2
    },
    onPrefetching: {
      type: Function,
      default: noop2
    },
    onPrefetched: {
      type: Function,
      default: noop2
    },
    cacheTags: {
      type: [String, Array],
      default: () => []
    },
    viewTransition: {
      type: [Boolean, Object],
      default: false
    },
    component: {
      type: String,
      default: null
    },
    instant: {
      type: Boolean,
      default: false
    },
    pageProps: {
      type: [Object, Function],
      default: null
    }
  },
  setup(props, { slots, attrs }) {
    const inFlightCount = ref6(0);
    const hoverTimeout = ref6();
    const prefetchModes = computed5(() => {
      if (props.prefetch === true) {
        return ["hover"];
      }
      if (props.prefetch === false) {
        return [];
      }
      if (Array.isArray(props.prefetch)) {
        return props.prefetch;
      }
      return [props.prefetch];
    });
    const cacheForValue = computed5(() => {
      if (props.cacheFor !== 0) {
        return props.cacheFor;
      }
      if (prefetchModes.value.length === 1 && prefetchModes.value[0] === "click") {
        return 0;
      }
      return config.get("prefetch.cacheFor");
    });
    onMounted4(() => {
      if (prefetchModes.value.includes("mount")) {
        prefetch();
      }
    });
    onUnmounted3(() => {
      clearTimeout(hoverTimeout.value);
    });
    const method = computed5(
      () => isUrlMethodPair2(props.href) ? props.href.method : (props.method ?? "get").toLowerCase()
    );
    const as = computed5(() => {
      if (typeof props.as !== "string" || props.as.toLowerCase() !== "a") {
        return props.as;
      }
      return method.value !== "get" ? "button" : props.as.toLowerCase();
    });
    const mergeDataArray = computed5(
      () => mergeDataIntoQueryString2(
        method.value,
        isUrlMethodPair2(props.href) ? props.href.url : props.href,
        props.data || {},
        props.queryStringArrayFormat
      )
    );
    const href = computed5(() => mergeDataArray.value[0]);
    const data = computed5(() => mergeDataArray.value[1]);
    const resolvedComponent = computed5(() => {
      if (props.component) {
        return props.component;
      }
      if (props.instant && isUrlMethodPair2(props.href)) {
        return resolveUrlMethodPairComponent2(props.href);
      }
      return null;
    });
    const elProps = computed5(() => {
      if (as.value === "button") {
        return { type: "button" };
      }
      if (as.value === "a" || typeof as.value !== "string") {
        return { href: href.value };
      }
      return {};
    });
    const baseParams = computed5(() => ({
      data: data.value,
      method: method.value,
      replace: props.replace,
      preserveScroll: props.preserveScroll,
      preserveState: props.preserveState ?? method.value !== "get",
      preserveUrl: props.preserveUrl,
      only: props.only,
      except: props.except,
      headers: props.headers,
      async: props.async,
      component: resolvedComponent.value,
      pageProps: props.pageProps
    }));
    const visitParams = computed5(() => ({
      ...baseParams.value,
      viewTransition: props.viewTransition,
      onCancelToken: props.onCancelToken,
      onBefore: props.onBefore,
      onStart: (visit) => {
        inFlightCount.value++;
        props.onStart?.(visit);
      },
      onProgress: props.onProgress,
      onFinish: (visit) => {
        inFlightCount.value--;
        props.onFinish?.(visit);
      },
      onCancel: props.onCancel,
      onSuccess: props.onSuccess,
      onError: props.onError
    }));
    const prefetch = () => {
      router7.prefetch(
        href.value,
        {
          ...baseParams.value,
          onPrefetching: props.onPrefetching,
          onPrefetched: props.onPrefetched
        },
        {
          cacheFor: cacheForValue.value,
          cacheTags: props.cacheTags
        }
      );
    };
    const regularEvents = {
      onClick: (event) => {
        if (shouldIntercept(event)) {
          event.preventDefault();
          router7.visit(href.value, visitParams.value);
        }
      }
    };
    const prefetchHoverEvents = {
      onMouseenter: () => {
        hoverTimeout.value = setTimeout(() => {
          prefetch();
        }, config.get("prefetch.hoverDelay"));
      },
      onMouseleave: () => {
        clearTimeout(hoverTimeout.value);
      },
      onClick: regularEvents.onClick
    };
    const prefetchClickEvents = {
      onMousedown: (event) => {
        if (shouldIntercept(event)) {
          event.preventDefault();
          prefetch();
        }
      },
      onKeydown: (event) => {
        if (shouldNavigate(event)) {
          event.preventDefault();
          prefetch();
        }
      },
      onMouseup: (event) => {
        if (shouldIntercept(event)) {
          event.preventDefault();
          router7.visit(href.value, visitParams.value);
        }
      },
      onKeyup: (event) => {
        if (shouldNavigate(event)) {
          event.preventDefault();
          router7.visit(href.value, visitParams.value);
        }
      },
      onClick: (event) => {
        if (shouldIntercept(event)) {
          event.preventDefault();
        }
      }
    };
    return () => {
      return h6(
        as.value,
        {
          ...attrs,
          ...elProps.value,
          "data-loading": inFlightCount.value > 0 ? "" : void 0,
          ...(() => {
            if (prefetchModes.value.includes("hover")) {
              return prefetchHoverEvents;
            }
            if (prefetchModes.value.includes("click")) {
              return prefetchClickEvents;
            }
            return regularEvents;
          })()
        },
        slots
      );
    };
  }
});
var link_default = Link;

// src/useHttp.ts
import {
  hasFiles,
  http,
  HttpCancelledError,
  HttpResponseError,
  mergeDataIntoQueryString as mergeDataIntoQueryString3,
  objectToFormData,
  UseFormUtils as UseFormUtils4
} from "@inertiajs/core";
import { cloneDeep as cloneDeep4 } from "es-toolkit";
import { toSimpleValidationErrors as toSimpleValidationErrors2 } from "laravel-precognition";
function useHttp(...args) {
  const { rememberKey, data, precognitionEndpoint } = UseFormUtils4.parseUseFormArguments(...args);
  let abortController = null;
  let pendingOptimisticCallback = null;
  const {
    form: baseForm,
    setDefaults,
    getTransform,
    getPrecognitionEndpoint,
    markAsSuccessful,
    wasDefaultsCalledInOnSuccess,
    resetDefaultsCalledInOnSuccess,
    setRememberExcludeKeys,
    resetBeforeSubmit,
    finishProcessing,
    withAllErrors
  } = useFormState({
    data,
    rememberKey,
    precognitionEndpoint
  });
  const form = baseForm;
  form.response = null;
  const submit = async (method, url, options) => {
    const onBefore = options.onBefore?.();
    if (onBefore === false) {
      return Promise.reject(new Error("Request cancelled by onBefore"));
    }
    resetDefaultsCalledInOnSuccess();
    resetBeforeSubmit();
    abortController = new AbortController();
    const cancelToken = {
      cancel: () => abortController?.abort()
    };
    options.onCancelToken?.(cancelToken);
    options.optimistic = options.optimistic ?? pendingOptimisticCallback ?? void 0;
    pendingOptimisticCallback = null;
    let snapshot;
    if (options.optimistic) {
      snapshot = cloneDeep4(form.data());
      const optimisticData = options.optimistic(cloneDeep4(snapshot));
      if (optimisticData) {
        Object.keys(optimisticData).forEach((key2) => {
          ;
          form[key2] = optimisticData[key2];
        });
      }
    }
    form.processing = true;
    options.onStart?.();
    const transform = getTransform();
    const transformedData = transform(form.data());
    const useFormData = hasFiles(transformedData);
    let requestUrl = url;
    let requestData;
    let contentType;
    if (method === "get") {
      const [urlWithParams] = mergeDataIntoQueryString3(
        method,
        url,
        transformedData
      );
      requestUrl = urlWithParams;
    } else {
      if (useFormData) {
        requestData = objectToFormData(transformedData);
      } else {
        requestData = JSON.stringify(transformedData);
        contentType = "application/json";
      }
    }
    try {
      const response = await http.getClient().request({
        method,
        url: requestUrl,
        data: requestData,
        headers: {
          Accept: "application/json",
          ...contentType ? { "Content-Type": contentType } : {},
          ...options.headers
        },
        signal: abortController.signal,
        onUploadProgress: (event) => {
          form.progress = event;
          options.onProgress?.(event);
        }
      });
      const responseData = response.data ? JSON.parse(response.data) : null;
      if (response.status >= 200 && response.status < 300) {
        markAsSuccessful();
        form.response = responseData;
        options.onSuccess?.(responseData, response);
        if (!wasDefaultsCalledInOnSuccess()) {
          setDefaults(cloneDeep4(form.data()));
        }
        form.isDirty = false;
        return responseData;
      }
      throw new HttpResponseError(`Request failed with status ${response.status}`, response, url);
    } catch (error) {
      if (snapshot) {
        Object.keys(snapshot).forEach((key2) => {
          ;
          form[key2] = snapshot[key2];
        });
      }
      if (error instanceof HttpResponseError) {
        if (error.response.status === 422) {
          const responseData = JSON.parse(error.response.data);
          const validationErrors = responseData.errors || {};
          const processedErrors = withAllErrors.enabled() ? validationErrors : toSimpleValidationErrors2(validationErrors);
          form.clearErrors().setError(processedErrors);
          options.onError?.(processedErrors);
          return void 0;
        }
        options.onHttpException?.(error.response);
        throw error;
      }
      if (error instanceof HttpCancelledError || error instanceof Error && error.name === "AbortError") {
        options.onCancel?.();
        throw new HttpCancelledError("Request was cancelled", url);
      }
      options.onNetworkError?.(error instanceof Error ? error : new Error("Unknown error"));
      throw error;
    } finally {
      finishProcessing();
      abortController = null;
      options.onFinish?.();
    }
  };
  const createSubmitMethod = (method) => async (url, options = {}) => {
    return submit(method, url, options);
  };
  Object.assign(form, {
    submit(...args2) {
      const parsed = UseFormUtils4.parseSubmitArguments(args2, getPrecognitionEndpoint());
      return submit(parsed.method, parsed.url, parsed.options);
    },
    get: createSubmitMethod("get"),
    post: createSubmitMethod("post"),
    put: createSubmitMethod("put"),
    patch: createSubmitMethod("patch"),
    delete: createSubmitMethod("delete"),
    cancel() {
      if (abortController) {
        abortController.abort();
      }
    },
    dontRemember(...keys) {
      setRememberExcludeKeys(keys);
      return form;
    },
    optimistic(callback) {
      pendingOptimisticCallback = callback;
      return form;
    },
    withAllErrors() {
      withAllErrors.enable();
      return form;
    }
  });
  const originalWithPrecognition = form.withPrecognition;
  form.withPrecognition = (...args2) => {
    originalWithPrecognition.call(form, ...args2);
    return form;
  };
  return getPrecognitionEndpoint() ? form : form;
}

// src/usePoll.ts
import { router as router8 } from "@inertiajs/core";
import { onMounted as onMounted5, onUnmounted as onUnmounted4, ref as ref7 } from "vue";
function usePoll(interval, requestOptions = {}, options = {
  keepAlive: false,
  autoStart: true
}) {
  const autoStart = options.autoStart ?? true;
  const { stop, start, destroy } = router8.poll(interval, requestOptions, {
    ...options,
    autoStart: false
  });
  const polling = ref7(autoStart);
  onMounted5(() => {
    if (autoStart) {
      start();
    }
  });
  onUnmounted4(() => {
    destroy();
  });
  return {
    polling,
    stop: () => {
      stop();
      polling.value = false;
    },
    start: () => {
      start();
      polling.value = true;
    }
  };
}

// src/usePrefetch.ts
import { router as router9 } from "@inertiajs/core";
import { onMounted as onMounted6, onUnmounted as onUnmounted5, ref as ref8 } from "vue";
function usePrefetch(options = {}) {
  const isPrefetching = ref8(false);
  const lastUpdatedAt = ref8(null);
  const isPrefetched = ref8(false);
  const cached = typeof window === "undefined" ? null : router9.getCached(window.location.pathname, options);
  const inFlight = typeof window === "undefined" ? null : router9.getPrefetching(window.location.pathname, options);
  lastUpdatedAt.value = cached?.staleTimestamp || null;
  isPrefetching.value = inFlight !== null;
  isPrefetched.value = cached !== null;
  let onPrefetchedListener;
  let onPrefetchingListener;
  onMounted6(() => {
    onPrefetchingListener = router9.on("prefetching", (e) => {
      if (e.detail.visit.url.pathname === window.location.pathname) {
        isPrefetching.value = true;
      }
    });
    onPrefetchedListener = router9.on("prefetched", (e) => {
      if (e.detail.visit.url.pathname === window.location.pathname) {
        isPrefetching.value = false;
        isPrefetched.value = true;
      }
    });
  });
  onUnmounted5(() => {
    onPrefetchedListener();
    onPrefetchingListener();
  });
  return {
    lastUpdatedAt,
    isPrefetching,
    isPrefetched,
    flush: () => router9.flush(window.location.pathname, options)
  };
}

// src/useRemember.ts
import { router as router10 } from "@inertiajs/core";
import { cloneDeep as cloneDeep5 } from "es-toolkit";
import { isReactive, reactive as reactive3, ref as ref9, watch as watch4 } from "vue";
function useRemember(data, key2) {
  if (typeof data === "object" && data !== null && data.__rememberable === false) {
    return data;
  }
  const restored = router10.restore(key2);
  const type = isReactive(data) ? reactive3 : ref9;
  const hasCallbacks = typeof data.__remember === "function" && typeof data.__restore === "function";
  const remembered = type(
    restored === void 0 ? data : hasCallbacks ? data.__restore(cloneDeep5(restored)) : cloneDeep5(restored)
  );
  watch4(
    remembered,
    (newValue) => {
      router10.remember(cloneDeep5(hasCallbacks ? data.__remember() : newValue), key2);
    },
    { immediate: true, deep: true }
  );
  return remembered;
}

// src/whenVisible.ts
import { router as router11 } from "@inertiajs/core";
import { get as get3 } from "es-toolkit/compat";
import { computed as computed6, defineComponent as defineComponent7, h as h7, nextTick, onUnmounted as onUnmounted6, ref as ref10, watch as watch5 } from "vue";
var whenVisible_default = defineComponent7({
  name: "WhenVisible",
  slots: Object,
  props: {
    data: {
      type: [String, Array]
    },
    params: {
      type: Object
    },
    buffer: {
      type: Number,
      default: 0
    },
    as: {
      type: String,
      default: "div"
    },
    always: {
      type: Boolean,
      default: false
    }
  },
  setup(props, { slots }) {
    const loaded = ref10(false);
    const fetching = ref10(false);
    const observer = ref10(null);
    const elementRef = ref10(null);
    const page2 = usePage();
    const keys = computed6(() => {
      return props.data ? Array.isArray(props.data) ? props.data : [props.data] : [];
    });
    function getReloadParams() {
      const reloadParams = { preserveErrors: true, ...props.params };
      if (props.data) {
        reloadParams.only = Array.isArray(props.data) ? props.data : [props.data];
      }
      return reloadParams;
    }
    function registerObserver() {
      if (typeof window === "undefined") {
        return;
      }
      observer.value?.disconnect();
      observer.value = new IntersectionObserver(
        (entries) => {
          if (!entries[0].isIntersecting) {
            return;
          }
          if (fetching.value) {
            return;
          }
          if (!props.always && loaded.value) {
            return;
          }
          fetching.value = true;
          const reloadParams = getReloadParams();
          router11.reload({
            ...reloadParams,
            onStart: (e) => {
              fetching.value = true;
              reloadParams.onStart?.(e);
            },
            onFinish: (e) => {
              loaded.value = true;
              fetching.value = false;
              reloadParams.onFinish?.(e);
              if (!props.always) {
                observer.value?.disconnect();
              }
            }
          });
        },
        {
          rootMargin: `${props.buffer}px`
        }
      );
      if (elementRef.value) {
        observer.value.observe(elementRef.value);
      }
    }
    watch5(
      () => keys.value.map((key2) => get3(page2.props, key2)),
      () => {
        const exists = keys.value.length > 0 && keys.value.every((key2) => get3(page2.props, key2) !== void 0);
        loaded.value = exists;
        if (exists && !props.always) {
          return;
        }
        if (!observer.value || !exists) {
          nextTick(registerObserver);
        }
      },
      { immediate: true }
    );
    onUnmounted6(() => {
      observer.value?.disconnect();
    });
    return () => {
      const els = [];
      if (props.always || !loaded.value) {
        els.push(h7(props.as, { ref: elementRef }));
      }
      if (!loaded.value) {
        els.push(slots.fallback ? slots.fallback({}) : null);
      } else if (slots.default) {
        els.push(slots.default({ fetching: fetching.value }));
      }
      return els;
    };
  }
});

// src/index.ts
var config = coreConfig.extend({});
export {
  app_default as App,
  deferred_default as Deferred,
  form_default as Form,
  head_default as Head,
  infiniteScroll_default as InfiniteScroll,
  link_default as Link,
  whenVisible_default as WhenVisible,
  config,
  createForm,
  createInertiaApp,
  http2 as http,
  progress,
  resetLayoutProps,
  router12 as router,
  setLayoutProps,
  useForm,
  useFormContext,
  useHttp,
  usePage,
  usePoll,
  usePrefetch,
  useRemember
};
//# sourceMappingURL=index.js.map
