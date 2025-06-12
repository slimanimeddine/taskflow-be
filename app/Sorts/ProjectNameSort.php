<?php

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class ProjectNameSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        $query->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->orderBy('projects.name', $direction)
            ->select('tasks.*');
    }
}
