@props(['label', 'name', 'help' => null, 'required' => false])

<label for="{{ $attributes->get('id', $name) }}" class="block text-sm font-semibold text-slate-700">
    {{ $label }}
    <select name="{{ $name }}" @required($required) {{ $attributes->merge(['id' => $name, 'class' => 'form-control mt-2']) }}>{{ $slot }}</select>
    @if ($help)<span class="mt-1.5 block text-xs font-normal leading-5 text-slate-500">{{ $help }}</span>@endif
    @error($name)<span class="mt-1.5 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
</label>
