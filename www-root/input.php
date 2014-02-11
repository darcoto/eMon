<?php

	$p1 = isset($_REQUEST['p1'])?$_REQUEST['p1']:null;
	$p2 = isset($_REQUEST['p2'])?$_REQUEST['p2']:null;
	$t1 = isset($_REQUEST['t1'])?$_REQUEST['t1']:null;
	$h1 = isset($_REQUEST['h1'])?$_REQUEST['h1']:null;
	$t2 = isset($_REQUEST['t2'])?$_REQUEST['t2']:null;
	$h2 = isset($_REQUEST['h2'])?$_REQUEST['h2']:null;
	$light = isset($_REQUEST['light'])?$_REQUEST['light']:null;

	$timestamp = isset($_REQUEST['timestamp'])?$_REQUEST['timestamp']:time();
  //return;
  file_put_contents('../power.log',"$timestamp $p1 $t1 $h1 $t2 $h2 $light $p2\n",FILE_APPEND);
