<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

//use App\Data\Provider\RegisterCheck;
use App\Modules\Api;

class rankHistory extends Api
{
    /**
     * @var \App\Data\RankHistory
     */
    protected $rank;

    public function __construct()
    {
        parent::__construct();
        $this->rank = new \App\Data\RankHistory();
    }

    public function index()
    {
        $data = $this->rank->index($_GET);
        if (!empty($data)) {
            return $this->cmd->setData($data);
        }
        return $this->rank->cmd;
    }
}