<?php

namespace Pathologic\Redirects\Controllers;

class Export
{
    protected $modx;
    public array $fields = [
        'id'            => 'ID',
        'source'        => 'Исходная ссылка',
        'target'        => 'Целевая ссылка',
        'response_code' => 'Код ответа',
        'description'   => 'Описание',
        'type'          => 'Тип исходной ссылки',
        'active'        => 'Активен',
        'triggered'     => 'Переходы',
        'triggeredon'   => 'Время последнего срабатывания',
        'createdon'     => 'Создан',
        'updatedon'     => 'Изменен',
    ];
    public array $config = [
        'controller'     => 'onetable',
        'table'          => 'redirects',
        'idType'         => 'documents',
        'display'        => 100,
        'ignoreEmpty'    => true,
        'returnDLObject' => true,
        'addWhereList'   => '',
        'orderBy'        => 'id ASC',
    ];

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    public function start()
    {
        $_SESSION['RecordsProcessed'] = 0;
        $_SESSION['RecordsTotal'] = 0;
        $_SESSION['LastRecordId'] = 0;
        $_SESSION['complete'] = false;
        $config = $this->config;
        unset($config['display']);
        $dl = $this->modx->runSnippet('DocLister', $config);

        $_SESSION['RecordsTotal'] = $dl->getChildrenCount();

        return ['status' => true];
    }

    public function process()
    {
        $processed = $_SESSION['RecordsProcessed'];
        $lastId = $_SESSION['LastRecordId'];
        $config = $this->config;
        if (!empty($config['addWhereList'])) {
            $config['addWhereList'] .= ' AND ';
        }
        $config['addWhereList'] .= 'id > ' . (int) $lastId;
        $docs = $this->modx->runSnippet('DocLister', $config)->getDocs();
        $data = [];
        if (!$processed) {
            $data[] = array_values($this->fields);
        }
        foreach ($docs as $id => $doc) {
            $rows = [];
            foreach ($this->fields as $field => $title) {
                if (isset($doc[$field])) {
                    $rows[] = $doc[$field];
                } else {
                    $rows[] = '';
                }
            }
            $data[] = $rows;
            $processed++;
            $_SESSION['LastRecordId'] = $id;
        }
        $_SESSION['RecordsProcessed'] = $processed;
        if ($_SESSION['RecordsProcessed'] >= $_SESSION['RecordsTotal']) {
            $_SESSION['complete'] = true;
        }
        $out = [
            'processed' => $_SESSION['RecordsProcessed'],
            'total'     => $_SESSION['RecordsTotal'],
            'complete'  => $_SESSION['complete'],
            'last'      => $_SESSION['LastRecordId']
        ];
        $out['data'] = $data;
        $out['status'] = true;

        return $out;
    }
}
