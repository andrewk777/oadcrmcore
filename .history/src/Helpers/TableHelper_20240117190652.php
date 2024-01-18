<?php

namespace Oadsoft\Crmcore\Helpers;

use Closure;

class TableHelper
{

    private $query;
    private $model;
    private $sort_by;
    private $sort_dir;
    private $page;
    private $per_page = 25;
    private $searchTerm;    
    private $filters            = [];
    private $customFilters      = [];
    private $columns;
    private $results;
    private $resultsObtained    = false;
    private $customSearch       = [];
    private $selectCols         = [];
    private $extraSelect        = [];
    private $prep_pagination    = true;

    function __construct(Object $model)
    {
        $this->model = $model;
        $this->query = $model::query();
    }

    public function prepareQuery() {
        $this->querySelect()
                ->queryFilters()
                ->querySearch()
                ->queryOrder();
        if ($this->prep_pagination)
                $this->paginateQuery();
        return $this;
    }

    public function massAssignRequestData(array $data) {
        
        if (array_key_exists('sort',$data))
            list($this->sort_by,$this->sort_dir)    = explode("|",$data['sort']);
        $this->page                             = array_key_exists('page',$data) ? $data['page'] : 1;
        $this->per_page                         = array_key_exists('per_page',$data) ? $data['per_page'] : 10000000;
        $this->searchTerm                       = array_key_exists('search',$data) ? $data['search'] : '';
        $this->filters                          = array_key_exists('filters',$data) && is_array($data['filters']) ? $data['filters'] : [];
        $this->columns                          = !empty($data['config']['cols']) ? $data['config']['cols'] : [];
        
        return $this;
    }

    public function setSorting(string $sort_by,string $sort_dir) {
        $this->sort_by = $sort_by;
        $this->sort_dir = $sort_dir;
        return $this;
    }

    public function dontPaginate() {
        $this->prep_pagination = false;
        return $this;
    }

    public function doPaginate() {
        $this->prep_pagination = true;
        return $this;
    }

    public function setPage(int $page) {
        $this->page = $page;
        return $this;
    }

    public function setPerPage(int $per_page) {
        $this->per_page = $per_page;
        return $this;
    }

    public function setSearchTerm(string $term) {
        $this->searchTerm = $term;
        return $this;
    }
    
    public function ignoreSearchTerm() {
        $this->searchTerm = null;
        return $this;
    }

    public function setFilters(array $filters) {
        $this->filters = $filters;
        return $this;
    }

    public function setFilter(string $filter, $value) {
        $this->filters[$filter] = $value;
        return $this;
    }

    public function setCustomFilter(string $filter,$cb_function) {
        $this->customFilters[$filter] = $cb_function;
        return $this;
    }

    public function ignoreFilters() {
        $this->filters = [];
        return $this;
    }

    public function setCols(array $columns) {
        $this->columns = $columns;
        return $this;
    }

    public function setCustomSelect(array $select) {
        if (count($select))
            $this->selectCols = $select;
        return $this;
    }

    public function extraSelect(array $columns = []) {
        foreach ($columns as $column) {
            $this->extraSelect[] = $column;
        } 
        return $this;
    }

    public function setCustomFieldSearch(string $field,Closure $searchClosure) {
        $this->customSearch[$field] = $searchClosure;
        return $this;
    }

    public function setQueryExtension(Closure $extention) {
        $this->query = call_user_func($extention,$this->query);
        return $this;
    }

    public function querySearch() {
        if ($this->searchTerm) {
            $self = $this;
            $this->query->where(function($q) use ($self) {
                $main_table = $self->model::getTableName();
                foreach ($self->columns as $column) {

                    $searchBy = !empty($column['searchBy']) ? $column['searchBy'] : $column['db'] ?? $column['name'];
                    if (!(isset($column['searchable']) && $column['searchable'] === false) && 
                        !array_key_exists($column['name'],$self->customSearch)) 
                    {
                        if (strstr($searchBy,'.')) 
                        {
                            list($table,$field) = explode('.',$searchBy);
                        } 
                        else 
                        {
                            $table = $main_table;
                            $field = $searchBy;
                        }
                        $q->orWhere($table.'.'.$field,'LIKE','%' . $self->searchTerm . '%');

                    }
                }
                foreach ($self->customSearch as $searchClosure) {
                    $q = call_user_func($searchClosure,$q,$self->searchTerm);                    
                }
            });            
        }
        return $this;
    }

    public function queryOrder() {
        if ($this->sort_by) {
            $this->query->orderBy($this->sort_by, $this->sort_dir);
        }        
        return $this;
    }

    public function paginateQuery() {
        $skip = ($this->page - 1) * $this->per_page;
        $this->query->skip($skip)->take($this->per_page);
        return $this;
    }

    public function querySelect() {
        //make sure not to override custom select
        if (count($this->selectCols) == 0) {
            $table_name = $this->model::getTableName();
            foreach ($this->columns as $column) {
                if (!array_key_exists('noSelect',$column) || $column['noSelect'] == false) {
                    $this->selectCols[] = array_key_exists('db',$column) ? $column['db'] . ' as ' .$column['name'] : $table_name.'.'.$column['name'];
                }
            }
        }  
        $this->selectCols = array_merge($this->selectCols, $this->extraSelect);
        $this->query->addSelect($this->selectCols); 
        return $this;
    }

    public function cloneQuery() {
        return clone $this->query;
    }

    public function queryFilters() {
        
        foreach ($this->filters as $field => $value) {
            if (array_key_exists($field,$this->customFilters)) {
                $this->query = call_user_func($this->customFilters[$field],$this->query,$value);
            } else if (!empty($value) || $value === false) {
                $this->query->where($field,'LIKE','%' . $value . '%');
            }            
        }
        return $this;        
    }

    public function getResults() {
        if (!$this->resultsObtained)
            $this->runQuery();
        return $this->results;
    }

    public function getTotalCount() {
        return (clone $this->query)->skip(0)->take(10000000)->get()->count();
    }

    public function getPaginatedResults() {
        return $this->results->forPage($this->page,$this->per_page);
    }

    public function runQuery(array $selectAs = ['*']) { //very import to have * by default - thats eloquents default
        $this->results = $this->query->get($selectAs);
        $this->resultsObtained = true;
        return $this;
    }

    public function replaceFields(array $fieldsData = []) {
        
        $this->results->transform(function($item) use ($fieldsData) 
        {
            $return = $item->toArray();
            foreach ($fieldsData as $name => $value) 
            {
                if (is_callable($value)) 
                {
                    $return[$name] = call_user_func($value,$item);
                } 
                else 
                {
                    $return[$name] = $value;
                }                
            }
            return $return;
        });
        return $this;        
    }

    public function getVuetableResponse(bool $json = true) {

        $total  = $this->getTotalCount();
        $to     = $total < ($this->page * $this->per_page) ? $total : $this->page * $this->per_page;

        $response = [
            'pagination' => [
                'total'             => $total,
                'per_page'          => $this->per_page,
                'current_page'      => (int)$this->page,
                'last_page'         => ceil($total / $this->per_page),
                'next_page_url'     => '...',
                'prev_page_url'     => '...',
                'from'              => ($this->page - 1) * $this->per_page + 1,
                'to'                => $to,
            ],
            'data'              => $this->getResults()
        ];

        return $json ? response()->json( $response ) : $response;
    }

    public function ddQuery() {
         dd( vsprintf( str_replace(['?'], ['\'%s\''], $this->query->toSql()), $this->query->getBindings() ) ); 
    }

    public function ddResults() {
        dd($this->results);
    }
    
}
