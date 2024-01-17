<template>
<div class="onComponentLoad">
    
    <div class="panel-title-wrap">@{{ pageTitle }}</div>

    <b-container>
        <b-row class="mb-3 d-flex justify-content-between">
            <div>
                <table-search :tableRef="mainTableRef"></table-search>
            </div>

            <div>
                <btn variant="outline-primary" @click="tableAeItem(mainTableRef)"><i class="fal fa-plus"></i><span class="d-none d-sm-inline"></span></btn>
                <btn variant="outline-primary" @click="tableRefresh(mainTableRef)"><i class="fal fa-redo"></i><span class="d-none d-sm-inline"></span></btn>
            </div>
        </b-row>
    </b-container>

    <my-vuetable :ref="mainTableRef" @action-btn-clicked="tableAeItem" :tableConf="mainTable" />

    <router-view/>

</div>
</template>

<script>
import table from '@/mixins/table';
import formatting from '@/mixins/formatting';
import { mapGetters } from 'vuex';

export default {
    mixins: [ table, formatting ],
    data() {
        return {
            pageTitle: '{{ $title }}',
            mainTableRef: '{{ $ref }}Index',
            vTablesConfigs: {
                {{ $ref }}Index: this.makeTable({
                    url: '/{{ $resource }}',
                    ref: '{{ $ref }}Index',
                    formComponentName: '{{ $resource }}Form',
                    cols: [
                        { name: 'hash', visible: false, searchable: false },
                        { name: '__component:table-action-btns', title: '', titleClass: 'dt-action-col', dataClass: 'p-0', noSelect: true, searchable: false }
                    ]
                })
            }
        }
    }
}
</script>

<style>
</style>
