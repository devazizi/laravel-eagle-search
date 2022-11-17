<?php

namespace LaravelEagleSearch;

use Illuminate\Database\Eloquent\Builder;
use LaravelEagleSearch\Exceptions\OrderablePropertyNotFoundException;

trait EagleOrderTrait
{
    public function scopeSetOrders(Builder $query)
    {
        $fields = $this->checkOrderableColumns();

        foreach ($fields as $fieldKey => $fieldValue) {
            if ($fieldValue === 'desc') {
                $this->orderByDesc($query, $fieldKey);
            } elseif ($fieldValue === 'asc') {
                $this->orderByAsc($query, $fieldKey);
            }
        }
    }

    private function checkOrderableColumns(): array
    {
        $requestOrderableFields = request()->get('orders', []);
        if (!property_exists($this, 'orderable')) {
            throw new OrderablePropertyNotFoundException('orderable property not found in your model');
        }

        $orderableFields = $this->orderable;
        $finalOrderableFields = [];

        foreach ($requestOrderableFields as $fieldName => $fieldValue) {
            if (in_array($fieldName, $orderableFields)) {
                $finalOrderableFields[$fieldName] = $fieldValue;
            }
        }

        return $finalOrderableFields;
    }

    private function orderByDesc(Builder $query, string $fieldName)
    {
        $query->orderByDesc($fieldName);
    }

    private function orderByAsc(Builder $query, string $fieldName)
    {
        $query->orderBy($fieldName);
    }
}
