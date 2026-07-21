@props(['label' => null, 'required' => false, 'name', 'rows' => 3, 'placeholder' => '', 'value' => ''])
<div class="rl-form-group">
    @if ($label)
        <label for="{{ $name }}" class="rl-label {{ $required ? 'rl-label--required' : '' }}">{{ $label }}</label>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        class="rl-textarea {{ $errors->has($name) ? 'rl-input--error' : '' }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="rl-form-errors mt-1">{{ $message }}</div>
    @enderror
</div>
