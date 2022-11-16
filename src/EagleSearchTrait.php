<?php

namespace LaravelEagleSearch;

use Illuminate\Database\Eloquent\Builder;
use LaravelEagleSearch\Exceptions\SearchablePropertyNotFoundException;

trait EagleSearchTrait
{
    private bool $firstOrOperatorActivated = false;
    private bool $firstOrOperatorForRelationActivated = false;

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

            $this->addSearchConfigs($searchableField);

            if (preg_match('/\./', $searchableField['key'])) {
                $this->inRelationSearch($query, $searchableField);
            } else {
                $this->directSearch($query, $searchableField);
            }
        }
    }

    /**
     * @param $searchableField
     * @return void
     */
    private function addSearchConfigs(&$searchableField): void
    {
        $searchValueAndConfig = explode('<:>', $searchableField['value']);
        $searchableField['value'] = $searchValueAndConfig[1];
        $searchOperatorAndType = explode('|', $searchValueAndConfig[0]);
        $searchableField['searchOperator'] = $searchOperatorAndType[0];
        $searchableField['searchType'] = $searchOperatorAndType[1];
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
        $this->registerFilterInQueryBuilder($query, $fieldRequestedFilter);
//        $query->where($fieldRequestedFilter['key'], $fieldRequestedFilter['value']);
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

    private function sqlEqualWhere(Builder $query, string $operator, string $fieldName, string $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '=', $value);
        }
    }

    private function sqlEqualWhereIn(Builder $query, string $operator, string $fieldName, string $value)
    {
        $sqlValues = explode(',', $value);
        if ($operator == '&') {
            $query->whereIn($fieldName, $sqlValues);
        }
    }

    private function registerFilterInQueryBuilder(Builder $query, $fieldRequestedFilter)
    {
        $whereOperator = $fieldRequestedFilter['searchOperator'];
        $fieldName = $fieldRequestedFilter['key'];
        $value = $fieldRequestedFilter['value'];

        switch ($fieldRequestedFilter['searchType']) {
            case 'equal':
                $this->sqlEqualWhere($query, $whereOperator, $fieldName, $value);
                break;
            case 'in':
                $this->sqlEqualWhereIn($query, $whereOperator, $fieldName, $value);
            default:
                break;
        }
    }
}
