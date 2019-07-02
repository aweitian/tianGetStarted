<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Debug;

use App\Data\Provider\Error;
use App\Data\Provider\WzryCalc;
use App\Modules\debug;
use Aw\Http\Request;
use Aw\Http\Response;

class wzry extends debug
{
    /**
     * @param Request $request
     * @return Response|string
     */
    public function index(Request $request)
    {
        $calc = new WzryCalc();
        $calc->setData(array(
            'raw' => $this->testData(),
            'inc' => $this->incData(),
            'dec' => $this->decData()
        ));
        $calc->build();
        $this->view->with("post", array());
        $this->view->with("select", $calc->getFloatDwData());
        if ($request->getMethod() == "POST") {
            return $this->calc($calc);
        }

        return $this->show();
    }


    private function calc(WzryCalc $calc)
    {
        /**
         * @var Error $error
         */
        $error = $this->app->make("error");
        $data = $calc->calc($_POST);
        $this->view->with("post", $_POST);
        if (empty($data)) {
            $this->view->with("error", $error->getLastInfo());
            return $this->show();
        }

        $this->view->with("debug_arr", $data);
        return $this->show();

    }

    private function show()
    {
        $this->view->with('raw', $this->testData());
        return $this->view->render("wzry-calc");
    }

    public function getDemoData()
    {
        return json_encode(array(
            "raw" => $this->testData(),
            "dec" => $this->decData(),
            "inc" => $this->incData()
        ));
    }

    public function testData()
    {
        return array(
            //每一条数据是一个点,描述从上个点到这个点多少钱
            //text,dw:start,dw:end,dw:unit,star:start,star:end,star:unit
            array("qt", "青铜", 3, 0, 6, 1, 3, 2),
            array("by", "白银", 3, 0, 9, 1, 3, 3),
            array("hj", "黄金", 4, 0, 16, 1, 4, 4),
            array("bj", "铂金", 4, 0, 20, 1, 4, 5),
            array("zs", "钻石", array(
                array(5, 4, 30, 1, 5, 6),
                array(4, 3, 30, 1, 5, 6),
                array(3, 2, 30, 1, 5, 6),
                array(2, 1, 40, 1, 5, 8),
                array(1, 0, 40, 1, 5, 8),
            )),
            array("xy",
                "星耀",
                array(
                    array(5, 4, 60, 1, 5, 12),
                    array(4, 3, 70, 1, 5, 14),
                    array(3, 1, 80, 1, 5, 16),
                    array(1, 0, 100, 1, 5, 20)
                )
            ),
            array("wz",
                "王者",
                array(
                    1, 0, 900, array(
                    array(1, 10, 30),
                    array(10, 20, 40),
                    array(20, 30, 60)
                ))
            )
        );
    }

    public function decData()
    {
        return array(
            "ddw" => array("zs" => 20),/*满减*/
            "xdw" => array("xy" => array(/*小段位满减*/
                array(3, 10),
                array(4, 20),
                array(5, 30),
            ))
        );
    }

    public function incData()
    {
        return array(
            "zs" => array(150, 10),
            "xy" => array(150, 20),
            "wz" => array(150, 30),
        );
    }
}