@props(['message'])

<div {{ $attributes->class(['rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500']) }}>
    {{ $message }}
</div>
