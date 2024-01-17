<?php
namespace App\_oad_repo\Helpers;

use App\Models\OAD\Field;

trait ModelHelper {

    public function prebuiltFields($form_name = '', $custom_form_name = '') {

        $custom_form_name = $custom_form_name ? $custom_form_name : $form_name;

        switch($form_name) {
            case "address":
                $this->form_fields[$custom_form_name] = $this->buildFormFields([
                    Field::init()->name('unit')->label('Unit')->toArray(),
                    Field::init()->name('address')->label('Address')->toArray(),
                    Field::init()->name('city')->label('City')->toArray(),
                    Field::init()->name('province')->label('Province')->toArray(),
                    Field::init()->name('postal')->label('Postal')->toArray(),
                ]);
            break;
            case "phone":
                $this->form_fields[$custom_form_name] = $this->buildFormFields([
                    Field::init()->name('type')->type('select')->options(config('project.phone_types'))->label('Phone Number')->toArray(),
                    Field::init()->name('number')->mask('tel')->label(false)->toArray(),
                    Field::init()->name('ext')->label(false)->toArray(),
                ]);
            break;
            case "email":
                $this->form_fields[$custom_form_name] = $this->buildFormFields([
                    Field::init()->name('type')->type('select')->options(config('project.email_types'))->label('Email')->toArray(),
                    Field::init()->name('email')->mask('tel')->label(false)->toArray(),
                ]);
            break;
        }
        
        return $this;
        
    }
}