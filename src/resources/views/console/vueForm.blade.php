<template>
<div :class="c_panelClasses" class="onComponentLoad component-page">
    <form @submit.prevent="saveForm(false)">
        <div class="panel-title-wrap d-flex">
            <div class="flex-grow-1">
                @{{ addEditWord }} @{{ pageTitle }}
            </div>
            <div class="panel-btns">
                <btn type="submit" variant="outline-success" title="Save"><i class="fal fa-save"></i></btn>
                <btn variant="outline-success" @click="saveForm(true)" title="Save &amp; Close"><i class="fal fa-share-square"></i></btn>
                <btn variant="outline-success" @click="saveForm(false,'resetFormValues')" title="Save &amp; Add New"><i class="fal fa-layer-plus"></i></btn>
                <btn variant="outline-primary" @click="closePanel" title="Close"><i class="fal fa-times"></i></btn>
            </div>
        </div>
        
        <div class="form-content">
            <div v-show="loading" class="form-loader"><div><i class="fad fa-circle-notch fa-spin fa-4x"></i></div></div>
            <b-container v-if='formReady'>
                <b-row>
                    <b-col>
                        <f-set v-model="forms.main.values.name" :data="forms.main.fields.name"/>
                    </b-col>
                </b-row>
            </b-container>
        </div>
    </form>
</div>    
</template>

<script>

import form from '@/mixins/form'
import panel from '@/mixins/panel'

export default {
    mixins: [form,panel],
    data() {
        return {
            pageTitle: '{{ $title }}',
            formUri: '/{{ $resource }}',
            formId: '{{ $resource }}Form'
        }
    }
}
</script>