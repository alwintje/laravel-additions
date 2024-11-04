<select name="{{ $name ?? 'per-page' }}" {{ $attributes->merge(['class' => 'form-control form-control-sm', 'style' => 'min-width: 70px;']) }}>
    @foreach($sizes as $size)
        <option value="{{ $size }}" @selected($size === $current)>{{ $size }}</option>
    @endforeach
</select>
