<?php
/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/


require_once('init.php');
require_once('./lang/class.default.php');
require_once('./lang/'.$config['lang'].'.php');

$lang = new Lang();

$sort = 0;
if(isset($_COOKIE['sort']) && $_COOKIE['sort'] != '') $sort = (int)$_COOKIE['sort'];

if($config['duedateformat'] == 2) $duedateformat = 'm/d/yy';
elseif($config['duedateformat'] == 3) $duedateformat = 'dd.mm.yy';
elseif($config['duedateformat'] == 4) $duedateformat = 'dd/mm/yy';
else $duedateformat = 'yy-mm-dd';

if(!isset($config['firstdayofweek']) || !is_int($config['firstdayofweek']) ||
	$config['firstdayofweek']<0 || $config['firstdayofweek']>6) $config['firstdayofweek'] = 1;

function __($s)
{
	global $lang;
	echo $lang->get($s);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<HEAD>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title><?php __('My Tiny Todolist');?></title>
<link rel="stylesheet" type="text/css" href="style.css?v=@VERSION" media="all">
<?php if(isset($_GET['pda'])): ?>
<meta name="viewport" id="viewport" content="width=device-width">
<link rel="stylesheet" type="text/css" href="pda.css?v=@VERSION" media="all">
<?php else: ?>
<link rel="stylesheet" type="text/css" href="print.css?v=@VERSION" media="print">
<?php endif; ?>
</HEAD>

<body>

<script type="text/javascript" src="jquery/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="jquery/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="ajax.lang.php"></script>
<script type="text/javascript" src="ajax.js"></script>
<script type="text/javascript" src="jquery/jquery.autocomplete.min.js"></script>

<script type="text/javascript">
$().ready(function(){
	$("#tasklist").sortable({cancel:'span,input,a,textarea', delay: 150, update:orderChanged, start:sortStart, items:'> :not(.task-completed)'});
	$("#tasklist").bind("click", tasklistClick);
	$("#edittags").autocomplete('ajax.php?suggestTags', {scroll: false, multiple: true, selectFirst:false, max:8, extraParams:{list:function(){return curList.id}}});
	$("#priopopup").mouseleave(function(){$(this).hide()});
	setSort(<?php echo $sort; ?>,1);
<?php
	if($needAuth)
	{
		echo "\tflag.needAuth = true;\n";
		if(!canAllRead()) echo "\tflag.canAllRead = false;\n";
		if(is_logged()) echo "\tflag.isLogged = true;\n";
		echo "\tupdateAccessStatus();\n";
	}
	echo "\tloadLists(1);\n";
?>
	preloadImg();
	$("#duedate").datepicker({dateFormat: '<?php echo $duedateformat; ?>', firstDay: <?php echo $config['firstdayofweek']; ?>,
		showOn: 'button', buttonImage: 'images/calendar.png', buttonImageOnly: true, changeMonth:true,
		changeYear:true, constrainInput: false, duration:'', nextText:'&gt;', prevText:'&lt;', dayNamesMin:lang.daysMin, 
		monthNamesShort:lang.monthsLong });
<?php if(!isset($_GET['pda'])): ?>
	$("#page_taskedit").draggable({ handle:'h3', stop: function(e,ui){ flag.windowTaskEditMoved=true; tmp.editformpos=[$(this).css('left'),$(this).css('top')]; } }); 
	$("#page_taskedit").resizable({ minWidth:$("#page_taskedit").width(), minHeight:$("#page_taskedit").height(), start:function(ui,e){editFormResize(1)}, resize:function(ui,e){editFormResize(0,e)}, stop:function(ui,e){editFormResize(2,e)} });
<?php endif; ?>

});
$().ajaxSend( function(r,s) {$("#loading").show();} );
$().ajaxStop( function(r,s) {$("#loading").fadeOut();} );
</script>

<div id="wrapper">
<div id="container">
<div id="body">

<h2><?php __('My Tiny Todolist');?></h2>

<div id="loading"><img src="images/loading1.gif"></div>

<div id="bar">
 <div id="msg" style="float:left"><span class="msg-text" onClick="toggleMsgDetails()"></span><div class="msg-details"></div></div>
 <div align="right">
 <span class="menu-owner">
   <a href="#settings" onClick="showSettings();return false;"><?php __('a_settings');?></a>
 </span>
 <span class="bar-delim" style="display:none"> | </span>
 <span id="bar_auth">
  <span id="bar_login"><a href="#" class="nodecor" onClick="showAuth(this);return false;"><u><?php __('a_login');?></u> <img src="images/arrdown.gif" border=0></a></span>
  <span id="authstr">&nbsp;</span>
  <a href="#" id="bar_logout" onClick="logout();return false"><?php __('a_logout');?></a>
 </span>
 </div>
</div>

<br clear="all">

<div id="page_tasks">

<div id="lists" class="mtt-tabs">
 <ul class=""></ul>
 <div class="mtt-htabs">
   <span id="htab_newtask"><?php __('htab_newtask');?> 
	<form onSubmit="return submitNewTask(this)"><input type="text" name="task" value="" maxlength="250" id="task"> <input type="submit" value="<?php __('btn_add');?>"></form>
	<a href="#" onClick="showEditForm(1);return false;" title="<?php __('advanced_add');?>"><img src="images/page_white_edit_bw.png" style="border:none;vertical-align:text-top;" onMouseOver="this.src='images/page_white_edit.png'" onMouseOut="this.src='images/page_white_edit_bw.png'"></a>
	&nbsp;&nbsp;| <a href="#" class="htab-toggle" onClick="addsearchToggle(1);this.blur();return false;"><?php __('htab_search');?></a>
   </span>

   <span id="htab_search" style="display:none"><?php __('htab_search');?>
	<form onSubmit="return searchTasks()"><input type="text" name="search" value="" maxlength="250" id="search" onKeyUp="timerSearch()" autocomplete="off"> <input type="submit" value="<?php __('btn_search');?>"></form>
	&nbsp;&nbsp;| <a href="#" class="htab-toggle" onClick="addsearchToggle(0);this.blur();return false;"><?php __('htab_newtask');?></a> 
	<div id="searchbar"><?php __('searching');?> <span id="searchbarkeyword"></span></div> 
   </span>
 </div>
</div>

<h3>
<span id="sort" onClick="showSort(this);" style="float:right"><span class="btnstr"></span> <img src="images/arrdown.gif"></span>
<span id="taskviewcontainer" onClick="showTaskview(this);"><span class="btnstr"><?php __('tasks');?></span> (<span id="total">0</span>) &nbsp;<img src="images/arrdown.gif"></span>
<span id="tagcloudbtn" onClick="showTagCloud(this);"><span class="btnstr"><?php __('tags');?></span> <img src="images/arrdown.gif"></span>
<span class="mtt-notes-showhide"><?php __('notes');?> <a href="#" onClick="toggleAllNotes(1);this.blur();return false;"><?php __('notes_show');?></a> / <a href="#" onClick="toggleAllNotes(0);this.blur();return false;"><?php __('notes_hide');?></a></span>
</h3>

<div id="taskcontainer">
 <ol id="tasklist" class="sortable"></ol>
</div>

</div> <!-- end of page_tasks -->


<div id="page_taskedit" style="display:none">

<h3 class="mtt-inadd"><?php __('add_task');?></h3>
<h3 class="mtt-inedit"><?php __('edit_task');?></h3>

<form onSubmit="return saveTask(this)" name="edittask">
<input type="hidden" name="id" value="">
<div class="form-row"><span class="h"><?php __('priority');?></span> <SELECT name="prio"><option value="2">+2</option><option value="1">+1</option><option value="0" selected>&plusmn;0</option><option value="-1">&minus;1</option></SELECT> 
 &nbsp; <span class="h"><?php __('due');?> </span> <input name="duedate" id="duedate" value="" class="in100" title="Y-M-D, M/D/Y, D.M.Y, M/D, D.M" autocomplete="off"></div>
<div class="form-row"><div class="h"><?php __('task');?></div> <input type="text" name="task" value="" class="in500" maxlength="250"></div>
<div class="form-row"><div class="h"><?php __('note');?></div> <textarea name="note" class="in500"></textarea></div>
<div class="form-row"><div class="h"><?php __('tags');?></div>
 <table cellspacing="0" cellpadding="0" width="100%"><tr>
  <td><input type="text" name="tags" id="edittags" value="" class="in500" maxlength="250"></td>
  <td width="1" style="white-space:nowrap; padding-left:5px; text-align:right;">
   <a href="#" id="alltags_show" onClick="toggleEditAllTags(1);return false;"><?php __('alltags_show');?></a>
   <a href="#" id="alltags_hide" onClick="toggleEditAllTags(0);return false;" style="display:none"><?php __('alltags_hide');?></a></td>
 </tr></table>
</div>
<div class="form-row" id="alltags" style="display:none;"><?php __('alltags');?> <span class="tags-list"></span></div>
<div class="form-row"><input type="submit" value="<?php __('save');?>" onClick="this.blur()"> <input type="button" value="<?php __('cancel');?>" onClick="cancelEdit();this.blur();return false"></div>
</form>

</div>  <!-- end of page_taskedit -->


<div id="authform" style="display:none">
<form onSubmit="doAuth(this);return false;">
 <div class="h"><?php __('password');?></div><div><input type="password" name="password" id="password"></div><div><input type="submit" value="<?php __('btn_login');?>"></div>
</form>
</div>

<div id="priopopup" style="display:none">
<span class="prio-neg" onClick="prioClick(-1,this)">&minus;1</span> <span class="prio-o" onClick="prioClick(0,this)">&plusmn;0</span>
<span class="prio-pos" onClick="prioClick(1,this)">+1</span> <span class="prio-pos" onClick="prioClick(2,this)">+2</span>
</div>

<div id="taskview" style="display:none">
 <div class="li" onClick="setTaskview(0);taskviewClose();"><span id="view_tasks"><?php __('tasks');?></span></div>
 <div class="li" onClick="setTaskview(1);taskviewClose();"><span id="view_compl"><?php __('tasks_and_compl');?></span></div>
 <div class="li" onClick="setTaskview('past');taskviewClose();"><span id="view_past"><?php __('f_past');?></span> (<span id="cnt_past">0</span>)</div>
 <div class="li" onClick="setTaskview('today');taskviewClose();"><span id="view_today"><?php __('f_today');?></span> (<span id="cnt_today">0</span>)</div>
 <div class="li" onClick="setTaskview('soon');taskviewClose();"><span id="view_soon"><?php __('f_soon');?></span> (<span id="cnt_soon">0</span>)</div>
</div>

<div id="sortform" style="display:none">
 <div id="sortByHand" class="li" onClick="setSort(0);sortClose();"><?php __('sortByHand');?></div>
 <div id="sortByPrio" class="li" onClick="setSort(1);sortClose();"><?php __('sortByPriority');?></div>
 <div id="sortByDueDate"  class="li" onClick="setSort(2);sortClose();"><?php __('sortByDueDate');?></div>
</div>

<div id="tagcloud" style="display:none">
 <div id="tagcloudcancel" onClick="cancelTagFilter();tagCloudClose();"><?php __('tagfilter_cancel');?></div>
 <div id="tagcloudload"><img src="images/loading1_24.gif"></div>
 <div id="tagcloudcontent"></div>
</div>

<div id="mylistscontainer" class="mtt-btnmenu-container" style="display:none">
 <div class="li" onClick="addList()"><?php __('list_new');?></div>
 <div class="li mtt-need-list" onClick="renameCurList()"><?php __('list_rename');?></div>
 <div class="li mtt-need-list" onClick="deleteCurList()"><?php __('list_delete');?></div>
</div>

<div id="page_ajax" style="display:none"></div>

</div>
<div id="space"></div>
</div>

<div id="footer"><div id="footer_content">Powered by <strong><a href="http://www.pozdeev.com/mytinytodo/">myTinyTodo</a></strong> v@VERSION </div></div>

</div>
</body>
</html>