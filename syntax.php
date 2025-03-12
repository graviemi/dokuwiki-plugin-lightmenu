<?php

use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_lightmenu extends SyntaxPlugin
{
	public function getType()
	{
		return 'substition';
	}

	public function getPType()
	{
		return 'block';
	}

	public function getSort()
	{
		return 138;
	}

	public function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern('<lightmenu>', $mode, 'plugin_lightmenu');
		$this->Lexer->addSpecialPattern('<lm:[^>]*>', $mode, 'plugin_lightmenu');
	}

	// return data about wiki path identified by file subpath and name
	// subpath : the sub path to the file from data page root directory
	// name : the file name of the page or directory (ex : start.txt)
	// returned values : array
	//   (boolean) true if the $name is a page, false if a directory
	//   (string) the dokuwiki id of the page
	//   (array) the lightmenu meta data
	protected function _data(string $subpath, string $name) : array
	{
		global $conf;

		$path = sprintf('%s%s/%s.lightmenu.json',$conf['metadir'],$subpath,$name);
		$data = [];
		if (is_file($path) && is_readable($path))
			$data = json_decode(file_get_contents($path),true,2,JSON_THROW_ON_ERROR);
		return [
			$is_page = substr($name,-4) === '.txt',
			($is_page)?substr($name,0,-4):$name,
			$data
		];
	}

	protected function _browse(string $subpath = '') : array
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
			[$is_page,$id,$data] = $this->_data($subpath,$name);
			if (is_dir($filepath))
				$tree[0][] = [$id,$data,$this->_browse($subpath.'/'.$name)];
			elseif ($is_page)
			{
				if (($id === $conf['start']) || is_dir($path.'/'.$id) || ($id === basename($path)))
					continue;
				$tree[1][] = [$id,$data];
			}
		}
		$sort = function ($a,$b) { return strcmp($a[0],$b[0]); };
		usort($tree[0],$sort);
		usort($tree[1],$sort);
		return $tree;
	}

	public function handle($match, $state, $pos, Doku_Handler $handler)
	{
		global $conf, $ID;

		try 
		{
			if ($match === '<lightmenu>')
			{
				[$is_page,$id,$data] = $this->_data('',$conf['start'].'.txt');
				return [[$id,$data],$this->_browse()];
			}
			elseif (preg_match('|^<lm:([^>]+)>$|',$match,$matches))
			{
				$_id = ':'.$ID;
				$data = json_decode($matches[1],true,2,JSON_THROW_ON_ERROR);
				$pos = strrpos($_id,':');
				$path = strtr(substr($_id,0,$pos),':','/');
				$name = substr($_id,$pos + 1);
				$page_path = sprintf('%s%s/%s.txt',$conf['datadir'],$path,$name);
				if ($name === $conf['start'] && ($path !== ''))
					$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
				elseif (is_dir(sprintf('%s/%s/%s',$conf['datadir'],$path,$name)))
					$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
				elseif (basename($path) === $name)
					$meta_path = sprintf('%s/%s.lightmenu.json',$conf['metadir'],$path);
				else
					$meta_path = sprintf('%s%s/%s.txt.lightmenu.json',$conf['metadir'],$path,$name);
				if ((! file_exists($meta_path)) || (filemtime($page_path) > filemtime($meta_path)))
					file_put_contents($meta_path,json_encode($data));
				return null;
			}
		}
		catch (Exception $e)
		{
			return sprintf('lightmenu error in json : %s',$e->getMessage());
		}
		return null;
	}

	protected function _label(string $id,array $data) : string
	{
		global $conf;

		if (isset($data['label.'.$conf['lang']]))
			return $data['label.'.$conf['lang']];
		if (isset($data['label']))
			return $data['label'];
		return $id;
	}

	protected function _attribute(string $name, string $value) : string
	{
		if (strlen($value) > 0)
			return sprintf(' %s="%s"',$name,$value);
		return '';
	}

	protected function _page(string $prefix, string $id, array $metas) : string
	{
			$label = $this->_label($id,$metas);
			$html = '<div class="child">'.PHP_EOL;
			$html .= sprintf('<span class="label" id="lm-%s%s"><a%s%s title="%s" href="doku.php?id=%s%s">%s</a></span>'.PHP_EOL,
				$prefix,$id,
				$this->_attribute('class',$metas['class'] ?? ''),$this->_attribute('style',$metas['style'] ?? ''),
				$label,$prefix,$id,trim($label));
			$html .= '</div>'.PHP_EOL;

			return $html;
	}

	protected function _html(array $data, string $prefix = '', int $level = 0) : string
	{
		$html = '';
		foreach ($data[0] as [$id,$metas,$children])
		{
			$label = $this->_label($id,$metas);
			$html .= '<div class="child">'.PHP_EOL;
			$html .= sprintf('<input type="checkbox" id="lm-%s%s" />',$prefix,$id);
			$html .= sprintf('<label class="label" for="lm-%s%s"><a%s%s title="%s" href="doku.php?id=%s%s:">%s</a></label>'.PHP_EOL,$prefix,$id,
				$this->_attribute('class',$metas['class'] ?? ''),$this->_attribute('style',$metas['style'] ?? ''),
				$label,$prefix,$id,trim($label));
			if (count($children) > 0)
				$html .= '<div class="tree">'.PHP_EOL.$this->_html($children,$prefix.$id.':').'</div>'.PHP_EOL;
			$html .= '</div>'.PHP_EOL;
		}
		foreach ($data[1] as [$id,$metas])
		{
			if (($prefix === '') && ($id === 'sidebar'))
				continue;
			$html .= $this->_page($prefix,$id,$metas);
		}
		return $html;
	}

	public function render($format, Doku_Renderer $renderer, $data)
	{
		global $conf, $ID;

		if ($data === null)
			return true;
		if (is_string($data))
		{
			$renderer->doc .= sprintf('<p class="lm_error">%s</p>',$data);
			return true;
		}

		if ($format == 'xhtml') {
			$renderer->doc .= '<div class="lm">';
			$renderer->doc .= $this->_page('',$data[0][0],$data[0][1]);
			$renderer->doc .= $this->_html($data[1]);
			$renderer->doc .= '</div>';
			return true;
		}
		return false;
	}
}
