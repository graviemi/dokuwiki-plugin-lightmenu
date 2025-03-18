<?php

// lightmenu utilities class

class lightmenu
{
	// function give lightmenu meta data file path from page id and environment
	// $id : dokuwiki page id
	// return : lightmenu meta data file path
	public static function meta_path(string $id) : string
	{
		global $conf;

		$_id = ':'.$id;
		$pos = strrpos($_id,':');
		$path = strtr(substr($_id,0,$pos),':','/');
		$name = substr($_id,$pos + 1);
		if (($name === $conf['start'] && ($path !== '')) || (basename($path) === $name))
			return sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
		if (is_dir(sprintf('%s/%s/%s',$conf['datadir'],$path,$name)))
			return sprintf('%s%s/%s.lightmenu.json',$conf['metadir'],$path,$name);
		return sprintf('%s%s/%s.txt.lightmenu.json',$conf['metadir'],$path,$name);
	}
}