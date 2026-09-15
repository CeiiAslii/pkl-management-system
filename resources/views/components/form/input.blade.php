@props(['label', 'name', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])

<label for="{{ $attributes->get('id', $name) }}" class="block text-sm font-semibold text-slate-700">
    {{ $label }}
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes->merge(['id' => $name, 'class' => 'form-control mt-2']) }}>
    @if ($help)<span class="mt-1.5 block text-xs font-normal leading-5 text-slate-500">{{ $help }}</span>@endif
    @error($name)<span class="mt-1.5 block text-xs font-medium text-rose-700">{{ $message }}</span>@enderror
</label>
