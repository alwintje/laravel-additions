<?php

namespace Kroesen\LaravelAdditions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ArchiveBuilder extends Builder
{

    protected Model $archiveModel;

    protected Builder $baseBuilder;

    public function __call($method, $parameters)
    {
        $this->baseBuilder->__call($method, $parameters);
        return parent::__call($method, $parameters);
    }

    public static function createFromBuilder(Builder $builder){
        $self = new static($builder->getQuery());
        foreach (get_object_vars($builder) as $key => $value){
            $self->$key = $value;
        }
        $self->baseBuilder = $builder;
        $self->archiveModel = new (config('laravel_additions.models.'.$builder->getModel()::class.'.archive_model'));
        return $self;
    }

    public function get($columns = ['*']): Collection|array
    {
        $select = $this->baseBuilder->clone();
        $offset = $this->getQuery()->offset;
        $limit = $this->getQuery()->limit;
        if(null !== $limit){
            $queryLimit = $offset+$limit;
            $select->offset(0)->limit($queryLimit);
        }

        $archiveBuilder = $this->baseBuilder->clone();
        $archiveBuilder->setModel($this->archiveModel);
        $archiveBuilder->getQuery()->connection = $this->archiveModel->getConnection();

        $results = $this->baseBuilder->get($columns)->merge($archiveBuilder->get($columns));
        $results = $this->applyOrderBy($results, $this->getQuery());
        $results = $this->applyOffsetAndLimit($results, $offset, $limit);

        return $results;
    }

    public function find($id, $columns = ['*'])
    {
        $find = $this->baseBuilder->find($id, $columns);
        if($find){
            return $find;
        }
        $archiveBuilder = $this->baseBuilder->clone();
        $archiveBuilder->setModel($this->archiveModel);
        $archiveBuilder->getQuery()->connection = $this->archiveModel->getConnection();

        return $archiveBuilder->find($id, $columns);
    }

    protected function getOrderBy(): array
    {
        $query = $this->getQuery();

        $orderBy = $query->{$query->unions ? 'unionOrders' : 'orders'};

        if($orderBy === null){
            $orderBy[] = [
                'column' => 'id',
                'direction' => 'asc'
            ];
        }

        return array_reverse($orderBy);
    }

    private function applyOrderBy(Collection|array $results, \Illuminate\Database\Query\Builder $getQuery)
    {


        foreach ($this->getOrderBy() as $order){
            if(strtolower($order['direction']) === 'desc'){
                $results = $results->sortByDesc($order['column']);
            }else{
                $results = $results->sortBy($order['column']);
            }
        }
        return $results;
    }

    private function applyOffsetAndLimit(Collection|array $results, ?int $offset = null, ?int $limit = null)
    {
        return $results->splice($offset ?? 0, $limit);
    }

}
