<?php

namespace Pathologic\Redirects;

class Model extends \autoTable
{
    protected $table = 'redirects';
    public $default_field = [
        'source'      => '',
        'target'      => '',
        'description' => '',
        'response_code' => '',
        'type'        => 0,
        'keep_get'    => 0,
        'active'      => 0,
        'triggered'   => 0,
        'triggeredon' => null,
        'createdon'   => null,
        'updatedon'   => null,
    ];

    /**
     * @param $pattern
     * @return string
     */
    public function sendRedirect($query = '', $regexp = false)
    {
        if(!$regexp) {
            $q = $this->query("SELECT * FROM {$this->makeTable($this->table)} WHERE `source` = '{$this->escape($query)}' AND `type` = 0 AND `active` = 1");
            if($row = $this->modx->db->getRow($q)) {
                $time = date('Y-m-d H:i:s', $this->getTime(time()));
                $this->modx->db->query("UPDATE {$this->makeTable($this->table)} SET `triggered` = `triggered` + 1, `triggeredon` = '{$time}' WHERE `id` = {$row['id']}");
                $get = $row['keep_get'] ? $_GET : [];
                unset($get['q']);
                $get = http_build_query($get);
                if(is_numeric($row['target'])) {
                    $this->modx->sendRedirect($this->modx->makeUrl($row['target'], '', $get, 'full'), 0, 'REDIRECT_HEADER', $this->getResponseCode($row['response_code']));
                } else {
                    if($get) {
                        $row['target'] .= '?' . $get;
                    }
                    $this->modx->sendRedirect($row['target'], 0, 'REDIRECT_HEADER', $this->getResponseCode($row['response_code']));
                }
            } else {
                $this->sendRedirect($query, true);
            };
        } else {
            $q = $this->query("SELECT * FROM {$this->makeTable($this->table)} WHERE '{$this->escape($query)}' REGEXP(`source`) AND `type` = 1 AND `active` = 1");
            if($row = $this->modx->db->getRow($q)) {
                $time = date('Y-m-d H:i:s', $this->getTime(time()));
                $this->modx->db->query("UPDATE {$this->makeTable($this->table)} SET `triggered` = `triggered` + 1, `triggeredon` = '{$time}' WHERE `id` = {$row['id']}");
                $get = $row['keep_get'] ? $_GET : [];
                unset($get['q']);
                $get = http_build_query($get);
                if(is_numeric($row['target'])) {
                    $this->modx->sendRedirect($this->modx->makeUrl($row['target'], '', $get, 'full'), 0, 'REDIRECT_HEADER', $this->getResponseCode($row['response_code']));
                } else {
                    $row['target'] = preg_replace('/' . $row['source'] . '/', $row['target'], $query);
                    if($get) {
                        $row['target'] .= '?' . $get;
                    }
                    $this->modx->sendRedirect($row['target'], 0, 'REDIRECT_HEADER', $this->getResponseCode($row['response_code']));
                }
            }

        }
    }

    public function getResponseCode($code = '') {
        $codes = [
            301 => '301 Moved Permanently',
            302 => '302 Found',
            307 => '307 Temporary Redirect',
            308 => '308 Permanent Redirect',
        ];
        if(isset($codes[$code])) {
            return $codes[$code];
        } else {
            return $codes[301];
        }
    }

    /**
     * @param $fire_events
     * @param $clearCache
     * @return bool|void|null
     */
    public function save($fire_events = false, $clearCache = false)
    {
        $this->prepareSource();
        if (!$this->checkUnique($this->table, 'source')) {
            throw new UniqueRedirectException('Такой редирект уже существует!');
        }
        if ($this->newDoc) {
            if (!$this->get('createdon')) {
                $this->set('createdon', date('Y-m-d H:i:s', $this->getTime(time())));
            }
        } else {
            if (!$this->get('updatedon')) {
                $this->set('updatedon', date('Y-m-d H:i:s', $this->getTime(time())));
            }
        }
        $out = parent::save($fire_events, $clearCache);

        return $out;
    }

    protected function prepareSource()
    {
        $source = $this->get('source');
        $type = (int)$this->get('type');
        if(!$type) {
            $url = parse_url($source, PHP_URL_PATH);
            $this->set('source', ltrim($url, '/'));
        }
    }

    public function createTable()
    {
        $this->query("CREATE TABLE IF NOT EXISTS {$this->makeTable($this->table)} (
            `id` INT(11) AUTO_INCREMENT,
            `source` VARCHAR(255) DEFAULT NULL,
            `target` VARCHAR(255) DEFAULT NULL,
            `description` TEXT,
            `type` TINYINT(1) NOT NULL DEFAULT 0,
            `response_code` SMALLINT NOT NULL DEFAULT 301,
            `keep_get` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `triggered` INT(11) NOT NULL DEFAULT 0,
            `triggeredon` DATETIME,
            `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP ,
            `updatedon` DATETIME,
            PRIMARY KEY (`id`),
            UNIQUE KEY `source` (`source`),
            KEY `active` (`active`, `type`)
            ) ENGINE=InnoDB
        ");
    }
}
