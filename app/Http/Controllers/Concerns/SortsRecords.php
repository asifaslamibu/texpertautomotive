<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait SortsRecords
{
    /**
     * Apply a whitelisted `sort` + `dir` from the request to a query builder.
     * Falls back to a default column/direction when the requested sort isn't allowed.
     */
    protected function applySort($query, Request $request, array $allowed, string $default = 'id', string $defaultDir = 'desc')
    {
        $sort = (string) $request->get('sort');
        $dir = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        if ($sort !== '' && in_array($sort, $allowed, true)) {
            return $query->orderBy($sort, $dir);
        }

        return $query->orderBy($default, $defaultDir);
    }
}
