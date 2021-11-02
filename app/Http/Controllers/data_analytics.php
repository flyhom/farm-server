<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use \DB;

use Event;

class data_analytics extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function corr_sql($count_time = 'min', $column1 = '', $column2 = '', $start_time = '2021-04-01 00:00:00', $end_time = '2021-05-01 00:00:00')
    {
        $tmp_arr = array();
        $where_column_arr=[];
        $select_arr = [];
        $select_concat_arr = [];
        $table = $column1. ', '. $column2;
        $groupby = '';
        $tb_select_arr = [];

        array_push($select_arr,DB::raw('@a1:=AVG('. $column1. '.value)'));
        array_push($select_arr,DB::raw('@b1:=AVG('. $column2. '.value)'));
        array_push($select_arr,DB::raw('@c1:=(stddev_samp('.$column1.'.value) * stddev_samp('. $column2. '.value))'));

        array_push($where_column_arr, [$column1. '.time', $column2. '.time']);
        if ($count_time == 'min') {
            $query = DB::table(DB::raw($table))
            ->whereColumn($where_column_arr)
            ->whereBetween($column1. '.time' ,[$start_time, $end_time])
            ->select($select_arr);


            $sql = $query->toSql();
            $getBindings = $query->getBindings();

            // dd($sql,$getBindings);

            // $getBindings = [$start_time,$end_time];
            // $sql = str_replace('?', '%s', $query->toSql());
            // $sql = sprintf($sql, ...$getBindings);

            $query2 = DB::table(DB::raw($table .', ('. $sql. ') as tb'))
            ->select(DB::raw('round((sum( ( '. $column1. '.value - @a1 ) * ('. $column2. '.value - @b1) ) / ((count('. $column1. '.value) -1) * @c1)), 4) as p'))
            ->whereColumn($where_column_arr)
            ->whereBetween($column1. '.time' ,[$start_time, $end_time]);
            $sql2 = $query2->toSql();
            $getBindings2 = $query2->getBindings();
            $bindings = array_merge($getBindings, $getBindings2);
            // dd($sql2,$bindings);
            // dd($query2,$getBindings);
            $query3 = DB::select($sql2, $bindings);
            // dd($query3[0]->p);
            return $query3[0]->p;
        }elseif ($count_time == 'hour') {
            array_push($tb_select_arr, DB::raw('date_format(time, "%Y-%m-%d %H") as time'));
            array_push($tb_select_arr, DB::raw('AVG(value) as value'));
            $groupby = DB::raw('date_format(time, "%Y-%m-%d %H")');
            $tb1 = DB::table($column1)
            ->whereBetween('time' ,[$start_time, $end_time])
            ->select($tb_select_arr)
            ->groupBy($groupby)
            ->tosql();

            $tb2 = DB::table($column2)
            ->whereBetween('time' ,[$start_time, $end_time])
            ->select($tb_select_arr)
            ->groupBy($groupby)
            ->tosql();

            $query = DB::table(DB::raw('('. $tb1 .') as ' . $column1 .', (' . $tb2 .') as ' . $column2))
            ->whereColumn($where_column_arr)
            ->whereBetween($column1. '.time' ,[$start_time, $end_time])
            ->select($select_arr);


            $sql = $query->toSql();
            $getBindings = $query->getBindings();

            // dd($sql,$getBindings);

            // $getBindings = [$start_time,$end_time];
            // $sql = str_replace('?', '%s', $query->toSql());
            // $sql = sprintf($sql, ...$getBindings);

            $query2 = DB::table(DB::raw('('. $tb1 .') as ' . $column1 .', (' . $tb2 .') as ' . $column2 .', ('. $sql. ') as tb'))
            ->select(DB::raw('round((sum( ( '. $column1. '.value - @a1 ) * ('. $column2. '.value - @b1) ) / ((count('. $column1. '.value) -1) * @c1)), 4) as p'))
            ->whereColumn($where_column_arr);
            $sql2 = $query2->toSql();
            $bindings = array_merge($getBindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            // dd($sql2,$bindings);
            // dd($query2,$getBindings);
            $query3 = DB::select($sql2, $bindings);
            // dd($query3[0]->p);
            return $query3[0]->p;
        }elseif ($count_time == 'day') {
            array_push($tb_select_arr, DB::raw('date_format(time, "%Y-%m-%d") as time'));
            array_push($tb_select_arr, DB::raw('AVG(value) as value'));
            $groupby = DB::raw('date_format(time, "%Y-%m-%d")');
            $tb1 = DB::table($column1)
            ->whereBetween('time' ,[$start_time, $end_time])
            ->select($tb_select_arr)
            ->groupBy($groupby)
            ->tosql();

            $tb2 = DB::table($column2)
            ->whereBetween('time' ,[$start_time, $end_time])
            ->select($tb_select_arr)
            ->groupBy($groupby)
            ->tosql();

            $query = DB::table(DB::raw('('. $tb1 .') as ' . $column1 .', (' . $tb2 .') as ' . $column2))
            ->whereColumn($where_column_arr)
            ->whereBetween($column1. '.time' ,[$start_time, $end_time])
            ->select($select_arr);


            $sql = $query->toSql();
            $getBindings = $query->getBindings();

            // dd($sql,$getBindings);

            // $getBindings = [$start_time,$end_time];
            // $sql = str_replace('?', '%s', $query->toSql());
            // $sql = sprintf($sql, ...$getBindings);

            $query2 = DB::table(DB::raw('('. $tb1 .') as ' . $column1 .', (' . $tb2 .') as ' . $column2 .', ('. $sql. ') as tb'))
            ->select(DB::raw('round((sum( ( '. $column1. '.value - @a1 ) * ('. $column2. '.value - @b1) ) / ((count('. $column1. '.value) -1) * @c1)), 4) as p'))
            ->whereColumn($where_column_arr);
            $sql2 = $query2->toSql();
            $bindings = array_merge($getBindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            $bindings = array_merge($bindings, $getBindings);
            // dd($sql2,$bindings);
            // dd($query2,$getBindings);
            $query3 = DB::select($sql2, $bindings);
            // dd($query3[0]->p);
            return $query3[0]->p;
        }
    }

    public function correlation(Request $request)
    {
        date_default_timezone_set('Asia/Taipei');
        // $sensor_arr = ['atp', 'ec', 'humidity', 'luminance', 'ph', 'soil_humid', 'soil_temp', 'temp', 'uv'];
        $sensor_arr = ['luminance', 'temp', 'humidity', 'soil_temp', 'soil_humid', 'ec', 'ph', 'atp', 'uv'];
        $data = $request->all();

        if (!$data) {
            return response()->json(['status' => 400, 'msg' => "沒有傳送任何資料", 'request' => $request, 'request_data' => $data, 'request_data_content' => $request->getContent()]);
        }
        $start_time = $data["start_time"];
        $end_time = $data["end_time"];
        $count_time = $data["time"]; //min, hour, day
        if (!$start_time) $start_time = '2021-04-01 00:00:00';
        if (!$end_time) $end_time = '2021-05-01 00:00:00';
        // if ($count_time != 'min') {
        //     return response()->json(['status' => 400, 'msg' => "目前僅可查詢分的相似值"]);
        // }
        $corr = array();
        $tmp_arr = array();
        for ($i=0; $i < count($sensor_arr); $i++) {
            $tmp_arr = array();
            for ($j=0; $j < count($sensor_arr); $j++) {
                if ($j == 0) {
                    $tmp_arr = array_merge($tmp_arr, ["header" => $sensor_arr[$i]]);
                }
                if ($i > $j) {
                    $ans = (double)($corr[$j][$sensor_arr[$i]]);
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => $ans ]);
                }else if($sensor_arr[$i] == $sensor_arr[$j]){
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => '-']);
                }else{
                    $ans = $this->corr_sql($count_time, $sensor_arr[$i], $sensor_arr[$j], $start_time, $end_time);
                    $tmp_arr = array_merge($tmp_arr, [$sensor_arr[$j] => $ans]);
                }
            }
            array_push($corr, $tmp_arr);
        }

        // dd($corr);
        if (!$corr) {
            return response()->json(['status' => 200, 'msg' => "查無紀錄", 'datas' => $corr]);
        }
        return response()->json(['status' => 200, 'msg' => "成功", 'datas' => $corr]);
    }
}
