<?php
//(c) Alexander Bunke - templates script, last update 16.12.2014
//HTML<-BLOCK<-MODULE (dlya cicla)
class BunTempl
{
	var $html;
	var $page;
	var $sttime;
	var $main_ccl_name; //potom filename
	var $main_ccl_body; //{***}***{/***} dlya replace
	var $main_ccl_html; //{***} ... {/***}
	var $main_ccl_module; // s zamenami
	var $main_ccl_block;  //suma moduley
	
	var $local_ccl_body; //{***}***{/***} dlya replace
	var $local_ccl_html; //{***} ... {/***}
	var $local_ccl_module; // s zamenami
	var $local_ccl_block;  //suma moduley
	
	var $temp_html;

	// Constructor
	function PrecTime() //Spasibo Sprinteru
	{
		$time1=explode(" ", microtime());
		$usec = (double)$time1[0];
		$sec = (double)$time1[1];
		return ($sec + $usec);
	}
	function BunTempl($src = null, $tpl = null)
	{
		if ($src != null) {
            $file=fopen($src,'r');
            $this->html=fread($file,filesize($src));
            $this->page=$this->html;
            fclose ($file);
        } elseif ($tpl != null) {
            $this->page=$tpl;
        }
        else {
            return null;
        }
        
        $this->sttime=$this->PrecTime();
	}
	
	function Replace($tag,$txt)
	{
		if (strstr($this->page,$tag)) {
            $this->ReplaceTag($tag,$txt);
            return 1;
        }
		else {
            return 0;
        }
	}
    
	//Main cicles ------------------------------------------
    
	//Konec Main cicla, podstanovka v telo page i zapis v cache file
	function EndMainCcl()
	{
		if ($this->main_ccl_body!="")
		{
			$this->page=str_replace($this->main_ccl_body,$this->main_ccl_block,$this->page);
	        if (file_exists("cache/"))
			{
				$src="cache/".$this->main_ccl_name.".html";
				$file=fopen($src,'w+');
				fputs($file,$this->main_ccl_block,strlen($this->main_ccl_block));
				fclose ($file);
			}
			$this->main_ccl_html="";
			$this->main_ccl_module="";
			$this->main_ccl_block="";
			$this->main_ccl_name="";
			$this->main_ccl_body="";
		}
	}
	function EMC()
	{
		$this->EndMainCcl();
	}
	
	function StartMainCcl($cclname,$caching=0)
    {
        $this->EndMainCcl(); //yaksho stariy ne zaversheno
        //echo $caching;
		$this->main_ccl_name=$cclname;
		$pos1 = strpos($this->page, '{'.$cclname.'}');
		$pos2 = strpos($this->page, '{/'.$cclname.'}');
		$l=strlen('{'.$cclname.'}');
		$this->main_ccl_html=substr($this->page, $pos1+$l,$pos2-$pos1-$l);
		$this->main_ccl_body='{'.$cclname.'}'.$this->main_ccl_html.'{/'.$cclname.'}';
		
		//echo $this->main_ccl_body;
		//echo $this->main_ccl_html;
		if ((@filesize("cache/$cclname.html")>0) && ($caching==1))
		{
			$this->page=str_replace($this->main_ccl_body,file_get_contents("cache/$cclname.html"),$this->page);
			$this->main_ccl_html="";
			$this->main_ccl_module="";
			$this->main_ccl_block="";
			$this->main_ccl_name="";
			$this->main_ccl_body="";
			return 0;
		}
		else
		{
			$this->main_ccl_module=$this->main_ccl_html;
			return 1;
		}
    }
	function SMC($cclname,$caching=0)
	{
		$this->StartMainCcl($cclname,$caching);
	}
    //Zamena v module Main CCL
	function ReplaceInMainCcl($tag,$txt)
	{
		$this->main_ccl_module=str_replace($tag,$txt,$this->main_ccl_module);
	}
	function RMC($tag,$txt)
	{
		$this->ReplaceInMainCcl($tag,$txt);
	}
    
