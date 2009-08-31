<?php
class Jp7_InterAdmin_Tagcloud
{
	public $maxFontSize = 20;
	public $minFontSize = 9;
	public $limit = 10;
	public function getTags($class = 'InterAdmin') {
		global $db_prefix, $lang;
		$tags_arr = array();
		$sql = "SELECT id, hits FROM " . $db_prefix . $lang->prefix .
		" WHERE hits > 0" .
		" ORDER BY date_hit DESC" .
		" LIMIT " . $this->limit;
		$rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$interadminCloud = new $class($row->id);
			$interadminCloud->hits = $row->hits;
			$tags_arr = array_merge($tags_arr, $interadminCloud->getTags());
		}
		$rs->Close();
		//krumo($tags_arr);
		$tags_arr_unique = array();
		foreach ($tags_arr as $tag) {
			$tag_key = ($tag->id) ? $tag->id_tipo . ';' . $tag->id : $tag->id_tipo;
			$tags_arr_unique[$tag_key]['obj'] = $tag;
			$tags_arr_unique[$tag_key]['hits'] += $tag->interadmin->hits;
		}
		$min = 1000;
		$max = 0;
		foreach ($tags_arr_unique as $tag) {
			if ($tag['hits'] < $min) {
				$min = $tag['hits'];
			}
			if ($tag['hits'] > $max) {
				$max = $tag['hits'];
			}
		}
		
		$diff = $max - $min;
		$fontSizeDiff = $this->maxFontSize - $this->minFontSize;
		foreach ($tags_arr_unique as $key => $tag) {
			$obj = $tag['obj'];
			if ($max > $min) {
				if ($this->type == 'linear') {
					// Linear
					$weight = ($tag['hits'] - $min) / $diff;
				} else {
					// Logarítmo
					$weight = (log($tag['hits']) - log($min)) / (log($max) - log($min));
				}
				// Final
				$tags_arr_unique[$key]['fontSize'] = $this->minFontSize + round($fontSizeDiff * $weight);
			} else {
				$tags_arr_unique[$key]['fontSize'] = $this->minFontSize + round($fontSizeDiff / 2);
			}
		}
		return $tags_arr_unique;
	}
}
?>