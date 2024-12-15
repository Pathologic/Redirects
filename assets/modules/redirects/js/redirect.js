class RedirectForm extends GenericForm{
    wrapperId = 'editRedirectForm';
    templateId = 'redirectForm';
    group = 'redirects';
    title = 'Редирект';
    newTitle = 'Новый редирект';

    constructor(data = {}, grid = null) {
        super(data, grid);
        this.init(data);

        return this;
    }
    init(data) {
        const that = this;
        let form = document.getElementById(this.templateId).innerHTML;
        form = parseTemplate(form, {
            id: data.id || 0,
            source: data.source || '',
            target: data.target || '',
            description: data.description || '',
            response_code: data.response_code || 301,
            type: data.type || 0,
            target_type: data.target_type || 0,
            target_name: data.target_name || '',
            target_path: data.target_path || '',
            target_id: data.target_id || '',
            keep_get: (data.keep_get || '0') == '1' ? 'checked' : '',
            active: (data.active || '1') == '1' ? 'checked' : '',
        })
        this.open(this.buildForm(form));
    }


    buildForm(form) {
        form = super.buildForm(form);
        $('[name="target_type"]', form).change(function(){
            if(this.value == 0) {
                $('#target', form).addClass('d-none');
                $('.ts-wrapper', form).removeClass('d-none');
            } else {
                $('#target', form).removeClass('d-none');
                $('.ts-wrapper', form).addClass('d-none');
            }
        });
        new TomSelect($('#documentSelect', form).get(0),{
            plugins: ['dropdown_input'],
            valueField: 'id',
            labelField: 'pagetitle',
            searchField: 'pagetitle',
            shouldLoad: function(query){
                return query.length > 2;
            },
            preload: 'focus',
            maxItems: 1,
            hideSelected: true,
            load: function(query, callback) {
                const selected = this.getValue();
                request(config.connector + '?mode=documents', {
                        query: query,
                        _token: config.csrf,
                        except: selected
                    },
                    (response) => {
                        callback(response.items);
                    },
                    () => callback())
            },
            onChange: function(value) {
                $('#target', form).val(value);
            },
            render:{
                option: function(data, escape) {
                    return '<div><small>' + escape(data.path || '/') + '</small><br>' + escape(data.pagetitle) + '</div>';
                },
                item: function(data, escape) {
                    return '<div><small>' + escape(data.path || '/') +'</small><br>' + escape(data.pagetitle) + '</div>';
                },
                no_results:function(data,escape){
                    return '<div class="no-results">Нет результатов для "'+escape(data.input)+'"</div>';
                },
                not_loading:function(data,escape){
                    // no default content
                },
                loading:function(data,escape){
                    return '<div class="spinner"></div>';
                },
            }
        });
        this.setRadioButtonCheckedByValue(form, 'type', this.data.type || 0);
        this.setRadioButtonCheckedByValue(form, 'target_type', this.data.target_type || 0);
        $('[name="response_code"] [value="' + (this.data.response_code || 301) + '"]', form).attr('selected', 'selected');

        return form;
    }

    setRadioButtonCheckedByValue(form, name, value) {
        const radios = $('[name="' + name + '"]', form); // замените 'radioName' на имя вашего радио-кнопки
        for (let i = 0; i < radios.length; i++) {
            if (radios[i].value == value) {
                radios[i].checked = true;
                $(radios[i]).trigger('change');
                break;
            }
        }
    }
}
