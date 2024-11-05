<div class="text-center" style="position: relative;min-height: 60px;">
    {{ $results->links() }}
    <div style="position: absolute;right:0;top: 0;">
        <label style="margin: 0;" for="per-page-select">{!! __('global.Rows_per_page') !!}</label><br />
        <x-laravel-additions::per-page :sizes="[0, 1, 15, 30, 50, 100, 250, 500]" :current="$perPage" style="width: auto;float: right;" id="per-page-select" />
    </div>
</div>
@yield('content')
<div class="text-center">
    {{ $results->links() }}
</div>
