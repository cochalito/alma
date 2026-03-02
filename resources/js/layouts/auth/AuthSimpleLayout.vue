<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10"
    >
        <!-- Soft background pattern/gradient based on brand -->
        <div class="absolute inset-0 -z-10 bg-zinc-50 dark:bg-zinc-950">
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"
            ></div>
            <div
                class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
            >
                <div
                    class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#7FC31F] to-[#a3e635] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                ></div>
            </div>
        </div>

        <div class="z-10 w-full max-w-md">
            <div
                class="flex flex-col gap-6 rounded-2xl border border-border bg-card p-8 shadow-2xl transition-all duration-300 md:p-10"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="group flex flex-col items-center gap-2 font-medium"
                    >
                        <div class="mb-2 flex items-center justify-center">
                            <img
                                src="https://www.almudenahotel.com/img/logo.png"
                                alt="Alma Hotel Logo"
                                class="h-28 w-auto object-contain drop-shadow-sm transition-transform duration-300 group-hover:scale-105"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="mt-2 space-y-1.5 text-center">
                        <h1
                            class="text-2xl font-bold tracking-tight text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <!-- Line Separator before exact slot content -->
                <div class="my-2 h-px w-full bg-border/40"></div>
                <slot />
            </div>
        </div>
    </div>
</template>
