@props(['label' => null, 'required' => false, 'name', 'type' => 'text', 'placeholder' => '', 'value' => '', 'small' => false])
<div class="rl-form-group">
    @if ($label)
        <label for="{{ $name }}" class="rl-label {{ $required ? 'rl-label--required' : '' }}">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        class="rl-input {{ $small ? 'rl-input--sm' : '' }} {{ $errors->has($name) ? 'rl-input--error' : '' }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
    @error($name)
        <div class="rl-form-errors mt-1">{{ $message }}</div>
    @enderror
</div>
