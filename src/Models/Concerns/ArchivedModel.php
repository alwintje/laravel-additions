<?php

namespace Kroesen\LaravelAdditions\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Kroesen\LaravelAdditions\Models\ArchiveBuilder;

trait ArchivedModel
{

    public function scopeWithArchive(Builder $builder): ArchiveBuilder
    {
        return ArchiveBuilder::createFromBuilder($builder);
    }

    public function scopeOnlyArchive(Builder $builder): ArchiveBuilder
    {
        $archiveModel = new (config('laravel_additions.models.'.$builder->getModel()::class.'.archive_model'));
        $builder->setModel($archiveModel);
        $builder->getQuery()->connection = $archiveModel->getConnection();
        return $builder;
    }

}
