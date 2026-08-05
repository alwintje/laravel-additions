@php
    use Illuminate\Support\Str;

    /** @var \Kroesen\LaravelAdditions\Models\Chart\ChartDataInterface $labels */
    /** @var \Kroesen\LaravelAdditions\Models\Chart\ChartDataInterface[] $datasets */


@endphp


@if(!empty($title))
    <h3 class="text-center justify-content-center">{{ $title }}</h3>
@endif
<canvas id="{{ $id }}"></canvas>

<script>

    new Chart(document.getElementById('{{ $id }}'), {
        type: '{{ $type }}',
        data: {
            labels: ['{!! implode("','", $labels->getData($results)) !!}'],
            datasets: [

                @foreach($datasets as $k => $dataset)
                {
                    label: '{{ $dataset->getName() }}',
                    data: [
                        @foreach($dataset->getData($results) as $value)
                            '{{ $value }}' {{ $loop->last ? '' : ',' }}
                        @endforeach
                    ],
                    borderWidth: 1
                },
                @endforeach
            ]
        }
    });

</script>
