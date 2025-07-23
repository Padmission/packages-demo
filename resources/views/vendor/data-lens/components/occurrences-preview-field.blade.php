<x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field"
>
    <div
            x-data="{
            occurrences: @js($getState() ?? []),
            init() {
                $wire.on('update-occurrences-preview', ({ occurrences }) => {
                    if (occurrences) {
                        this.occurrences = occurrences;
                    }
                });
            }
        }"
    >
        <template x-if="occurrences && occurrences.length">
            <div>
                <div class="mb-2 text-sm font-medium text-indigo-800 dark:text-indigo-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span x-text="`Next ${occurrences.length} Occurrences`"></span>
                </div>

                <div class="space-y-2">
                    <template x-for="(occurrence, index) in occurrences" :key="index">
                        <div class="py-2 px-3 rounded border" :class="index === 0
                            ? 'bg-white dark:bg-gray-700 border-indigo-300 dark:border-indigo-500 shadow-sm'
                            : 'bg-white/60 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700'">

                            <div class="flex justify-between items-center">
                                <span class="text-sm" :class="index === 0 ? 'font-medium' : ''">
                                    <span x-text="occurrence.formatted_date"></span> at <span
                                            x-text="occurrence.formatted_time_with_tz"></span>
                                </span>
                                <template x-if="index === 0">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">NEXT</span>
                                </template>
                            </div>

                            <template x-if="occurrence.is_today">
                                <div class="text-xs mt-1"><span
                                            class="text-green-600 dark:text-green-400 font-medium">Today</span></div>
                            </template>
                            <template x-if="!occurrence.is_today && occurrence.is_tomorrow">
                                <div class="text-xs mt-1"><span class="text-blue-600 dark:text-blue-400 font-medium">Tomorrow</span>
                                </div>
                            </template>
                            <template
                                    x-if="!occurrence.is_today && !occurrence.is_tomorrow && occurrence.days_diff > 0">
                                <div class="text-xs mt-1"><span class="text-gray-600 dark:text-gray-400">In <span
                                                x-text="occurrence.days_diff"></span> <span x-text="occurrence.days_diff === 1 ? 'day' : 'days'"></span></span></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
