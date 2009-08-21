<?php
class Jp7_InterAdmin_Tagcloud
{
	public $maxFontSize;
	public $minFontSize;
	public $tags;
	public function getTags() {
		global $db_prefix;
		$tags_arr = array();
		$sql = "SELECT id, tags, hits FROM " . $db_prefix .
		" WHERE tags <> ''" .
		" ORDER BY date_hit DESC" .
		" LIMIT 10";
		$rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$interadminCloud = new InterAdmin($row->id);
			$interadminCloud->tags = $row->tags;
			$interadminCloud->hits = $row->hits;
			$tags_arr = array_merge($tags_arr, $interadminCloud->getTags());
		}
		$rs->Close();
		//krumo($tags_arr);
		$tags_arr_unique = array();
		foreach ($tags_arr as $tag) {
			$tags_arr_unique[$tag->id_tipo . '_' . $tag->id]['obj'] = $tag;
			$tags_arr_unique[$tag->id_tipo . '_' . $tag->id]['hits'] += $tag->interadmin->hits;
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
		
		//echo $min . '<br />';
		//echo $max . '<br />';
		$diff = $max - $min;
		$fontSizeDiff = $this->maxFontSize - $this->minFontSize;
		foreach ($tags_arr_unique as $key => $tag) {
			$obj = $tag['obj'];
			// Linear
			//$weight = ($tag['hits']-$min)/$diff;
			// Logarítmo
			$weight = (log($tag['hits'])-log($min))/(log($max)-log($min));
			// Final
			$tags_arr_unique[$key]['fontSize'] = $this->minFontSize + round($fontSizeDiff * $weight);
			/*
			if ($tag['hits'] > $min) {
				$size = ($font_size_max * ($tag['hits'] - $min)) / $diff;
			} else {
				$size = $font_size_min;
			}
			*/
		}
		$this->tags = $tags_arr_unique;
	}
}
?>