<?php

// lightmenu utilities class

class lightmenu
{
	protected static $syntax = [
		'|^-head$|' => 'head',
		'|^-min=(\d+)$|' => 'min'
	];

	protected static $options;

	protected static function _log(string $format, string ...$params)
	{
		file_put_contents(__DIR__.'/lightmenu.log',date('Y/m/d H:i:s').':'.vsprintf($format,$params),FILE_APPEND);
	}

	protected static function _options(string $match) : array
	{
		$options = [];

		$list = preg_split('|\s+|',$match);
		foreach ($list as $option)
		{
			foreach (self::$syntax as $regex => $name)
			{
				if (preg_match($regex,$option,$matches))
				{
					$options[$name] = (count($matches) === 2)?$matches[1]:true;
					break;
				}
			}
		}

		return $options;
	}

	// function give lightmenu meta data file path from page id and environment
	// $id : dokuwiki page id
	// return : lightmenu meta data file path
	protected static function _meta_path(string $id) : string
	{
		global $conf;

		$_id = ':'.$id;
		$pos = strrpos($_id,':');
		$path = strtr(substr($_id,0,$pos),':','/');
		$name = substr($_id,$pos + 1);
		if (($name === $conf['start'] && ($path !== '')) || (basename($path) === $name))
			return $path;
		if (is_dir(sprintf('%s%s/%s',$conf['datadir'],$path,$name)))
			return sprintf('%s/%s',$path,$name);
		return sprintf('%s/%s.txt',$path,$name);
	}

	// function give lightmenu meta data file path from page id and environment
	// $id : dokuwiki page id
	// return : lightmenu meta data file path
	protected static function _subpath(string $id) : string
	{
		global $conf;

		$parts = explode(':',$id);
		$path = implode('/',array_map(function ($e) {
			return rawurlencode($e);
		}, array_slice($parts, 0, -1)));
		$name = rawurlencode($parts[count($parts) - 1]);
		if (($name === $conf['start'] && ($path !== '')) || (basename($path) === $name))
			return $path;
		if (is_dir(sprintf('%s/%s/%s',$conf['datadir'],$path,$name)))
			return sprintf('/%s/%s',$path,$name);
		return sprintf('/%s/%s.txt',$path,$name);
	}


	// return data about wiki path identified by file subpath and name
	// subpath : the sub path to the file from data page root directory
	// name : the file name of the page or directory (ex : start.txt)
	// returned values : array
	//   (boolean) true if the $name is a page, false if a directory
	//   (string) the dokuwiki id of the page
	//   (array) the lightmenu meta data
	protected static function _get_page_data(string $subpath, string $name) : array
	{
		global $conf;

		$path = sprintf('%s%s/%s.lightmenu.json',$conf['metadir'],$subpath,$name);
		$data = [];
		if (is_file($path) && is_readable($path))
			$data = json_decode(file_get_contents($path),true,2,JSON_THROW_ON_ERROR);
		return [
			$is_page = substr($name,-4) === '.txt',
			rawurldecode(($is_page)?substr($name,0,-4):$name),
			$data
		];
	}

	protected static function _set_page_data(string $subpath, array &$data)
	{
		global $conf;

		$path = sprintf('%s%s.lightmenu.json',$conf['metadir'],$subpath);
		if (is_file($path) && (! is_writable($path)))
			throw new Exception(sprintf('Lightmenu : meta data file "%s" not writable.',$path));
		if (is_file($path) && (count($data) === 0))
			unlink($path);
		else
		{
			if (! is_dir(dirname($path)))
				mkdir(dirname($path),0755,true);
			if (file_put_contents($path,json_encode($data,JSON_THROW_ON_ERROR)) === false)
				throw new Exception('Lightmenu unable to write meta data.');
		}
	}

	protected static function _touch_sidebar()
	{
		global $conf;

		if (is_writeable($path = $conf['datadir'].'/'.$conf['sidebar'].'.txt'))
			touch($path);
	}

	public static function update(string $id, string &$contents)
	{
		$data = [];
		if (preg_match('|<lm:([^>]+)>|',$contents,$matches))
			$data = json_decode(trim($matches[1]),true,2,JSON_THROW_ON_ERROR);
		if (preg_match('/(?:^|[^=])======([^=\n]+)======(?:$|[^=])/',$contents,$matches))
			$data['head'] = trim($matches[1]);
		self::_set_page_data(self::_subpath($id),$data);
		self::_touch_sidebar();
	}

