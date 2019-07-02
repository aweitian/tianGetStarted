<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 18:18
 */

namespace App\Data;


use App\Provider\Privilege;
use Aw\Build\Mysql\Crud;

class RankHistory extends Provider
{
    protected $table = 'rank_history';

    public function index($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return array();
        }
        if (!$this->check($data_src, 'rank_id')) {
            return array();
        }
        $that = $this;
        $rank_id = $data_src['rank_id'];
        $data = $this->search(
            http_query_get('_page', 1, $data_src),
            http_query_get('_size', 10, $data_src),
            function (Crud $crud, &$bind) use ($that, $rank_id) {
                $crud->bindWhere('rank_id=:rank_id');
                $bind['rank_id'] = $rank_id;
            }
        );
        return $data;
    }
}