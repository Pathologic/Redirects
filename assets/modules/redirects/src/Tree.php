<?php

namespace Pathologic\Redirects;

class Tree
{
    private static $tree = [];
    private static $ignore = [];

    protected static function getTreeData($ids)
    {
        $table = evo()->getFullTableName('site_content');
        $ids = array_unique($ids);
        $tree = [];
        if($ids) {
            self::$ignore = $ids;
            $_ids = $ids;
            while(!empty($_ids)) {
                $_ids = implode(',', $_ids);
                $q = evo()->db->query("SELECT `id`, `parent`, `pagetitle` FROM {$table} WHERE `id` IN ({$_ids}) AND `deleted` = 0");
                $_ids = [];
                while($row = evo()->db->getRow($q)) {
                    $_ids[] = $row['parent'];
                    $tree[] = $row;
                }
            }
        }
        self::$tree = $tree;
    }

    public static function getPaths($ids, $hideEndpoint = true)
    {
        self::getTreeData($ids);
        $tree = self::arrayToTree(self::$tree);

        return self::treeToPaths($tree, '', $hideEndpoint);
    }

    private static function arrayToTree($array, $parentId = 0)
    {
        $tree = [];
        foreach ($array as $item) {
            if ($item['parent'] == $parentId) {
                $children = self::arrayToTree($array, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }

    private static function treeToPaths($tree, $path = '', $hideEndpoint = true)
    {
        $array = [];
        foreach ($tree as $item) {
            $newPath = ($path ? $path . ' / ' : '') . trim($item['pagetitle']);
            $array[$item['id']] = in_array($item['id'], self::$ignore) && $hideEndpoint ? $path : $newPath;
            if (isset($item['children'])) {
                $array += self::treeToPaths($item['children'], $newPath, $hideEndpoint);
            }
        }

        return $array;
    }
}