	//posle tela Main cilca
    function AddMainMTB()
	{
		$this->main_ccl_block.=$this->main_ccl_module;
		$this->main_ccl_module=$this->main_ccl_html;
	}
		//posle tela Main cilca
    function AMTB()
	{
		$this->AddMainMTB();
	}
	
    //Zamena v MainCCL vseh peremennuh iz $row
	function RMCAll($row,$pref="",$notend=0)
	{
		foreach($row as $key => $value)
		{
			$this->ReplaceInMainCcl("{".$pref."".$key."}",stripslashes($value));
		}
		if ($notend==0)  $this->AddMainMTB();
	}
	
	//Cicle v cicle (Local) --------------------------------------
	function StartLocalCcl($cclname)
    {
        $this->EndLocalCcl(); //yaksho stariy ne zaversheno
		$pos1 = strpos($this->main_ccl_html, '{'.$cclname.'}');
		$pos2 = strpos($this->main_ccl_html, '{/'.$cclname.'}');
		$l=strlen('{'.$cclname.'}');
		$this->local_ccl_html=substr($this->main_ccl_html, $pos1+$l,$pos2-$pos1-$l);
		$this->local_ccl_body='{'.$cclname.'}'.$this->local_ccl_html.'{/'.$cclname.'}';
		
		//echo $this->main_ccl_body;
		//echo $this->main_ccl_html;
		$this->local_ccl_module=$this->local_ccl_html;
		return 1;

    }
	function SLC($cclname)
	{
		$this->StartLocalCcl($cclname,$caching);
	}
	
	//Zamena v module Local CCL
	function ReplaceInLocalCcl($tag,$txt)
	{
		$this->local_ccl_module=str_replace($tag,$txt,$this->local_ccl_module);
	}
	function RLC($tag,$txt)
	{
		$this->ReplaceInLocalCcl($tag,$txt);
	}
	
	//posle tela Local cilca
    function AddLocalMTB()
	{
		$this->local_ccl_block.=$this->local_ccl_module;
		$this->local_ccl_module=$this->local_ccl_html;
	}
	//posle tela Local cilca
    function ALTB()
	{
		$this->AddLocalMTB();
	}
	
	//Konec Local cicla, podstanovka v telo page
	function EndLocalCcl()
	{
		if ($this->local_ccl_body!="")
		{
			$this->main_ccl_module=str_replace($this->local_ccl_body,$this->local_ccl_block,$this->main_ccl_module);
			
			$this->local_ccl_html="";
			$this->local_ccl_module="";
			$this->local_ccl_block="";
			$this->local_ccl_body="";
		}
	}
	function ELC()
	{
		$this->EndLocalCcl();
	}
	//End Local cicles
	
	    
    //Vstavka vnutrennego shablona vo vneshniy (slitie)
	function InsertHtml($tag,$src)
	{
		$file=fopen($src,'r');
		$this->temp_html=fread($file,filesize($src));
		fclose($file);
		$this->page=str_replace($tag,$this->temp_html,$this->page);
		$this->temp_html="";
	}
	
		//Zamena v osnovnom tele
	function ReplaceTag($tag,$txt)
	{
		$this->page=str_replace($tag,stripslashes($txt),$this->page);
	}
	//Vivod
	function PrintPage($mode)
	{
		$this->EndMainCcl(); //yaksho ne zaversheno
		echo $this->page;
		if ($mode==1) echo "<!-- Gentime=".($this->PrecTime()-$this->sttime)."-->";
		if ($mode==2) echo "Gentime=".($this->PrecTime()-$this->sttime);
	}
}

function delPovt($t)
{
	//$t=trim(strip_tags($t));
	$t=trim($t);
	
	for($b=0;$b<strlen($t)-3;$b++) {
		if ($t[$b]==$t[$b+1] && $t[$b]==$t[$b+2] && $t[$b]==$t[$b+3]) $t[$b+3]=' ';
	}
	return $t;
}

?>
