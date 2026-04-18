function confirming(txt,url)
{
if (confirm(txt)) document.location=url;
}

function selectParam(id) {
    
    $("#param_range_" + id).val($("#param_" + id + " option:selected").attr("rel"));
}

function selectGroupChbx(cls)
{

    $("."+cls).each(function(){
                
            //alert($(this).attr('checked'));
            if ($(this).attr('checked')=='checked') $(this).removeAttr('checked');
            else $(this).attr('checked',true);
          });
       
}

function applyCheckBoxStatus(name)
{

   if ($("#"+name+"_control").is(":checked")) {
		$("#"+name).val('1');
   } else {
		$("#"+name).val('0');
   }
                       
}

function selectAllChbx(cls)
{

    $("."+cls).each(function(){
                
            //alert($(this).attr('checked'));
            if ($(this).attr('checked')==true) $(this).attr('checked',false);
            else if ($(this).attr('no_del')=='0') $(this).attr('checked',true);
          });
       
}

function moveAllChbx(cls,table)
{
	var ids='0';
    $("."+cls).each(function(){ 
           // alert($(this).attr('checked'));
            if ($(this).is(":checked") && $(this).attr('no_del')=='0') 
            {
				
				ids+=','+$(this).attr('rel')
			}
          });
    var rub = prompt('Укажите ID категории для переноса товаров', 0);
    
    if (rub > 0)
    {
				$.ajax({
		  type: "GET",
		  url: 'ajax/move_ids.php',
		  data: 'table='+table+'&ids='+ids+'&rub='+rub,
		  success: function(answer) {
			 //alert(answer);
			  document.location.reload();
			
		  //$('#rubs').html(answer);
		  }
		 });
	}   
}

function delAllChbx(cls,table)
{
	var ids='0';
    $("."+cls).each(function(){ 
           // alert($(this).attr('checked'));
            if ($(this).is(":checked") && $(this).attr('no_del')=='0') 
            {
				
				ids+=','+$(this).attr('rel')
			}
          });
    if (confirm('Delete '+ids.replace("0,", "")+'?'))
    {
				$.ajax({
		  type: "GET",
		  url: 'ajax/delete_ids.php',
		  data: 'table='+table+'&ids='+ids,
		  success: function(answer) {
			  $("."+cls).each(function(){
				$(this).attr('checked',false);
			  });
			  document.location.reload();
			//alert(answer);
		  //$('#rubs').html(answer);
		  }
		 });
	}   
}

function popUp($id)
{
	$div=document.getElementById($id);
	if ($div.style.display=='none') $div.style.display='block';
	else $div.style.display='none';
}

function insertToInput($inputID,$val)
{
	$pole=document.getElementById($inputID);
	if ($pole.value=='')$pole.value+=$val;
	else $pole.value+=','+$val;
}
function setInput($inputID,$val)
{
	$pole=document.getElementById($inputID);
	$pole.value=$val;
	alert($inputID+' set to '+$val)
}

function removeFromInput($inputID,$val)
{
	$pole=document.getElementById($inputID);
	$pole.value=','+$pole.value;
	$pole.value+=',';
	$pole.value=$pole.value.replace(','+$val+',',',');
	$pole.value=' '+$pole.value;
	$pole.value=$pole.value.replace(' ,','');
	$pole.value+=' ';
	$pole.value=$pole.value.replace(', ','');
	$pole.value=$pole.value.replace(' ','');
}

function setDiv(http_request) {
 	//alert("111");
        if (http_request.readyState == 4) {
            if (http_request.status == 200) {
                //alert(http_request.responseText);
				//setTimeout("document.getElementById('apDiv1').style.display=http_request.responseText;",400);
            } else {
                alert('There was a problem with the request.');
            }
        }

    }
	
function updateItem(table,field,fid,id) //checked
{
		//document.getElementById('apDiv1').style.display='block';
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
				     // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }

	http_request.onreadystatechange = function(){
		// do the thing
		//setDiv(http_request);
	}
	
	//http_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	if (document.getElementById(fid).checked==true) value=1;
	else value=0;
	//alert(document.getElementById(fid).checked);
	
	http_request.open('GET', 'ajax/update.php?table='+table+'&field='+field+'&value='+value+'&id='+id+'&xr='+Math.random(), true);
	http_request.send(null);
}

function updateItemSelUni(table,fid,st,wh)
		{
		$.ajax({
		  type: "GET",
		  url: 'ajax/update_uni.php',
		  data: 'table='+table+'&st='+st+'&wh='+wh+'&xr='+Math.random(),
		  success: function(answer) {
			alert(answer);
		  //$('#rubs').html(answer);
		  }
		 });
		}

function updateItemSel(table,field,fid,id)
{
		//document.getElementById('apDiv1').style.display='block';
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
				     // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }

	http_request.onreadystatechange = function(){
		// do the thing
		setDiv(http_request);
	}
	
	//http_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	
	obj=document.getElementById(fid);
	value=obj[obj.selectedIndex].value;
	
	http_request.open('GET', 'ajax/update.php?table='+table+'&field='+field+'&value='+value+'&id='+id+'&xr='+Math.random(), true);
	//alert('ajax/update.php?table=<? echo $tablei;?>&field='+field+'&value='+value+'&id='+id+'&xr='+Math.random());
	http_request.send(null);
}

function updateItemVal(table,field,fid,id)
{
		//document.getElementById('apDiv1').style.display='block';
		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
				     // See note below about this line
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }

        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }

	http_request.onreadystatechange = function(){
		// do the thing
		setDiv(http_request);
	}
	
	
	//http_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	value = $('#'+fid).val();
	
	http_request.open('GET', 'ajax/update.php?table='+table+'&field='+field+'&value='+value+'&id='+id+'&xr='+Math.random(), true);
	http_request.send(null);
}

function updateItemVal2(table,field,fid,where)
		{
		value=document.getElementById(fid).value;
		$.ajax({
		  type: "GET",
		  url: 'ajax/update.php',
		  data: 'table='+table+'&field='+field+'&value='+value+'&wh='+where+'&xr='+Math.random(),
		  success: function(answer) {
			 //alert(answer);
		  }
		 });
		}

function alertContents(http_request,div) {
 	//alert("222");
        if (http_request.readyState == 4) {
			//alert("333");
            if (http_request.status == 200) {
				//alert("444");
                //alert(http_request.responseText);
				document.getElementById(div).innerHTML=http_request.responseText;
            } else {
                alert('There was a problem with the request.');
            }
        }

    }
