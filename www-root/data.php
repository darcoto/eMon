<?php

  ini_set("display_errors",1);
  
  $o = new Emon();
  $o->run();

  //============================================
  class EmonGroup{
    
    public $points = Array();
    
    //---------------------------------------------------
    function __construct($group_format,$x_values = 'datetime',$limit_days = null){
      $this->group_format = $group_format;
      $this->limit_days = $limit_days;
      $this->x_values = $x_values;
    }
    
    //---------------------------------------------------
    function checkPoint($time){
      if($this->limit_days){
        if($time < time()-$this->limit_days*24*60*60){
          return false;  
        }
      }
      return true;
    }
      
    //---------------------------------------------------
    function addPoint($time){
      $tt = date($this->group_format,$time);
      if($this->x_values == 'datetime'){
        $tt = strtotime($tt);
      }
      if(!isset($this->points[$tt]))  $this->points[$tt] = new EmonPoint();
      
      return $this->points[$tt];
    }
  }
  
  //============================================
  class EmonPoint{
    public $p1;
    public $p2;
    public $t1;
    public $h1;
    public $lt;
    function __construct(){
      $this->p1 = new EmonParam();
      $this->p2 = new EmonParam();
      $this->t1 = new EmonParam();
      $this->h1 = new EmonParam();
      $this->lt = new EmonParam();
    }
    
  //---------------------------------------------------
    function addValue($row){

      $p1 = isset($row[1])?$row[1]:null;
      $p2 = isset($row[7])?$row[7]:null;
      $t1 = isset($row[2])?$row[2]:null;
      $h1 = isset($row[3])?$row[3]:null;
      $lt = isset($row[6])?$row[6]:null;      

      $this->p1->value += $p1/1000;
      $this->p2->value += $p2/1000;
      $this->t1->values[] = $t1;
      $this->h1->values[] = $h1;
      $this->lt->values[] = $lt;
    }
  }
  
  //============================================
  class EmonParam{
    public $value = 0;
    public $values = Array(); 
    
    //---------------------------------------------------
    function setAvgValue(){
      $this->values = array_filter($this->values);
      if(count($this->values)){
        $this->value = round(array_sum($this->values) / count($this->values),1);
      }
    }
  }
  
  //============================================
  class Emon{
   
    //----------------------------------------------
    function __construct(){
      $this->log_file = "../power.log";

      $this->data = Array();
      $this->data['all'] = new EmonGroup('Y-m-d H:i:0','datetime',3);
      $this->data['days'] = new EmonGroup('Y-m-d','datetime');
      $this->data['hours'] = new EmonGroup('G','hours');
    }
    
    //----------------------------------------------
    function run(){
      $this->read_log();
      $this->print_result();
    }
    
    //----------------------------------------------    
    function read_log(){

      $file = new SplFileObject($this->log_file);
      $file->seek(10000);
      while (!$file->eof()) {
        
        $row = $file->fgets();
        $row = explode(" ",$row);
        if(sizeof($row) < 2) continue;

        $time = $row[0];
        
        foreach($this->data as $group){
          if($group->checkPoint($time)){
            $group->addPoint($time)->addValue($row);
          }
        }
      }

      ksort($this->data['hours']->points,SORT_NUMERIC);
      $size = sizeof($this->data['hours']->points);
      foreach($this->data['hours']->points as &$p){
        $p->p1->value = round($p->p1->value/$size,3);
        $p->p2->value = round($p->p2->value/$size,3);
      }

      foreach($this->data as $group){
        foreach($group->points as &$p){
          $p->t1->setAvgValue();
          $p->h1->setAvgValue();
          $p->lt->setAvgValue();
        }
      }
      
    }
  
    //----------------------------------------------    
    function print_result(){
      header("content-type: application/json"); 
      echo '{';
      $size = sizeof($this->data);
      $i=0;
      foreach($this->data as $group_name=>$group){        
        $this->print_result_group($group_name,$group);
        if($i+1 < $size){
          echo ',';
        }
        $i++;
      }
      echo '}';
   
    }
    //----------------------------------------------    
    function print_result_group($group_name,$group){

      echo '"'.$group_name.'":{';
      $len = sizeof($group->points);
      $x=0;
      $y=0;
      $params = Array('p1','p2','t1','h1');
      foreach($params as $param){
        echo '"'.$param.'":[';
        foreach($group->points as $point=>$value){
          if($group->x_values == 'datetime'){
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
