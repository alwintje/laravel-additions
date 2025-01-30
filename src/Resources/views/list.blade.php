<div class="text-center" @if(isset($perPage)) style="position: relative;min-height: 60px;" @endif >
    {{ $results->links() }}
    @if(isset($perPage))
    <div style="position: absolute;right:0;top: 0;">
        <label style="margin: 0;" for="per-page-select">{!! __('global.Rows_per_page') !!}</label><br />
        <x-laravel-additions::per-page :sizes="$perPageOptions ?? [15, 30, 50, 100, 250, 500]" :current="$perPage" style="width: auto;float: right;" class="contenter-per-page-select" />
    </div>
    @endif
</div>
@yield('content')
<div class="text-center">
    {{ $results->links() }}
</div>
