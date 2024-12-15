const Module = {
    init: function () {
        const module = this;
        $('#grid').datagrid({
            url: config.connector,
            title: "Управление редиректами",
            scrollbarSize: 0,
            fitColumns: true,
            pagination: true,
            idField: 'id',
            singleSelect: true,
            striped: true,
            checkOnSelect: false,
            selectOnCheck: false,
            emptyMsg: 'Редиректы не созданы',
            pageList: [25, 50, 75, 100],
            pageSize: 25,
            columns: [[
                {field: 'select', checkbox: true},
                {
                    field: 'source', title: 'Исходная ссылка', width: 140, sortable: true,
                    formatter: function (value, row) {
                        let out = '[' + row.id + '] ' + sanitize(value) + '<br><small>' + sanitize(row.description) + '</small>';
                        let badges = '';
                        if(row.type == '1') badges += '<span class="badge badge-success">regexp</span>';

                        return badges ? out + '<br>' + badges : out;
                    }
                },
                {
                    field: 'target', title: 'Целевая ссылка', width: 140, sortable: true,
                    formatter: function (value, row) {
                        let out = '';
                        if(row.doc_path || false) {
                            out += '<small>' + sanitize(row.doc_path) + '</small><br>';
                        }
                        out += sanitize(row.doc_url || value);
                        let badges = '';
                        if(row.keep_get == '1') badges += '<span class="badge badge-primary">get</span>';
                        if(!isNaN(parseInt(row.target))) badges += '<span class="badge badge-info">doc</span>';

                        return badges ? out + '<br>' + badges : out;
                    },
                },
                {
                    field: 'response_code', title: 'Код<br>ответа', width: 50, fixed: true, align: 'center', sortable: true,
                },
                {
                    field: 'triggered', title: 'Переходы', width: 70, fixed: true, align: 'center', sortable: true,
                },
                {
                    field: 'triggeredon',
                    title: 'Время<br>последнего<br>перехода',
                    width: 90,
                    fixed: true,
                    align: 'center',
                    sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'createdon', title: 'Создан', width: 90, fixed: true, align: 'center', sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'updatedon', title: 'Изменен', width: 90, fixed: true, align: 'center', sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'active',
                    width: 30,
                    fixed: true,
                    align: 'center',
                    title: '<span class="fa fa-lg fa-power-off"></span>',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return '<input type="checkbox" value="1"' + (value === '0' ? '' : ' checked') + ' onchange="Module.toggleActive(' + index + ')">';
                    }
                },
                {
                    field: 'action',
                    width: 40,
                    title: '',
                    align: 'center',
                    fixed: true,
                    formatter: function (value, row) {
                        return '<a class="action delete" href="javascript:void(0)" onclick="Module.delete(' + row.id + ')" title="Удалить"><i class="fa fa-trash fa-lg"></i></a>';
                    }
                }
            ]],
            toolbar: '#toolbar',
            onDblClickRow: function (index, row) {
                module.edit(row.id);
            },
        });
        $('#searchBtn').click(() => $('#grid').datagrid('load', {search: $('#search').val()}));
        Uploader.init();
    },
    toggleActive: function (index) {
        const grid = $('#grid');
        let row = grid.datagrid('getSelected');
        if (typeof row !== 'undefined') {
            const active = row.active === '1' ? '0' : '1';
            request(config.connector + '?mode=toggleActive', {id: row.id, active: active}, (response) => {
                if (response.status) {
                    row.active = active;
                    grid.datagrid('updateRow', {
                        index: index,
                        row: row
                    });
                    grid.datagrid('refreshRow', index);
                }
            }, (error) => {
                $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
            });
        }
    },
    delete: function (id) {
        let ids = [];
        const grid = $('#grid');
        if (typeof id === 'undefined') {
            const rows = grid.datagrid('getChecked');
            const options = grid.datagrid('options');
            const pkField = options.idField;
            if (rows.length) {
                $.each(rows, function (i, row) {
                    ids.push(row[pkField]);
                });
            }
        } else {
            ids.push(id);
        }
        if (ids.length) {
            $.messager.confirm('Удаление', 'Вы уверены?', function (r) {
                if (r) {
                    request(config.connector + '?mode=delete', {ids: ids}, (response) => {
                        if (response.status) {
                            grid.datagrid('reload');
                            grid.datagrid('clearChecked');
                        } else {
                            $.messager.alert('Ошибка', 'Не удалось удалить', 'error')
                        }
                    }, (error) => {
                        $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                    });
                }
            });
        }
    },
    create: function () {
        new RedirectForm({
        }, $('#grid'))
    },
    edit: function (id) {
        request(config.connector + '?mode=edit', {id: id}, (response) => {
            new RedirectForm(response.fields, $('#grid'));
        }, (error) => {
            $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
        });
    },
    import: function() {
        $('input[name="upload"]').click();
    }
}


