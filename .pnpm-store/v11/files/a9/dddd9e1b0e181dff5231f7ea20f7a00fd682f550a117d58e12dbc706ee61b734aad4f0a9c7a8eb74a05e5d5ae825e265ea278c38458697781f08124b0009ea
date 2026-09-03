import { PollOptions, ReloadOptions } from '@inertiajs/core';
import { Ref } from 'vue';
export default function usePoll(interval: number, requestOptions?: ReloadOptions | (() => ReloadOptions), options?: PollOptions): {
    stop: VoidFunction;
    start: VoidFunction;
    polling: Ref<boolean>;
};
