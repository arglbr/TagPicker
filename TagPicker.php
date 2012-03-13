<?php
/**
 * TagPicker
 *
 * LICENSE
 *
 * GPL.
 * For the impatients:
 *    - Basically you are able to run, study, change this source code,
 *      redistribute this exact copy or modified versions of this
 *      code too, since you keep the references to the original
 *      author.
 * For the patient ones:
 *    - http://en.wikipedia.org/wiki/GNU_General_Public_License
 *
 * @category   Text processing
 * @package    Helper
 * @copyright  Copyleft (@arglbr)
 * @license    GPL
 * @author     {"name":"Adriano Laranjeira","github":"arglbr","twitter":"arglbr","aboutme":"arglbr"}
 */
class TagPicker
{
	const OUTPUT_JSON   = 1;
	const OUTPUT_XML    = 2;
	const OUTPUT_CSV    = 3;
	const OUTPUT_WPRESS = 4;
	
	const LANG_ENUS = 1;
	const LANG_PTBR = 2;

	private $undesired_list;
	private $desired_list;
	
	private $output_method;
	private $language;
	private $content;
	
	private function setDefaultLanguage($p_language)
	{
		if (! in_array($p_language, array(self::LANG_ENUS, self::LANG_PTBR)))
		{
			throw new Exception ("Unknown language $p_language. The allowed languages are: TagPicker::LANG_ENUS and TagPicker::LANG_PTBR");
		}
		
		switch ($p_language)
		{
			case self::LANG_ENUS:
				$this->language = 'enus';
				break;
			case self::LANG_PTBR:
				$this->language = 'ptbr';
				break;
		}
	}

	private function setDefaultOutputMethod($p_output_method)
	{
		if (! in_array($p_output_method, array(self::OUTPUT_JSON, self::OUTPUT_XML, self::OUTPUT_CSV, self::OUTPUT_WPRESS)))
		{
			throw new Exception ("Unknown output $p_output_method. The allowed output methods are: TagPicker::OUTPUT_JSON, TagPicker::OUTPUT_XML, TagPicker::OUTPUT_CSV, TagPicker::OUTPUT_WPRESS");
		}

		$this->output_method = (int) $p_output_method;
	}

	private function getData($p_url)
	{
		// Eh preciso saber como PARSEAR um conteudo HTML corretamentente.
		$this->content = (isset($p_url)) ? strtolower(file_get_contents($p_url)) : '';
		$this->content = preg_replace("/[\W]/u", ' ', $this->content);
		$this->content = preg_replace('/\s[\s]+/u',' ', $this->content);
		return $this;
	}
	
	private function dismantle()
	{
		$this->content = explode(' ', $this->content);
	}

	private function filterText($p_amount)
	{
		$wlist1 = array();
		$ret    = array();
		
		foreach ($this->content as $word)
		{
			if ( (! $this->isUndesired($word)) || $this->isDesired($word) )
			{
				$wlist1[$word] = (isset($wlist1[$word])) ? $wlist1[$word] + 1 : 1;
			}
		}

		$ret = $this->reArrange($wlist1, $p_amount);
		return $ret;
	}

	private function isUndesired($p_word)
	{
		$found = $this->undesired_list->xpath("//*[name='$p_word']");
		$ret = (array_filter($found) && count($found) > 0) ? true : false;
		return $ret;
	}

	private function isDesired($p_word)
	{
		$found = $this->desired_list->xpath("//*[name='$p_word']");
		$ret = (array_filter($found) && count($found) > 0) ? true : false;
		return $ret;
	}

	private function reArrange($p_list, $p_threshold = 0)
	{
		$ret = array();

		foreach ($p_list as $word => $amount)
		{
			if ($amount >= (int) $p_threshold)
			{
				$ret[$word] = $amount;
			}
		}

		if (count($ret) > 0)
		{
			arsort($ret, SORT_NUMERIC);
		}
		
		return $ret;
	}

	private function getXML($p_array)
	{
		$ret = new \SimpleXMLElement('<?xml version="1.0"?><results></results>');
		
		foreach ($p_array as $word => $amount)
		{
			$ret_item = $ret->addChild('result');
			$ret_item->addChild('name', $word);
			$ret_item->addChild('amount', $amount);
		}
		
		return $ret->asXML();
	}

	private function getCSV($p_array)
	{
		$ret = '';
		
		foreach ($p_array as $word => $amount)
		{
			$ret .= "$word,$amount" . PHP_EOL;
		}
		
		return $ret;
	}

	private function getOutput($p_items)
	{
		switch ( $this->output_method )
		{
			case self::OUTPUT_JSON:
				$ret = json_encode($p_items);
				break;
			case self::OUTPUT_XML:
				$ret = $this->getXML($p_items);
				break;
			case self::OUTPUT_CSV:
				$ret = $this->getCSV($p_items);
				break;
			case self::OUTPUT_WPRESS:
				ksort($p_items);
				$ret = implode(',', array_keys($p_items));
				break;
		}

		return $ret;
	}
	
	public function __construct($p_default_language = self::LANG_PTBR, $p_output_method = self::OUTPUT_JSON)
	{
		$this->setDefaultLanguage($p_default_language);
		$this->setDefaultOutputMethod($p_output_method);
		$this->desired_list     = new \SimpleXMLElement('desired_' . $this->language . '.xml', null, true);
		$this->undesired_list   = new \SimpleXMLElement('undesired_' . $this->language . '.xml', null, true);
		$this->content          = '';
	}

	public function __destruct()
	{
		unset ($this->desired_list, $this->undesired_list);
	}

	public function getTags($p_url, $p_amount)
	{
		$this->getData($p_url)->dismantle();
		$ret = $this->filterText($p_amount);
		print $this->getOutput($ret) . PHP_EOL;
	}
}
