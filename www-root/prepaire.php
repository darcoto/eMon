<?php

  ini_set("display_errors",1);
  
  $o = new Emon();
  $o->run();


  class EmonPoint{
    public $po;
    public $t1;
    function __construct(){
      $this->po = new EmonParam();
      $this->t1 = new EmonParam();
      $this->h1 = new EmonParam();
      $this->lt = new EmonParam();
    }
    
    function addValue($po,$t1,$h1,$lt){
      $this->po->value += $po/1000;
      $this->t1->values[] = $t1;
      $this->h1->values[] = $h1;
      $this->lt->values[] = $lt;
    }
  }
  
  class EmonParam{
    public $value = 0;
    public $values = Array(); 
    
    function setAvgValue(){
      $this->values = array_filter($this->values);
      if(count($this->values)){
        $this->value = array_sum($this->values) / count($this->values);
      }
    }
  }
  
  class Emon{

    //----------------------------------------------
    function __construct(){
      $this->data = new stdClass();
      $this->data->all = Array();
      $this->data->days = Array();
      $this->data->hours = Array();
    }
    
    //----------------------------------------------
    function run(){
      $this->init_data();
      $this->read_log();
      $this->print_result();
    }
    
    //----------------------------------------------
    function init_data(){
      $begin = strtotime('2014-01-11 22:00');
      for($i=$begin;$i<time();$i=$i+60){
        $this->data->all[$i] = new EmonPoint();
      }
    }

    //----------------------------------------------    
    function read_log(){

      $handle = @fopen("../power.log", "r");
      if ($handle){
        while (($buffer = fgets($handle, 4096)) !== false) {
          $buffer = explode(" ",$buffer);
          if(sizeof($buffer) < 2) continue;
          
          $time_1 = strtotime(date('Y-m-d H:i:0',$buffer[0]));  //group by minute
          $time_2 = strtotime(date('Y-m-d',$buffer[0]));        //group by day
          $time_3 = date('G',$buffer[0]);                       //group by hour

          //if(!isset($this->data->all[$time_1]))  $this->data->all[$time_1] = new stdClass();
          if(!isset($this->data->days[$time_2]))  $this->data->days[$time_2] = new EmonPoint();
          if(!isset($this->data->hours[$time_3])) $this->data->hours[$time_3] = new EmonPoint();

          $po = isset($buffer[1])?$buffer[1]:null;
          $t1 = isset($buffer[2])?$buffer[2]:null;
          $h1 = isset($buffer[3])?$buffer[3]:null;
          $lt = isset($buffer[6])?$buffer[6]:null;

          $this->data->all[$time_1]->addValue($po,$t1,$h1,$lt);
          $this->data->days[$time_2]->addValue($po,$t1,$h1,$lt);
          $this->data->hours[$time_3]->addValue($po,$t1,$h1,$lt);

            
        }
        if (!feof($handle)){
          echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
      }

      foreach($this->data->all as &$p){
        $p->t1->setAvgValue();
        $p->h1->setAvgValue();
        $p->lt->setAvgValue();
      }

      foreach($this->data->days as &$p){
        $p->t1->setAvgValue();
        $p->h1->setAvgValue();
        $p->lt->setAvgValue();
      }

      ksort($this->data->hours,SORT_NUMERIC);
      $size = sizeof($this->data->hours);
      foreach($this->data->hours as &$p){
        $p->po->value = round($p->po->value/$size,3);
        $p->t1->setAvgValue();
        $p->h1->setAvgValue();
        $p->lt->setAvgValue();
      }
 

      //$size = sizeof($this->data->all);
      //foreach($this->data->all as &$p){
      //  $p->t1->values = array_filter($p->t1->values);
      //  if(count($p->t1->values)){
      //    $p->t1->value = array_sum($p->t1->values) / count($p->t1->values);
      //  }
      //}
      //
      //$size = sizeof($this->data->days);
      //foreach($this->data->days as &$p){
      //  $p->t1->values = array_filter($p->t1->values);
      //  if(count($p->t1->values)){
      //    $p->t1->value = array_sum($p->t1->values) / count($p->t1->values);
      //  }
      //}
      //
      //ksort($this->data->hours,SORT_NUMERIC);
      //$size = sizeof($this->data->hours);
      //foreach($this->data->hours as &$p){
      //  $p->power->value = round($p->power->value/$size,3);
      //  $p->t1->values = array_filter($p->t1->values);
      //  if(count($p->t1->values)){
      //    $p->t1->value = array_sum($p->t1->values) / count($p->t1->values);
      //  }
      //}
    }
  
    //----------------------------------------------    
    function print_result(){
      header("content-type: application/json"); 
      echo '{';
      $this->print_result_group('all');
      echo ',';
      $this->print_result_group('days');
      echo ',';
      $this->print_result_group('hours',false);
      echo '}';
    
    }
    //----------------------------------------------    
    function print_result_group($name,$is_datetime=true){

      echo '"'.$name.'":{';
      $len = sizeof($this->data->$name);
      $x=0;
      $y=0;
      $params = Array('po','t1','h1');
      foreach($params as $param){
        echo '"'.$param.'":[';
        foreach($this->data->$name as $point=>$value){
          if($is_datetime){
            $point = ($point+60*60*2)*1000;
          }
          echo "[$point,".$value->{$param}->value."]";
          if($len-1 > $x++){
            echo ",\n";
          }
        }
        $x=0;
        echo "]\n";
        if(sizeof($params)-1 > $y++){
          echo ",\n";
        }        
      }
      echo "}\n";
    }
  }

?>
