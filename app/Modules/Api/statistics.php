<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

use App\Modules\Api;


class statistics extends Api
{
    /**
     * @var \App\Data\Statistics
     */
    protected $statistics;

    public function __construct()
    {
        parent::__construct();
        $this->statistics = new \App\Data\Statistics();
    }

    /**
     * @return \Aw\Cmd
     */
    public function taskCnt()
    {
        $this->statistics->taskCnt();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function todayConsumeCnt()
    {
        $this->statistics->todayConsumeCnt();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function completionRate()
    {
        $this->statistics->completionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function bdCompletionRate()
    {
        $this->statistics->bdCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function soCompletionRate()
    {
        $this->statistics->soCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function sgCompletionRate()
    {
        $this->statistics->sgCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function mbdCompletionRate()
    {
        $this->statistics->mbdCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function m360CompletionRate()
    {
        $this->statistics->m360CompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function msgCompletionRate()
    {
        $this->statistics->msgCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function smCompletionRate()
    {
        $this->statistics->smCompletionRate();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function allCompletionSum()
    {
        $this->statistics->allCompletionSum();
        return $this->statistics->cmd;
    }

    /**
     * @return \Aw\Cmd
     */
    public function bdCompletionSum()
    {
        $this->statistics->bdCompletionSum();
        return $this->statistics->cmd;
    }
}