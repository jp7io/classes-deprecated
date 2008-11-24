<?
class Social {
	/* Bookmarking */
	public $displaySocials = array ();

	private $mainSocials = array (
		'delicious' => 'Delicious',
		'myweb' => 'Yahoo MyWeb',
		'google' => 'Google Bookmarks',
		'stumbleupon' => 'StumbleUpon',
		'digg' => 'Digg',
		'live' => 'Live',
		'reddit' => 'Reddit'
	);
	private $extendedSocials = array (
		'twitter' => 'Twitter',
		'linkedin' => 'LinkedIn',
		'facebook' => 'Facebook',
		'myspace' => 'MySpace',
		'slashdot' => 'Slashdot',
		'ask' => 'Ask',
		'blinklist' => 'Blinklist',
		'multiply' => 'Multiply',
		'technorati' => 'Technorati',
		'yahoobkm' => 'Yahoo Bookmarks'
	);
	private $otherSocials = array (
		'propeller' => 'Propeller',
		'backflip' => 'Backflip',
		'kaboodle' => 'Kaboodle',
		'linkagogo' => 'Link-a-Gogo',
		'segnalo' => 'Segnalo',
		'blogmarks' => 'Blogmarks',
		'magnolia' => 'Magnolia',
		'spurl' => 'Spurl',
		'diigo' => 'Diigo',
		'misterwong' => 'Mister Wong',
		'mixx' => 'Mixx',
		'tailrank' => 'Tailrank',
		'fark' => 'Fark',
		'bluedot' => 'Faves (Bluedot)',
		'aolfav' => 'myAOL',
		'favorites' => 'Favorites',
		'feedmelinks' => 'FeedMeLinks',
		'netvouz' => 'Netvouz',
		'furl' => 'Furl',
		'newsvine' => 'Newsvine',
		'yardbarker' => 'Yardbarker'
	);
	private $allSocials = array ();

	private function createSocial ($theme) {
		// Merge all socials
		$this->allSocials = array_merge($this->mainSocials, $this->extendedSocials);
		$this->allSocials = array_merge($this->allSocials, $this->otherSocials);

		switch ($theme) {
			case 'main':
				$this->displaySocials = $this->mainSocials;
				break;
			case 'extended':
				$this->displaySocials = array_merge($this->mainSocials, $this->extendedSocials);
				break;
			case 'all':
				$this->displaySocials = $this->allSocials;
				break;
			default:
				$this->displaySocials = $this->mainSocials;
				break;
		}
	}
	private function addSocials ($add) {
		// Add socials
		foreach ($add as $item) {
			if (array_key_exists($item, $this->allSocials)) {
				$this->displaySocials[$item] = $this->allSocials[$item];
			} else {
				exit ('This social does not exist.');
			}
		}
	}
	private function removeSocials ($remove) {
		// Remove socials
		foreach ($remove as $item) {
			if (array_key_exists($item, $this->allSocials)) {
				unset($this->displaySocials[$item]);
			} else {
				exit ('This social does not exist.');
			}
		}
	}

	public function newSocialBookmark ($theme = 'main', $add = array(), $remove = array()) {
		// Create a social
		Social::createSocial ($theme);

		// Add socials if passed
		if ($add) {
			Social::addSocials ($add);
		}

		// Remove socials if passed
		if ($remove) {
			Social::removeSocials ($remove);
		}
	}
	public function displayBookmark ($limiter = 5, $url = FALSE, $title = '', $target = '_blank') {
		// Must create a social first
		if (!$this->displaySocials) {
			exit('You must creat a social first.');
		}

		// Set the default configuration
		if (!$url) {
			$url = $_SERVER['HTTP_REFERER'];
		}

		$html = "\n";

		$html .= '<script type="text/javascript">' . "\n";
		$html .= 'function sets(val) {' . "\n";
		$html .= 'elt = document.getElementById(\'bookmarkingMys\');' . "\n";
		$html .= 'elt.value = val;' . "\n";
		$html .= 'elt = document.getElementById(\'bookmarkingWinname\');' . "\n";
		$html .= 'elt.value = window.name;' . "\n";
		$html .= 'elt = document.getElementById(\'bookmarkingForm\');' . "\n";
		$html .= 'elt.submit();' . "\n";
		$html .= '}' . "\n";
		$html .= '</script>' . "\n";

		$html .= '<form id="bookmarkingForm" action="http://www.addthis.com/bookmark.php" method="post" target="' . $target . '">' . "\n";
		$html .= '<input type="hidden" id="bookmarkingAte" name="ate" value="AT-internal/-/-/-/-/-" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingMys" name="s" value="" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingPub" name="pub" value="" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingUrl" name="url" value="' . $url . '" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingTitle" name="title" value="' . $title . '" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingLng" name="lng" value="" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingWinname" name="winname" value="" />' . "\n";
		$html .= '<input type="hidden" id="bookmarkingContent" name="content" value="" />' . "\n";
		$html .= '</form>' . "\n";

		$html .= '<ul>' . "\n";
		$count = 1;
		foreach($this->displaySocials as $key=>$value) {
			$html .= '	<li><a href="javascript:sets(\'' . $key . '\');"><span class="at15t at15t_' . $key . '">' . $value . '</span></a></li>' . "\n";
				
			if($count == $limiter) {
				$html .= '</ul>' . "\n" . '<ul>' . "\n";
				$count = 0;
			}
			$count++;
		}
		$html .= '</ul>' . "\n";

		return $html;
	}
	/* Bookmarking */

	/* Send to a friend */
	public function newSendFriend ($url = FALSE, $title = '', $action = '../indique/enviar.php', $target = '_parent', $template = array (
		'form' => 'global',
		'mail' => 'global',
		'success' => 'global',
		'fail' => 'global'
	),
	$messages = array (
		'legend' => 'Envie para um amigo',
		'yourName' => 'Seu nome:',
		'yourMail' => 'Seu e-mail:',
		'friendMail' => 'E-mail do seu amigo:',
		'friendMailLabel' => '(separe os e-mails com vírgula)',
		'friendComment' => 'Comentário:',
		'send' => 'Enviar'
	)) {
		#global $c_site, $c_url, $c_cliente_title;
		// Set the default configuration
		if (!$url) {
			$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		if ($template['form'] == 'global' or $template == false or $template['form'] == false) {
			$template['form'] = jp7_path_find('../../_default/site/_templates/sendFriend/form.htm');
		} else {
			$template['form'] = jp7_path_find('../site/_templates/' . $template['form']);
		}

		$html = file_get_contents($template['form']);
		$html = str_replace('%action%', $action, $html);
		$html = str_replace('%target%', $target, $html);
		$html = str_replace('%template%', $template['mail'], $html);
		$html = str_replace('%success%', $template['success'], $html);
		$html = str_replace('%fail%', $template['fail'], $html);
		$html = str_replace('%url%', $url, $html);
		$html = str_replace('%title%', $title, $html);
		$html = str_replace('%legend%', $messages['legend'], $html);
		$html = str_replace('%yourName%', $messages['yourName'], $html);
		$html = str_replace('%yourMail%', $messages['yourMail'], $html);
		$html = str_replace('%friendMail%', $messages['friendMail'], $html);
		$html = str_replace('%friendMailLabel%', $messages['friendMailLabel'], $html);
		$html = str_replace('%friendComment%', $messages['friendComment'], $html);
		$html = str_replace('%send%', $messages['send'], $html);

		return $html;
	}
	/* Send to a friend */
}
