const Export = {
    processing: false,
    data: [],
    init: function () {
        let that = this;
        const form = $('form', '#searchBar');
        $.post(
            config.connector + '?mode=export/start',
            {
                _token: config.csrf
            },
            function (response) {
                if (response.status) {
                    that.processing = true;
                    that.data = [];
                    $('<div id="exportDlg"><div class="dialogContent" style="padding:15px;height:52px;">' +
                        '<div id="exportProgress" style="display:none;"></div>' +
                        '</div>').dialog({
                        title: 'Экспорт редиректов',
                        width: 400,
                        modal: true,
                        onOpen: function () {
                            $('#exportProgress').show().progressbar({value: 0});
                        },
                        onClose: function () {
                            $("#exportDlg").remove();
                            that.exportProcess = false;
                            that.data = [];
                        }
                    });
                    that.process();
                } else {
                    that.handleError();
                }
            }, 'json'
        ).fail(that.handleError);
    },
    process: function () {
        var that = this;
        if (!this.processing) return;
        $.post(
            config.connector + '?mode=export/process',
            {
                _token: config.csrf
            },
            function (response) {
                if (response.status) {
                    if (response.data.length > 0) {
                        that.data = that.data.concat(response.data);
                    }
                    if (!response.complete) {
                        $('#exportProgress').progressbar('setValue', Math.floor(response.processed / response.total * 100));
                        that.process();
                    } else {
                        $('#exportProgress').progressbar('setValue', 100);
                        let message = 'Экспортировано ' + response.processed + ' записей<br><br>';
                        let filename = "export.xlsx";
                        let ws_name = "Export";
                        let wb = XLSX.utils.book_new(), ws = XLSX.utils.aoa_to_sheet(that.data);
                        XLSX.utils.book_append_sheet(wb, ws, ws_name);
                        XLSX.writeFile(wb, filename);
                        $.messager.alert('Экспорт завершен', message, 'info', function () {
                            $('#exportDlg').dialog('close');
                        })
                    }
                } else {
                    that.handleError();
                }
            }, 'json'
        ).fail(that.handleError);
    },
    handleError: function () {
        $.messager.alert('Ошибка', 'Произошла ошибка', 'error', function () {
            $('#exportDlg').dialog('close');
        })
    }
}
