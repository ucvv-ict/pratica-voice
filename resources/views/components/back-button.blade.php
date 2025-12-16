@props(['href'])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'back-button inline-flex items-center gap-2 px-3 py-1 text-sm font-medium rounded transition hover:no-underline']) }}>
    ⬅ {{ $slot }}
</a>
