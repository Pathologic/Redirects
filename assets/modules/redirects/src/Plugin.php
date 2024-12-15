<?php

namespace Pathologic\Redirects;

class Plugin
{
    protected $modx;
    protected $params = [];

    public function __construct(\DocumentParser $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = $params;
    }

    public function OnPageNotFound()
    {
        $query = $_GET['q'];
        $model = new Model($this->modx);
        $model->sendRedirect($query);
    }

    public function OnEmptyTrash()
    {
        if (empty($this->params['ids'])) {
            return;
        }
        $where = implode(',', $this->params['ids']);
        $this->modx->db->delete($this->modx->getFullTableName('redirects'), "`target` IN ($where)");
    }

    public function OnBeforeDocFormSave()
    {
        if ($this->params['mode'] === 'new' || $this->params['track'] === 'No') {
            return;
        }
        UrlTracker::getInstance()->setDoc($this->params['id']);
    }

    public function OnDocFormSave()
    {
        if ($this->params['mode'] === 'new' || $this->params['track'] === 'No') {
            return;
        }
        $this->track();
    }

    public function OnBeforeMoveDocument()
    {
        if ($this->params['track'] === 'No') {
            return;
        }
        UrlTracker::getInstance()->setDoc($this->params['id']);
    }

    public function OnAfterMoveDocument()
    {
        if ($this->params['track'] === 'No') {
            return;
        }
        $this->track();
    }

    protected function track()
    {
        $urls = UrlTracker::getInstance()->track();
        $model = new Model($this->modx);
        $table = $model->makeTable($model->tableName());
        foreach ($urls as $url) {
            $id = $url['id'];
            $source = $this->modx->db->escape($url['source']);
            $target = $this->modx->db->escape(ltrim($this->modx->makeUrl($id), '/'));
            $this->modx->db->query("DELETE FROM {$table} WHERE `source` = '{$target}' AND `target` = '{$id}'");
            try {
                $model->create([
                    'source'        => $source,
                    'target'        => $id,
                    'active'        => 1,
                    'keep_get'      => 1,
                    'response_code' => $this->params['response_code'] ?? 301,
                    'description'   => 'Изменился URL документа',
                ])->save();
            } catch (UniqueRedirectException $e) {

            }
        }
    }
}
