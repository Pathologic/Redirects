<?php

namespace Pathologic\Redirects;

class UrlTracker
{
    private static $instance;
    private $modx;
    private $urls = [];

    public static function getInstance()
    {

        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * is not allowed to call from outside: private!
     *
     */
    private function __construct()
    {
        $this->modx = evo();
    }

    /**
     * prevent the instance from being cloned
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized
     *
     * @return void
     */
    public function __wakeup()
    {
    }

    public function setDoc($id)
    {
        $id = (int) $id;
        $this->urls[] = [
            'id'     => $id,
            'source' => ltrim($this->modx->makeUrl($id), '/'),
        ];
        $q = $this->modx->db->query("SELECT `alias`, `isfolder` FROM {$this->modx->getFullTableName('site_content')} WHERE `id` = {$id}");
        if ($this->modx->db->getValue($q)) {
            $this->setChildren($id);
        }
    }

    public function track()
    {
        if (isset($this->urls[0])) {
            unset($this->modx->aliasListing[$this->urls[0]['id']]);
            $this->urls[0]['target'] = ltrim($this->modx->makeUrl($this->urls[0]['id']), '/');
            if ($this->urls[0]['target'] !== $this->urls[0]['source']) {
                foreach ($this->urls as &$url) {
                    if (!isset($url['target'])) {
                        unset($this->modx->aliasListing[$url['id']]);
                        $url['target'] = ltrim($this->modx->makeUrl($url['id']), '/');
                    }
                }
            } else {
                return [];
            }
        }

        return $this->urls;
    }

    protected function setChildren($id)
    {
        $id = (int) $id;
        $ids = [$id];
        while (!empty($ids)) {
            $_ids = implode(',', $ids);
            $q = $this->modx->db->query("SELECT `id`, `parent` FROM {$this->modx->getFullTableName('site_content')} WHERE `parent` IN ({$_ids}) AND `deleted` = 0");
            $ids = [];
            while ($row = evo()->db->getRow($q)) {
                $ids[] = $row['id'];
                $this->urls[] = [
                    'id'     => $row['id'],
                    'source' => ltrim($this->modx->makeUrl($row['id']), '/')
                ];
            }
        }
    }
}
