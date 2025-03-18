<?php

require_once __DIR__.'/lightmenu.class.php';

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

class action_plugin_lightmenu extends ActionPlugin
{
	public function register(EventHandler $controller)
	{
		$controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'lm_update');
	}

	// take care of removing lightmenu meta data and sidebar cache on wiki page save.
	public function lm_update($event)
	{
		global $conf;

		// delete lightmenu metadata if last modification time older than wiki page file last modification time.
/*		$_id = ':'.$event->data['id'];
		$pos = strrpos($_id,':');
		$path = strtr(substr($_id,0,$pos),':','/');
		$name = substr($_id,$pos + 1);
		if ($name === $conf['start'] && ($path !== ''))
			$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
		elseif (is_dir(sprintf('%s/%s/%s',$conf['datadir'],$path,$name)))
			$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
		elseif (basename($path) === $name)
			$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
		else
			$meta_path = sprintf('%s%s/%s.txt.lightmenu.json',$conf['metadir'],$path,$name);*/
		$meta_path = lightmenu::meta_path($event->data['id']);
		if (file_exists($meta_path)
			&& ((! file_exists($event->data['file'])) || (filemtime($meta_path) < filemtime($event->data['file']))))
			unlink($meta_path);

		// update last modification time of sidebar to force cache update
		$path = $conf['datadir'].'/sidebar.txt';
		if (! is_writeable($path))
			return;
		touch($path);
	}
}
