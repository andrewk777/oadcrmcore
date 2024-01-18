<?php

namespace Oadsoft\Crmcore\Models;

class Field {

    protected $type = 'text'; //string
    protected $id = '';
    protected $key = '';
    protected $name = '';
	protected $db_name = '';
    protected $dValue = '';
    protected $cssClass = '';
    protected $cssFormControl = true;
    protected $disabled = '';
    protected $readonly = '';
    protected $required = false;
    protected $placeholder = '';
    protected $options = [];
    protected $rows = 3;
    protected $allow_clear = false;
    protected $fieldClass = '';
    protected $ajax = '';
    protected $dir = '';
    protected $multiple = false;
    protected $filters = [];
    protected $mask = '';
    protected $max_files = '';
    protected $deleted_unsaved_files = true;
    protected $allowed_ext = [];
    protected $can_delete = true;
    protected $can_edit = false;
    protected $can_upload = true;
    protected $confirm_on_delete = false;
    protected $max = '';
    protected $min = '';
    protected $description = '';
    protected $storeEmpty = true;
    protected $reset = false;
    //wrap
    protected $fieldset = true;
    protected $label = 'Label';
    protected $showPlaceholder = true;
    //actions
    protected $assignVal = true; //assign value from the post to the field on store
    protected $save_to_db = true; //save to db
    protected $db_retrieve = true; //restrieve from db
    public $group = '';

    public static function init() 
    {
        return new self();
    }

    public function __call($var, $args) {
        $this->$var = $args[0];
        return $this;
    }

    public function allow_clear() {
        $this->allow_clear = true;
        return $this;
    }

    public function noSave() {
        $this->save_to_db = false;
        return $this;
    }

    public function noRetreive() {
        $this->db_retrieve = false;
        return $this;
    }

    public function ignore($status = false) {
        if (!$status) {
            $this->noAssign()->noSave()->noRetreive();
        } else {
            $this->assignVal = true;
            $this->save_to_db = true;
            $this->db_retrieve = true;
        }
        return $this;
    }

    public function required($condition = '') {
        if($condition !== false) {
            $this->required = $condition ? $condition : 'required';
        }
        return $this;
    }

    public function addClass($val) {
        $this->cssClass .= ' ' . $val;
        return $this;
    }

    public function param($param) {
        return $this->$param;
    }

    public function disabled($condition = true) {
        if($condition !== false) {
            $this->disabled = 'disabled';
        }
        return $this;
    }

    public function readonly() {
        $this->readonly = 'readonly';
        return $this;
    }

    public function getKey() 
    {
        return $this->key;
    }

    public function toArray() {

		$this->db_name = $this->db_name ? $this->db_name : $this->name; //remove this with an update
        $this->key = $this->key ? $this->key : $this->db_name; //remove this with an update

        if (!$this->mask && $this->type == 'number') {
            $this->mask = '#';
        }

        if ($this->type == 'echeck' && !$this->cssClass) {
            $this->cssClass('success mt-25');
        }

        if (!$this->id) {
            $this->id = str_replace('[', '_', $this->name);
            $this->id = str_replace(']', '', $this->id);
        }

        return get_object_vars($this);

    }

}
