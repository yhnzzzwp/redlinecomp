@props(['label' => null, 'required' => false, 'name', 'options' => [], 'selected' => null, 'placeholder' => null, 'small' => false])
<div class="rl-form-group">
    @if ($label)
        <label for="{{ $name }}" class="rl-label {{ $required ? 'rl-label--required' : '' }}">{{ $label }}</label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="rl-select {{ $small ? 'rl-select--sm' : '' }} {{ $errors->has($name) ? 'rl-select--error' : '' }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $val => $text)
            <option value="{{ $val }}" @selected(old($name, $selected) == $val)>{{ $text }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($name)
        <div class="rl-form-errors mt-1">{{ $message }}</div>
    @enderror
</div>
