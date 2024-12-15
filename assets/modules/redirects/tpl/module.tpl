<!DOCTYPE html>
<html>
<head>
    <title>Управление редиректами</title>
    <link rel="stylesheet" type="text/css" href="[+manager_url+]media/style/[+theme+]/style.css"/>
    <link rel="stylesheet" href="[+manager_url+]media/style/common/font-awesome/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/js/easy-ui/themes/modx/easyui.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/redirects/js/tomselect/tom-select.css"/>
    <script type="text/javascript" src="[+manager_url+]media/script/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-ru.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/fileapi/FileAPI/FileAPI.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/xlsx.full.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/tomselect/tom-select.complete.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/sender.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/form.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/redirect.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/util.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/uploader.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/module.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/import.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/redirects/js/export.js"></script>
    <script>
        const config = {
            connector: '[+connector+]',
            csrf: '[+csrf+]'
        };
    </script>
    <style>
        *,*:focus,*:hover{
            outline:none;
        }
        body {
            overflow-y: scroll;
        }

        #grid {
            width: 100%;
            min-height: 100px;
        }

        #actionsBar {
            padding: 3px 5px;
        }

        #actionsBar a {
            margin-top: 3px;
        }

        .form-wrapper {
            max-height: calc(80vh);
        }

        .form-wrapper form {
            padding: 5px 10px 5px 10px;
        }

        .form-row {
            margin-left:0;
        }

        .delete, .btn-red {
            color: red;
        }

        .btn-green {
            color: green;
        }

        .delete:hover {
            color: #990404;
        }

        .help-block {
            font-size: 0.8em;
            color: green;
        }

        .error {
            font-size: 0.8em;
            color: red;
        }

        .form-check-input {
            margin-top: 0.16rem;
        }

        .datagrid-row-selected {
            background: #d3f0ff;
            color:#000;
        }

        .l-btn-focus, .btn:focus {
            outline: 0;
            box-shadow: none;
        }

        .ts-control {
            padding:5px 8px;
            min-height:30px;
        }
        .ts-dropdown .dropdown-input {
            width: 100% !important;
            border-left: 0;
            border-right: 0;
        }
    </style>
</head>
<body>
<h1 class="pagetitle">
  <span class="pagetitle-icon">
    <i class="fa fa-exchange"></i>
  </span>
    <span class="pagetitle-text">
    Управление редиректами
  </span>
</h1>
<div id="actions">
    <ul class="btn-group">
        <li><a class="btn btn-secondary" href="#" onclick="document.location.href='index.php?a=2';">Закрыть модуль</a>
        </li>
    </ul>
</div>
<div style="padding:20px;box-shadow: 0 0 0.3rem 0 rgba(0,0,0,.1);background:#fff;">
    <table id="grid"></table>
</div>
<script type="text/template" id="redirectForm">
        <form>
            <input type="hidden" name="id" value="{%id%}">
            <input type="hidden" name="formid" value="redirect">
            <div class="form-group" data-field="source">
                <label for="source"><b>Исходная ссылка</b></label>
                <input name="source" id="source" class="form-control" value="{%source%}">
            </div>
            <div class="form-group" data-field="target">
                <label for="target"><b>Целевая ссылка</b></label>
                <input name="target" id="target" class="form-control d-none" value="{%target%}">
                <select id="documentSelect" style="display:none;">
                    <option value="{%target_id%}" data-path="{%target_path%}">{%target_name%}</option>
                </select>
            </div>
            <div class="form-group" data-field="response_code">
                <label for="response_code"><b>Код ответа</b></label>
                <select class="form-control" id="response_code" name="response_code">
                    <option value="301">301 Moved Permanently</option>
                    <option value="302">302 Found</option>
                    <option value="307">307 Temporary Redirect</option>
                    <option value="308">308 Permanent Redirect</option>
                </select>
            </div>
            <div class="form-row form-group">
                <div class="col-md-4" data-field="type">
                    <label><b>Тип исходной ссылки</b></label>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type0" value="0">
                            <label class="form-check-label" for="type0">Текст</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type1" value="1">
                            <label class="form-check-label" for="type1">Регулярное выражение</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-field="type">
                    <label><b>Тип целевой ссылки</b></label>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_type" id="target_type0" value="0">
                            <label class="form-check-label" for="target_type0">Документ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target_type" id="target_type1" value="1">
                            <label class="form-check-label" for="target_type1">Ссылка</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="keep_get"><b>Сохранять get-параметры</b></label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="keep_get" value="1" id="keep_get" {%keep_get%}>
                        <label class="form-check-label" for="keep_get">
                            Да
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group" data-field="description">
                <label for="description"><b>Описание</b></label>
                <textarea name="description" id="description" class="form-control"
                          rows="2">{%description%}</textarea>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="active" id="active" {%active%}>
                    <label class="form-check-label" for="active">
                        Включить
                    </label>
                </div>
            </div>
        </form>
</script>
<div style="display: none; visibility: hidden;" id="toolbar">
    <div id="actionsBar">
        <div class="row">
            <div class="col-md-9">
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-file',plain:true"
                   onclick="Module.create(); return false;">Новый редирект</a>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-upload',plain:true"
                   onclick="Export.init(); return false;">Экспорт</a>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-download',plain:true"
                   onclick="Module.import(); return false;">Импорт</a>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-trash delete',plain:true"
                   onclick="Module.delete(); return false;">Удалить</a>
            </div>
            <div class="input-group col-md-3">
                <input id="search" name="search" type="text" class="form-control" placeholder="Поиск...">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="searchBtn"><i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="file" name="upload" style="display:none;">
<script>
    $.parser.onComplete = function () {
        $('#toolbar').css('visibility', 'visible');
    }
    Module.init();
</script>
</body>
</html>
