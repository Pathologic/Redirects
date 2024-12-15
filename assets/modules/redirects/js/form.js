class GenericForm {
    wnd;
    wrapperId = '';
    group = '';
    templateId = '';
    title = 'Форма';
    newTitle = 'Новая форма';
    grid;
    data;
    buttons = [
        {
            text: 'Сохранить',
            iconCls: 'btn-green fa fa-check fa-lg',
            handler: () => {
                $('form', '#' + this.wrapperId).get(0).dispatchEvent(new Event('submit'));
            }
        }, {
            text: 'Закрыть',
            iconCls: 'btn-red fa fa-ban fa-lg',
            handler: () => {
                this.wnd.dialog('close', true);
            }
        }
    ];

    constructor(data = {}, grid = null) {
        this.grid = grid;
        this.data = data;
    }

    init(data) {
        let form = document.getElementById(this.templateId).innerHTML;
        form = parseTemplate(form, {

        })
        this.open(this.buildForm(form));
    }

    buildForm(form) {
        return $('<div><div class="form-wrapper" id="' + this.wrapperId + '">' + form + '</div></div>');
    }

    open(form) {
        const that = this;
        this.wnd = $(form).dialog({
            modal: true,
            title: this.data?.id ? this.title + ' ' + sanitize(this.data.id) : this.newTitle,
            collapsible: false,
            minimizable: false,
            maximizable: false,
            resizable: false,
            width: 580,
            buttons: this.buttons,
            onOpen: function() {
                that.onOpen($(this));
            },
            onClose: function() {
                destroyWindow($(this));
            },
        });
    }

    onOpen(wnd) {
        wnd.window('center');
        new FormSender({
            formWrapper: '#' + this.wrapperId,
            url: config.connector + '?mode=save',
            onSuccess: () => {
                this.grid.datagrid('reload');
                wnd.dialog('close', true);
            },
            onError: (error) => {
                console.error(error);
                $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
            },
        });
    }
}
