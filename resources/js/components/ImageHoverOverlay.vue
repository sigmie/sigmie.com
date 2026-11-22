<template>
    <div class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100 motion-reduce:transition-none">
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/65 via-black/25 to-transparent"></div>
        <div class="absolute bottom-3 left-3 translate-y-1 transition-transform duration-200 ease-out group-hover:translate-y-0 motion-reduce:transform-none motion-reduce:transition-none">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-[12px] font-medium text-graphite shadow-md backdrop-blur-sm">
                Find similar
                <span aria-hidden="true">→</span>
            </span>
        </div>
    </div>
</template>
