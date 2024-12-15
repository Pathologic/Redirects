const Import = {
    init: function (data) {
        this.processing = true;
        this.processed = 0;
        this.updated = 0;
        this.created = 0;
        this.errors = 0;
        this.currentChunk = 0;
        this.importData = [];
        let that = this;
        $('<div id="importDlg"><div class="dialogContent" style="padding:15px;height:52px;">' +
            '<div id="importProgress" style="display:none;"></div>' +
            '</div>').dialog({
            title: 'Импорт редиректов',
            width: 400,
            modal: true,
            onOpen: function () {
                $('#importProgress').show().progressbar({value: 0});
            },
            onClose: function () {
                $("#importDlg").remove();
                that.exportProcess = false;
                that.data = [];
            }
        });
        this.total = data.length;
        let chunk = 50;
        for (i = 0; i < this.total; i += chunk) {
            let chunkData = data.slice(i, i + chunk);
            this.importData.push({data:JSON.stringify(chunkData), size:chunkData.length});
        }
        this.process(this.importData[0]);
    },
    process: function (data) {
        var that = this;
        if (!this.processing) return;
        $.post(
            config.connector + '?mode=import/process',
            {
                data: data.data,
                begin: this.currentChunk === 0,
                _token: config.csrf
            },
            function (response) {
                if (response.success) {
                    that.processed += data.size;
                    that.created += response.created;
                    that.updated += response.updated;
                    that.errors += response.errors;
                    if (that.processed < that.total) {
                        $('#importProgress').progressbar('setValue', Math.floor(that.processed / that.total * 100));
                        that.process(that.importData[++that.currentChunk]);
                    } else {
                        $('#importProgress').hide();
                        $.messager.alert('Импорт завершен', 'Обработано строк: ' + that.processed + '<br>Обновлено записей: ' + that.updated + '<br>Cоздано записей: ' + that.created + '<br>Ошибок: ' + that.errors, 'info', function () {
                            $('#importDlg').dialog('close');
                            $('#grid').datagrid('reload');
                            Uploader.reset();
                        });
                    }
                } else {
                    that.handleError();
                }
            }, 'json'
        ).fail(that.handleError);
    },
    handleError: function () {
        $.messager.alert('Ошибка', 'Произошла ошибка', 'error', function () {
            $('#importDlg').dialog('close');
        })
        Uploader.reset();
    }
}
