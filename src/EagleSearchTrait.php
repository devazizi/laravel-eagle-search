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
     * @throws SearchablePropertyNotFoundException
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

    /**
     * @throws SearchablePropertyNotFoundException
     */
    private function getFieldsNameRequiredForSearch(): array
    {
        $filtersNameInRequest = request()->get('filters', []);
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
    }

    private function inRelationSearch(Builder $query, $fieldRequestedFilter)
    {
        $pathToField = explode('.', $fieldRequestedFilter['key']);
        $relation = implode('.', array_slice($pathToField, 0, count($pathToField) - 1));

        $query->whereHas($relation, function (Builder $query) use ($fieldRequestedFilter) {
            $this->registerFilterInQueryBuilder($query, $fieldRequestedFilter);
        });
    }

    private function sqlEqualWhere(Builder $query, string $operator, string $fieldName, string $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '=', $value);
        }
    }

    private function sqlNotEqualWhere(Builder $query, string $operator, string $fieldName, string $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '!=', $value);
        }
    }

    private function sqlEqualWhereIn(Builder $query, string $operator, string $fieldName, string $value)
    {
        $sqlValues = explode(',', $value);
        if ($operator == '&') {
            $query->whereIn($fieldName, $sqlValues);
        }
    }

    private function sqlEqualWhereNotIn(Builder $query, string $operator, string $fieldName, string $value)
    {
        $sqlValues = explode(',', $value);
        if ($operator == '&') {
            $query->whereNotIn($fieldName, $sqlValues);
        }
    }

    private function sqlEqualBetween(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->whereBetween($fieldName, explode(',', $value));
        }
    }

    private function sqlNotBetween(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->whereNotBetween($fieldName, explode(',', $value));
        }
    }

    private function sqlNull(Builder $query, string $operator, string $fieldName)
    {
        if ($operator == '&') {
            $query->whereNull($fieldName);
        }
    }

    private function sqlNotNull(Builder $query, string $operator, string $fieldName)
    {
        if ($operator == '&') {
            $query->whereNotNull($fieldName);
        }
    }

    # gt
    private function sqlGreaterThen(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '>', $value);
        }
    }

    # gte
    private function sqlGreaterThenEqual(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '>=', $value);
        }
    }

    # lt
    private function sqlLessThen(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '<', $value);
        }
    }

    # lte
    private function sqlLessThenEqual(Builder $query, string $operator, string $fieldName, $value)
    {
        if ($operator == '&') {
            $query->where($fieldName, '<=', $value);
        }
    }

    private function registerFilterInQueryBuilder(Builder $query, $fieldRequestedFilter)
    {
        $whereOperator = $fieldRequestedFilter['searchOperator'];
        $fieldName = $fieldRequestedFilter['key'];
        $value = $fieldRequestedFilter['value'];

        switch ($fieldRequestedFilter['searchType']) {
            case 'eq':
                $this->sqlEqualWhere($query, $whereOperator, $fieldName, $value);
                break;
            case '!eq':
                $this->sqlNotEqualWhere($query, $whereOperator, $fieldName, $value);
                break;
            case 'in':
                $this->sqlEqualWhereIn($query, $whereOperator, $fieldName, $value);
                break;
            case '!in':
                $this->sqlEqualWhereNotIn($query, $whereOperator, $fieldName, $value);
                break;
            case 'bwn':
                $this->sqlEqualBetween($query, $whereOperator, $fieldName, $value);
                break;
            case '!bwn':
                $this->sqlNotBetween($query, $whereOperator, $fieldName, $value);
                break;
            case 'nil':
                $this->sqlNull($query, $whereOperator, $fieldName);
                break;
            case '!nil':
                $this->sqlNotNull($query, $whereOperator, $fieldName);
                break;
            case 'lte':
                $this->sqlLessThenEqual($query, $whereOperator, $fieldName, $value);
                break;
            case 'lt':
                $this->sqlLessThen($query, $whereOperator, $fieldName, $value);
                break;
            case 'gt':
                $this->sqlGreaterThen($query, $whereOperator, $fieldName, $value);
                break;
            case 'gte':
                $this->sqlGreaterThenEqual($query, $whereOperator, $fieldName, $value);
                break;
            default:
                break;
        }
    }
}
