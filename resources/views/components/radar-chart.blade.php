@php
    // Geometry: hexagonal radar with percentage rings.
    $count = max(3, count($data));
    $cx = 160;
    $cy = 140;
    $radius = 92;

    $angleFor = fn (int $index): float => deg2rad(-90 + $index * (360 / $count));

    $vertex = function (int $index, float $pct) use ($angleFor, $cx, $cy, $radius): array {
        $angle = $angleFor($index);
        $distance = $radius * max(0, min(100, $pct)) / 100;

        return [
            round($cx + $distance * cos($angle), 1),
            round($cy + $distance * sin($angle), 1),
        ];
    };

    $ringPolygon = function (float $pct) use ($count, $vertex): string {
        return collect(range(0, $count - 1))
            ->map(fn (int $index) => implode(',', $vertex($index, $pct)))
            ->implode(' ');
    };

    $dataPolygon = collect($data)
        ->map(fn (array $point, int $index) => implode(',', $vertex($index, (float) ($point['value'] ?? 0))))
        ->implode(' ');
@endphp

<svg viewBox="0 0 320 280" class="w-full h-full" role="img" aria-label="Category strength profile">
    {{-- Grid rings --}}
    @foreach ([25, 50, 75, 100] as $ring)
        <polygon points="{{ $ringPolygon($ring) }}"
                 fill="none"
                 stroke="#cbd5e1"
                 stroke-width="1"
                 stroke-dasharray="3 3"/>
    @endforeach

    {{-- Axes --}}
    @foreach (range(0, $count - 1) as $index)
        @php
            [$x, $y] = $vertex($index, 100);
        @endphp
        <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $x }}" y2="{{ $y }}" stroke="#cbd5e1" stroke-width="1" stroke-dasharray="3 3"/>
    @endforeach

    {{-- Data shape --}}
    <polygon points="{{ $dataPolygon }}"
             fill="#1a365d"
             fill-opacity="0.25"
             stroke="#002045"
             stroke-width="2"
             stroke-linejoin="round"/>

    {{-- Vertex dots --}}
    @foreach (range(0, $count - 1) as $index)
        @php
            [$x, $y] = $vertex($index, (float) ($data[$index]['value'] ?? 0));
        @endphp
        <circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" fill="#fcb812" stroke="#002045" stroke-width="1.5"/>
    @endforeach

    {{-- Labels --}}
    @foreach ($data as $index => $point)
        @php
            $angle = $angleFor($index);
            $labelX = $cx + ($radius + 18) * cos($angle);
            $labelY = $cy + ($radius + 18) * sin($angle);

            $cosValue = cos($angle);

            $anchor = match (true) {
                $cosValue > 0.3 => 'start',
                $cosValue < -0.3 => 'end',
                default => 'middle',
            };
        @endphp
        <text x="{{ round($labelX) }}"
              y="{{ round($labelY + 4) }}"
              text-anchor="{{ $anchor }}"
              font-size="11"
              font-weight="500"
              fill="#43474e">
            {{ \Illuminate\Support\Str::limit($point['name'] ?? '', 16, '') }}
        </text>
    @endforeach
</svg>
