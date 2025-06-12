<?php

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class AssigneeNameSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        $query->join('users', 'tasks.assignee_id', '=', 'users.id')
            ->orderBy('users.name', $direction)
            ->select('tasks.*');
    }
}
