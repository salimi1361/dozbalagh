@props([
    'title',
    'subtitle' => null,
    'icon' => 'document',
])

<section {{ $attributes->class(['company-page-header relative overflow-hidden rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5']) }}>
    <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex min-w-0 items-center gap-3.5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white bg-opacity-15 ring-1 ring-white ring-opacity-20">
                @switch($icon)
                    @case('location')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.25" stroke-width="1.8"/></svg>
                        @break
                    @case('users')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-2-11.96a4 4 0 0 1 0 7.75"/></svg>
                        @break
                    @case('truck')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h11v11H3zM14 10h4l3 3v4h-7zM7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                        @break
                    @case('message')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
                        @break
                    @case('report')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m6 10V5m6 14v-7m4 7H2"/></svg>
                        @break
                    @default
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h6m-6 4h6"/></svg>
                @endswitch
            </div>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-black sm:text-2xl">{{ $title }}</h1>
                @if($subtitle)<p class="mt-1 text-xs font-semibold text-blue-100">{{ $subtitle }}</p>@endif
            </div>
        </div>
        @isset($actions)
            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">{{ $actions }}</div>
        @endisset
    </div>
</section>
