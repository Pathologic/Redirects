<?php

namespace Pathologic\Redirects\DocLister;

trait FiltersTrait
{
    protected function addFilters(&$config)
    {
        $where = [];
        $search = !empty($_POST['search']) && is_scalar($_POST['search']) ? $this->modx->db->escape(trim($_POST['search'])) : '';
        if($search) {
            $where[] = "`source` LIKE '{$search}%'";
            $where[] = "`target` LIKE '{$search}%'";
            $where[] = "`description` LIKE '{$search}%'";
        }
        if($where) {
            $config['addWhereList'] = implode(' OR ', $where);
        }
    }
}