	protected static function _browse(string $subpath = '') : array
	{
		global $conf;

		$tree = [[],[]];
		$path = $conf['datadir'].$subpath;
		$list = scandir($path);

		foreach ($list as $name)
		{
			if (($name === '.') || ($name === '..'))
				continue;
			$filepath = $path.'/'.$name;
			[$is_page,$id,$data] = self::_get_page_data($subpath,$name);
			if (is_dir($filepath))
				$tree[0][] = [$id,$data,self::_browse($subpath.'/'.$name)];
			elseif ($is_page)
			{
				$short = substr($name,0,strrpos($name,'.'));
				if (($id === $conf['start']) || is_dir($path.'/'.$short) || ($short === basename($path)))
					continue;
				$tree[1][] = [$id,$data];
			}
		}
		$sort = function ($a,$b) { return strcmp($a[0],$b[0]); };
		usort($tree[0],$sort);
		usort($tree[1],$sort);
		return $tree;
	}

	/* browse the wiki hierarchy to get data needed for Lightmenu tree.
		$options : string of options after "<lightmenu" tag.
		return : array with start page data, wiki hierachy tree data and options
	*/
	public static function get_data(string $options) : array
	{
		global $conf;

		[$is_page,$id,$data] = self::_get_page_data('',$conf['start'].'.txt');
		return [[$id,$data],self::_browse(),lightmenu::_options($options)];
	} 

	protected static function _get_label(string $id,array &$data) : string
	{
		global $conf;

		if (isset($data['label.'.$conf['lang']]))
			return $data['label.'.$conf['lang']];
		if (isset($data['label']))
			return $data['label'];
		if (self::$options['head'] && isset($data['head']))
			return $data['head'];
		return $id;
	}

	protected static function _format_attributes(array &$metas) : string
	{
		$html = '';

		foreach ($metas as $name => $value)
		{
			if ((strncmp($name,'label',5) === 0) || ($name === 'head') || ($name === 'title') || ($name === 'href'))
				continue;
			$html .= sprintf(' %s="%s"',$name,$value);
		}

		return $html;
	}

	protected static function _get_page(string $prefix, string $id, array &$metas) : string
	{
		$label = self::_get_label($id,$metas);
		$html = '<div class="child">'.PHP_EOL;
		$html .= sprintf('<span class="label" id="lm-%s%s"><a%s title="%s" href="%s">%s</a></span>'.PHP_EOL,
			$prefix,$id,self::_format_attributes($metas),isset($metas['title'])?$metas['title']:$label,wl($prefix.$id),trim($label));
		$html .= '</div>'.PHP_EOL;
		return $html;
	}

	protected static function _get_html(array &$data, string $prefix = '', int $level = 0) : string
	{
		global $conf;

		$html = '';

		foreach ($data[0] as [$id,$metas,$children])
		{
			$label = self::_get_label($id,$metas);
			$html .= '<div class="child">'.PHP_EOL;
			$html .= sprintf('<input type="checkbox" id="checkbox-%s%s" />',$prefix,$id);
			$html .= sprintf('<label class="label" id="lm-%s%s" for="checkbox-%s%s"><a%s title="%s" href="%s">%s</a></label>'.PHP_EOL,$prefix,$id,$prefix,$id,
				self::_format_attributes($metas),isset($metas['title'])?$metas['title']:$label,wl($prefix.$id.':'),trim($label));
			if (count($children) > 0)
				$html .= '<div class="tree">'.PHP_EOL.self::_get_html($children,$prefix.$id.':').'</div>'.PHP_EOL;
			$html .= '</div>'.PHP_EOL;
		}

		foreach ($data[1] as [$id,$metas])
		{
			if (($prefix === '') && ($id === $conf['sidebar']))
				continue;
			$html .= self::_get_page($prefix,$id,$metas);
		}

		return $html;
	}

	public static function render(array &$data) : string
	{
		self::$options = $data[2];
		$html = '<div class="lm">';
		$html .= self::_get_page('',$data[0][0],$data[0][1]);
		$html .= self::_get_html($data[1]);
		$html .= '</div>';

		return $html;
	}

	protected static function _rescan(string $subpath = '')
	{
		global $conf;

		$path = $conf['datadir'].$subpath;
		$list = scandir($path);
		foreach ($list as $name)
		{
			if (($name === '.') || ($name === '..'))
				continue;
			if (($subpath === '') && ($name === $conf['sidebar'].'.txt'))
				continue;
			$filepath = $path.'/'.$name;
			[$is_page,$id,$data] = self::_get_page_data($subpath,$name);
			if (is_dir($filepath))
				self::_rescan($subpath.'/'.$name);
			elseif ($is_page)
			{
				$contents = file_get_contents($filepath);
				if (preg_match('/(?:^|[^=])======([^=\n]+)======(?:$|[^=])/',$contents,$matches))
				{
					$data['head'] = trim($matches[1]);
					if ((($id === $conf['start']) && ($subpath !== '')) || ($id === basename($path)))
						self::_set_page_data($subpath,$data);
					elseif (is_dir($path.'/'.$id))
						self::_set_page_data($subpath.'/'.$id,$data);
					else						
						self::_set_page_data($subpath.'/'.$name,$data);
				}
			}
		}
	}

	public static function rescan()
	{
		global $conf;

		self::_rescan();
		self::_touch_sidebar();
	}
}