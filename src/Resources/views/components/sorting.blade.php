@php
    $sort = $sort ?? json_decode(Request::query('sort', ''), true);
    $activeDesc = false;
    $activeAsc = false;
    if($sort !== false && is_array($sort) && $sort['field'] === $field){
        if(strtolower($sort['direction']) === 'desc'){
            $activeDesc = true;
        }else{
            $activeAsc = true;
        }
    }
@endphp
<a href="#" data-field="{{ $field }}" data-direction="asc" class="{{ $activeAsc ? 'sorting-active' : '' }} contenter-sort"><i class="fa fa-caret-up"></i></a><a href="#" data-field="{{ $field }}" data-direction="desc" class="{{ $activeDesc ? 'sorting-active' : '' }} contenter-sort"><i class="fa fa-caret-down"></i></a>
