<?php

namespace Kroesen\LaravelAdditions\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Kroesen\LaravelAdditions\Models\Contenter\ContenterField;
use Kroesen\LaravelAdditions\Models\ContenterListData;

class Contenter implements ContenterInterface
{
    protected string $listKey;
    protected array $fields;
    protected array $listData = [];
    protected array $defaultSorting = [];
    protected array $rawSorting = [];
    protected array $multipleSorting = [];
    protected null|Builder $builder = null;
    protected null|Collection $results = null;

    public static function create(
        string $listKey,
        array  $fields,
        array  $defaultSorting,
    ): static {
        $self = new static();
        $self->listKey = $listKey;
        $self->fields = $fields;
        $self->listData = $self->getListData($listKey);

        /** @var ContenterField $field */
        foreach ($fields as $field){
            if(!isset($self->listData[$field->getName()])){
                $self->listData[$field->getName()] = $field->getDefault();
            }
        }
        if(isset($defaultSorting['raw'])){
            $self->rawSorting = $defaultSorting['raw'];
            foreach ($self->rawSorting as $k => $value) {
                if ($value instanceof \Illuminate\Contracts\Database\Query\Builder) {
                    $self->rawSorting[$k] = "({$value->toRawSql()})";
                }
            }
            unset($defaultSorting['raw']);
        }
        if(isset($defaultSorting['multiple'])){
            $self->multipleSorting = $defaultSorting['multiple'];
            unset($defaultSorting['multiple']);
        }
        $self->defaultSorting = $defaultSorting;

        return $self;
    }

    public function getListData(string $keyName): array
    {
        return \Request::get('list-data', ContenterListData::getListData($keyName)) ?? [];
    }

    public function getOrDefault(string $name, mixed $default): mixed
    {
        return $listData[$name] ?? $default;
    }

    public function getViewData(array $extra): array
    {
        return array_merge($extra, $this->listData);
    }

    public function handleRequest(Builder $builder, Request $request): void
    {
        // First sorting, then filters so the filters can manipulate sorting
        $sorting = $request->post('sorting', $this->listData['sorting'] ?? $this->defaultSorting);
        $this->applySortingToQuery($builder, $sorting, true);

        $fieldData = [];
        foreach ($this->fields as $field){
            $fieldData[$field->getName()] = $request->post($field->getName(), $field->getDefault());
        }

        $this->applyFiltersToQuery($builder, $fieldData, true);

        $this->builder = $builder;
    }

    public function setBuilder(Builder $builder): void
    {
        $this->builder = $builder;
    }

    public function applyFiltersToQuery(Builder $builder, array $fieldData = [], bool $save = false): void
    {
        /** @var ContenterField $field */
        foreach ($this->fields as $field){
            $data = $fieldData[$field->getName()] ?? $this->listData[$field->getName()] ?? $field->getDefault();
            $doAction = false;
            if(!$field->isApplicable($builder, $data)){
                $default = $field->getDefault();
                if($data !== $default && $field->isApplicable($builder, $default)){
                    $doAction = true;
                }
                $data = $default;
            }else{
                $doAction = true;
            }

            if($doAction && is_callable($field->action)){
                call_user_func($field->action, $builder, $data);
            }
            if($save){
                $this->listData[$field->getName()] = $data;
            }
        }
    }

    public function applySortingToQuery(Builder $builder, null|array|string $sorting = null, bool $save = false): void
    {
        $sorting = $sorting ?? $this->listData['sorting'] ?? $this->defaultSorting;
        try{
            if(!is_array($sorting)){
                $sorting = json_decode($sorting, true);
            }
        } catch (\Exception $e) {
            $sorting = $this->defaultSorting;
        }
        if (!isset($sorting['field']) || !isset($sorting['direction'])) {
            $sorting = $this->defaultSorting;
        }
        if($save){
            $this->listData['sorting'] = $sorting;
        }

        if(isset($this->multipleSorting[$sorting['field']])){
            foreach ($this->multipleSorting[$sorting['field']] as $key => $value){
                $field = $value;
                $reverse = false;
                if(is_bool($value)){
                    $field = $key;
                    $reverse = $value;
                }

                $direction = $sorting['direction'];
                if($reverse){
                    if(strtolower($direction) === 'desc'){
                        $direction = 'asc';
                    }else{
                        $direction = 'desc';
                    }
                }
                if(isset($this->rawSorting[$field])){
                    $builder->orderByRaw($this->formatRawSorting($this->rawSorting[$field], $direction));
                }else{
                    $builder->orderBy($field, $direction);
                }
            }
        }elseif(isset($this->rawSorting[$sorting['field']])){
            $builder->orderByRaw($this->formatRawSorting($this->rawSorting[$sorting['field']], $sorting['direction']));
        }else{
            $builder->orderBy($sorting['field'], $sorting['direction']);
        }
    }

    public function response(View $view): Response
    {
        $data = $this->listData;
        config(['app.list_data' => $this->listData]);
        if($this->builder !== null){
            $data['results'] = $this->builder->paginate($this->listData['perPage']);
            $data['results']->setPath('#');
        }
        $data = array_merge($data, $view->getData());
        foreach ($data as $key => $value){
            $data[Str::camel($key)] = $value;
        }
        $response = \Response::make($view->with($data));
        $response->header('Ajax-Number', \Request::header('Ajax-Number'));
        ContenterListData::saveListData($this->listKey, $this->listData);
        return $response;
    }

    public function formatRawSorting(string $sort, string $direction): string
    {
        if($direction !== 'asc'){
            // Change min, max, greatest and least to opposite
            $sort = str_replace(['min(','MIN('], 'placeholder_min(', $sort);
            $sort = str_replace(['max(', 'MAX('], 'MIN(', $sort);
            $sort = str_replace('placeholder_min(', 'MAX(', $sort);
            $sort = str_replace(['greatest(', 'GREATEST('], 'placeholder_greatest(', $sort);
            $sort = str_replace(['least(', 'LEAST('], 'GREATEST(', $sort);
            $sort = str_replace('placeholder_greatest(', 'LEAST(', $sort);
        }
        return $sort . ' ' . $direction;
    }
}
