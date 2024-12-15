<?php

namespace Pathologic\Redirects\Controllers;

use Pathologic\Redirects\DocLister\FiltersTrait;
use Pathologic\Redirects\Model;
use Pathologic\Redirects\Tree;
use Pathologic\Redirects\UniqueRedirectException;

class Redirects
{
    use FiltersTrait;

    protected $modx;
    protected $model;

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->model = new Model($modx);
    }

    public function list()
    {
        $config = [
            'controller'     => 'onetable',
            'table'          => 'redirects',
            'idType'         => 'documents',
            'ignoreEmpty'    => 1,
            'display'        => 25,
            'offset'         => 0,
            'sortBy'         => 'id',
            'selectFields'   => 'c.*',
            'sortDir'        => 'desc',
            'returnDLObject' => true
        ];

        $this->addDynamicConfig($config);
        $this->addFilters($config);
        $dl = $this->modx->runSnippet('DocLister', $config);
        $total = $dl->getChildrenCount();
        $docs = $dl->getDocs();
        $ids = [];
        foreach ($docs as &$doc) {
            if (is_numeric($doc['target'])) {
                $doc['doc_url'] = $this->modx->makeUrl($doc['target']);
                $ids[] = $doc['target'];
            }
        }
        $paths = Tree::getPaths($ids, false);
        foreach ($docs as &$doc) {
            if (is_numeric($doc['target'])) {
                $doc['doc_path'] = $paths[$doc['target']] ?? '';
            }
        }

        return ['rows' => array_values($docs), 'total' => $total];
    }

    protected function addDynamicConfig(&$config)
    {
        if (isset($_POST['rows'])) {
            $config['display'] = (int) $_POST['rows'];
        }
        $offset = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $config['display'] * abs($offset - 1);
        $config['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $config['sortBy'] = '`' . preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']) . '`';
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), ["ASC", "DESC"])) {
            $config['sortDir'] = $_POST['order'];
        }
    }

    protected function getFormParams()
    {
        return [
            'formid'            => 'redirect',
            'api'               => 1,
            'noemail'           => 1,
            'csrf'              => 1,
            'protectSubmit'     => 0,
            'submitLimit'       => 0,
            'filters'           => [
                'source'        => ['strip_tags', 'trim'],
                'target'        => ['strip_tags', 'trim'],
                'label'         => ['strip_tags', 'trim', 'removeExtraSpaces'],
                'description'   => ['strip_tags', 'trim', 'removeExtraSpaces'],
                'response_code' => ['castInt'],
                'type'          => ['castInt'],
                'active'        => ['castInt'],
                'keep_get'      => ['castInt'],
            ],
            'emptyFormControls' => ['keep_get' => 0, 'active' => 0],
            'rules'             => [
                'source'        => [
                    'required' => 'Введите исходную ссылку',
                    'regexp'   => [
                        'function' => function ($FormLister, $value) {
                            if ($FormLister->getField('type')) {
                                set_error_handler(function () {
                                }, E_WARNING);
                                $isRegularExpression = preg_match('#' . $value . '#', "") !== false;
                                restore_error_handler();

                                return $isRegularExpression;
                            } else {
                                return true;
                            }
                        },
                        'message'  => 'Некорректное регулярное выражение'
                    ],
                    'format'   => [
                        'function' => function ($FormLister, $value) {
                            if (!$FormLister->getField('type')) {
                                $path = parse_url($value, PHP_URL_PATH);

                                return !empty($path);
                            } else {
                                return true;
                            }
                        },
                        'message'  => 'Некорректная ссылка'
                    ]
                ],
                'target'        => [
                    'required'        => 'Введите целевую ссылку',
                    'target_exists'   => [
                        'function' => function ($FormLister, $value) {
                            if (is_numeric($value)) {
                                $value = (int) $value;
                                $q = $this->modx->db->query("SELECT id FROM {$this->modx->getFullTableName('site_content')} WHERE `id` = {$value}");

                                return (bool) $this->modx->db->getValue($q);
                            } else {
                                $path = parse_url($value, PHP_URL_PATH);
                                $host = parse_url($value, PHP_URL_HOST);

                                return !empty($path) || !empty($host);
                            }
                        },
                        'message'  => 'Некорректная ссылка'
                    ],
                    'not_equal_paths' => [
                        'function' => function ($FormLister, $value) {
                            $source_path = parse_url($FormLister->getField('source'), PHP_URL_PATH);
                            if (is_numeric($value)) {
                                $value = $this->modx->makeUrl($value);
                            }
                            $target_path = parse_url($value, PHP_URL_PATH);

                            return $source_path !== $target_path;
                        },
                        'message' => 'Целевая ссылка совпадает с исходной'
                    ]
                ],
                'response_code' => [
                    'in' => [
                        'params'  => [[301, 302, 307, 308]],
                        'message' => 'Неверный код'
                    ]
                ],
            ],
            'prepareProcess'    => function ($modx, $data, $FormLister, $name) {
                $id = $data['id'] ?? 0;
                $model = new Model($modx);
                if ($id && $id == $model->edit($id)->getID()) {
                    $model->fromArray($data);
                } else {
                    $model->create($data);
                }
                try {
                    $result = $model->save(true, false);
                } catch (UniqueRedirectException $e) {
                    $FormLister->addError('source', 'unique', 'Редирект уже существует');

                    return;
                }
                if (!$result) {
                    $FormLister->setValid(false);
                    $FormLister->addMessage('Не удалось сохранить редирект');
                }
            }
        ];
    }

    public function save()
    {
        return $this->modx->runSnippet('FormLister', $this->getFormParams());
    }

    public function edit()
    {
        $out = ['status' => false];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($this->model->edit($id)->getID()) {
            $out['status'] = true;
            $out['fields'] = $this->model->toArray();
            if (!is_numeric($out['fields']['target'])) {
                $out['fields']['target_type'] = 1;
            } else {
                $out['fields']['target_type'] = 0;
                $id = (int) $out['fields']['target'];
                $q = $this->modx->db->query("SELECT `id`, `pagetitle` FROM {$this->modx->getFullTableName('site_content')} WHERE `id` = {$id} AND `deleted`=0");
                if ($row = $this->modx->db->getRow($q)) {
                    $paths = Tree::getPaths([$row['id']]);
                    $out['fields']['target_id'] = $row['id'];
                    $out['fields']['target_name'] = $row['pagetitle'];
                    $out['fields']['target_path'] = $paths[$row['id']] ?? '';
                } else {
                    $out['fields']['target_id'] = '';
                    $out['fields']['target_name'] = '';
                }
            }
        }

        return $out;
    }

    public function toggleActive()
    {
        $out = ['status' => false];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $active = isset($_POST['active']) ? (int) $_POST['active'] : 0;
        if ($this->model->edit($id)->getID() && $this->model->set('active', $active)->save()) {
            $out['status'] = true;
        }

        return $out;
    }

    public function delete()
    {
        $out = ['status' => false];

        $out['status'] = $this->model->delete($_POST['ids'] ?? []);

        return $out;
    }

    public function documents()
    {
        $query = $_POST['query'] ?? '';
        $except = $_POST['except'] ?? '';
        $sql = "SELECT `id`, `pagetitle` FROM {$this->modx->getFullTableName('site_content')}";
        $where = [];
        if (!empty($query)) {
            if (is_numeric($query)) {
                $id = (int) $query;
                $where[] = "`id` = {$id}";
            } else {
                $query = $this->modx->db->escape($query);
                $where[] = "`pagetitle` LIKE '%{$query}%'";
            }
        }
        if (is_scalar($except) && !empty($except)) {
            $except = \APIhelpers::cleanIDs($except);
            if ($except) {
                $except = implode(',', $except);
                $where[] = "`id` NOT IN ({$except})";
            }
        }
        $where[] = "`deleted` = 0";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " LIMIT 10";
        $q = $this->modx->db->query($sql);
        $items = $this->modx->db->makeArray($q);
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        $paths = Tree::getPaths($ids);
        foreach ($items as &$item) {
            $item['path'] = $paths[$item['id']] ?? '';
        }

        return ['items' => $items];
    }
}
