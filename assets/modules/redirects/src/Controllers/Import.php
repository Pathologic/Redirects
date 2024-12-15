<?php

namespace Pathologic\Redirects\Controllers;

use Pathologic\Redirects\Model;

class Import
{
    protected $modx;
    protected $data;
    public array $fields = [
        'id',
        'source',
        'target',
        'response_code',
        'description',
        'type',
        'active'
    ];

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->data = new Model($modx);
    }

    public function process()
    {
        $flag = !empty($_POST['data']) && is_scalar($_POST['data']) && ($data = json_decode($_POST['data'], true));
        $updated = $created = $errors = 0;
        if ($flag) {
            foreach ($data as $row) {
                $item = [];
                foreach ($this->fields as $col => $field) {
                    $item[$field] = $row[$col];
                }
                $item['active'] = (int) (bool) $item['active'];
                $item['type'] = (int) (bool) $item['type'];
                $item['response_code'] = (int) $item['response_code'];
                if(!in_array($item['response_code'], [301, 302, 307, 308])) {
                    $item['response_code'] = 301;
                }
                try {
                    if (!empty($item['id'])) {
                        if ($this->updateItem($item['id'], $item)) {
                            $updated++;
                        } else {
                            $errors++;
                        }
                    } else {
                        if ($this->createItem($item)) {
                            $created++;
                        } else {
                            $errors++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                }
            }
        }
        if ($flag) {
            $out['created'] = $created;
            $out['updated'] = $updated;
            $out['errors'] = $errors;
            $out['success'] = true;
        } else {
            $out = ['success' => false, 'message' => 'Произошла ошибка'];
        }

        return $out;
    }

    protected function validate($item)
    {
        return
            !empty($item['source'])
            && !empty($item['target'])
            && !empty($item['response_code']);
    }

    protected function updateItem($id, $item)
    {
        return $this->validate($item) && $this->data->edit($id)->getID() && $this->data->fromArray($item)->save();
    }

    protected function createItem($item)
    {
        return $this->validate($item) && $this->data->create($item)->save();
    }
}
