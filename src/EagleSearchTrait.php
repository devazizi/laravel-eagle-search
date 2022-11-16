<?php

namespace LaravelEagleSearch;

use Illuminate\Database\Eloquent\Builder;
use LaravelEagleSearch\Exceptions\SearchablePropertyNotFoundException;

trait EagleSearchTrait
{
    /**
     * Scope a query for set filters
     *
     * @param Builder $query
     * @return void
     */
    public function scopeSetFilters(Builder $query)
    {
        $searchableFields = $this->getFieldsNameRequiredForSearch();
        foreach ($searchableFields as $searchableField) {
            if (preg_match('/\./', $searchableField['key'])) {
                $this->inRelationSearch($query, $searchableField);
            } else {
                $this->directSearch($query, $searchableField);
            }
        }
    }

    /**
     * @return array
     * @throws SearchablePropertyNotFoundException
     */
    private function getSearchSearchableFieldsList(): array
    {
        if (property_exists($this, 'searchable')) {
            return $this->searchable;
        } else {
            throw new SearchablePropertyNotFoundException('searchable property not found please add to entity');
        }
    }

    private function getFieldsNameRequiredForSearch(): array
    {
        $filtersNameInRequest = request()->get('filters');
        $entitySearchableFields = $this->getSearchSearchableFieldsList();
        $validatedFields = [];

        foreach ($filtersNameInRequest as $requestFieldKey => $requestField) {

            if (array_key_exists($requestFieldKey, $entitySearchableFields)) {
                $validatedFields[$requestFieldKey]['key'] = $entitySearchableFields[$requestFieldKey];
                $validatedFields[$requestFieldKey]['value'] = $requestField;
            }
        }

        return $validatedFields;
    }

    private function directSearch(Builder $query, $fieldRequestedFilter)
    {
        $query->where($fieldRequestedFilter['key'], $fieldRequestedFilter['value']);
    }

    private function inRelationSearch(Builder $query, $fieldRequestedFilter)
    {
        $pathToField = explode('.', $fieldRequestedFilter['key']);
        $relation = implode('.', array_slice($pathToField, 0, count($pathToField) - 1));
        $dbColumn = end($pathToField);
        $searchValue = $fieldRequestedFilter['value'];

        $query->whereHas($relation, function (Builder $q) use ($dbColumn, $searchValue) {
            $q->where($dbColumn, $searchValue);
        });
    }
}
