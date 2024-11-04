<?php

namespace Kroesen\LaravelAdditions\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Kroesen\LaravelAdditions\Models\Contenter\ContenterField;

class Contenter implements ContenterInterface
{
    protected string $cookieName;
    protected array $fields;
    protected array $listData = [];
    protected array $defaultSorting = [];
    protected array $rawSorting = [];
    protected null|Builder $builder = null;
    protected null|Collection $results = null;

    public static function create(
        string $cookieName,
        array  $fields,
        array  $defaultSorting,
    ): static {
        $self = new static();
        $self->cookieName = $cookieName;
        $self->fields = $fields;
        $self->listData = \Request::get('list-data', json_decode(Cookie::get($cookieName, '{}'), true));

        /** @var ContenterField $field */
        foreach ($fields as $field){
            if(!isset($self->listData[$field->getName()])){
                $self->listData[$field->getName()] = $field->getDefault();
            }
        }
        if(isset($defaultSorting['raw'])){
            $self->rawSorting = $defaultSorting['raw'];
            unset($defaultSorting['raw']);
        }
        $self->defaultSorting = $defaultSorting;

        return $self;
    }


    public function getOrDefault(string $name, mixed $default)
    {
        return $listData[$name] ?? $default;
    }

    public function getViewData(array $extra): array
    {
        return array_merge($extra, $this->listData);
    }

    public function handleRequest(Builder $builder, Request $request)
    {
        /** @var ContenterField $field */
        foreach ($this->fields as $field){
            $data = $request->post($field->getName(), $field->getDefault());
            if ( (
                    is_bool($field->applicable)
                    && $field->applicable
                ) || (
                    is_callable($field->applicable)
                    && call_user_func($field->applicable, $builder, $data)
                )
            ) {
                if(is_callable($field->action)){
                    call_user_func($field->action, $builder, $data);
                }
                $this->listData[$field->getName()] = $data;
            }
        }
        $sorting = $request->post('sorting', $this->listData['sorting'] ?? $this->defaultSorting);
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
        $this->listData['sorting'] = $sorting;
        if(isset($this->rawSorting[$sorting['field']])){
            $builder->orderByRaw($this->rawSorting[$sorting['field']].' '.$sorting['direction']);
        }else{
            $builder->orderBy($sorting['field'], $sorting['direction']);
        }

        $this->builder = $builder;
    }

    public function response(View $view): Response
    {
        $data = $this->listData;
        if($this->builder !== null){
            $data['results'] = $this->builder->paginate($this->listData['perPage']);
            $data['results']->setPath('#');
        }
        $data = array_merge($data, $view->getData());
        $response = \Response::make($view->with($data));
        $response->header('Ajax-Number', \Request::header('Ajax-Number'));
        $this->saveData();
        return $response;
    }

    private function saveData()
    {
        // Store for 2 weeks
        Cookie::queue($this->cookieName, json_encode($this->listData), 60*24*14);
    }
}
