@props([
    'plate' => '',
    'size' => 'md',
])

@php
    $rawPlate = trim((string) $plate);
    $plateParts = str_contains($rawPlate, '-')
        ? array_values(array_filter(array_map('trim', explode('-', $rawPlate)), fn ($part) => $part !== ''))
        : [];

    if (count($plateParts) >= 4) {
        [$leftDigits, $letter, $rightDigits, $cityCode] = array_slice($plateParts, 0, 4);
    } else {
        preg_match_all('/(\d+)|([^\d\s]+)/u', $rawPlate, $matches);
        $tokens = $matches[0] ?? [];
        $leftDigits = $tokens[0] ?? '';
        $letter = $tokens[1] ?? '';
        $rightDigits = $tokens[2] ?? ($rawPlate ?: '---');
        $cityCode = $tokens[3] ?? '--';
    }
@endphp

<span
    dir="ltr"
    title="{{ $rawPlate ?: 'بدون پلاک' }}"
    {{ $attributes->merge(['class' => 'iran-plate iran-plate--' . $size]) }}
>
    <span class="iran-plate__blue" aria-hidden="true">
        <span class="iran-plate__flag"><i></i><i></i><i></i></span>
        <span class="iran-plate__en"><b>I.R.</b><b>IRAN</b></span>
    </span>
    <span class="iran-plate__main">
        @if($leftDigits !== '')
            <b>{{ $leftDigits }}</b>
        @endif
        @if($letter !== '')
            <b class="iran-plate__letter">{{ $letter }}</b>
        @endif
        <b>{{ $rightDigits }}</b>
    </span>
    <span class="iran-plate__city">
        <small>ایران</small>
        <b>{{ $cityCode }}</b>
    </span>
    <span class="sr-only">{{ $rawPlate }}</span>
</span>
