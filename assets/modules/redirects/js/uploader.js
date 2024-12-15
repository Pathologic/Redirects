const Uploader = {
    init: function () {
        const that = this;
        FileAPI.event.on($('input[name="upload"]')[0], 'change', function (evt) {
            var files = FileAPI.getFiles(evt); // Retrieve file list
            FileAPI.filterFiles(files, function (file, info/**Object*/) {
                return /xlsx$/.test(file.name.split('.').pop().toLowerCase());
            }, function (files/**Array*/, rejected/**Array*/) {
                that.read(files[0]);
            });
        });
    },
    read: function (file) {
        const that = this;
        FileAPI.readAsArrayBuffer(file, function (evt/**Object*/) {
            if (evt.type == 'load') {
                let data = evt.result;
                let workbook = XLSX.read(data, {type: 'array'});
                let result = [];
                workbook.SheetNames.forEach(function (sheetName) {
                    let roa = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {header: 1});
                    if (roa.length) {
                        roa.forEach(function (row, index) {
                            if (row.length && index) result.push(row);
                        });
                    }
                });
                if (result.length) {
                    Import.init(result);
                } else {
                    $.messager.alert('Ошибка', 'В файле нет данных', 'error');
                }
            } else if (evt.type == 'progress') {
                //TODO
            } else {
                $.messager.alert('Ошибка', 'Не удалось обработать файл', 'error');
                that.reset();
            }
        })
    },
    reset: function () {
        const upload = $('input[name="upload"]');
        upload.wrap('<form>').closest('form').get(0).reset();
        upload.unwrap();
    }
};
