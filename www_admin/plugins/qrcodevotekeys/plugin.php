<?php
/*
Plugin name: Print Votekeys with QR code
Description: Add ability to print votekeys with QR code
*/

defined('ADMIN_DIR') || exit();

function qrcodevotekeys_generate_html($votekey) {
	require_once(__DIR__.'/qrcode.php');

	global $settings;
	$register_url = $settings['qrcodevotekeys_register_url'] ?? 'http://party.lan/index.php?page=Login&votekey={%VOTEKEY%}';
	$register_url = str_replace('{%VOTEKEY%}', $votekey, $register_url);
	$pixelsize = ((int)($settings['qrcodevotekeys_pixelsize'] ?? 2)) ?? 2;
	$generate_type = $settings['qrcodevotekeys_generate_type'] ?? 'image';


	if ($generate_type == 'table') {
		$qr = QRCode::getMinimumQRCode($register_url, QR_ERROR_CORRECT_LEVEL_L);

		$html = '<table>';
		for ($r = 0; $r < $qr->getModuleCount(); $r++) {
			$html .= '<tr>';
			for ($c = 0; $c < $qr->getModuleCount(); $c++) {
				$isdark = $qr->isDark($r, $c);
				if ($isdark) {
					$html .= '<td class="dark"></td>';
				} else {
					$html .= '<td></td>';
				}
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
	} else if ($generate_type == 'inlineimage') {
		$qr = QRCode::getMinimumQRCode($register_url, QR_ERROR_CORRECT_LEVEL_L);

		$im = $qr->createImage($pixelsize, $pixelsize);

		ob_start();
		imagegif($im);
		$imagedata = ob_get_contents();
		imagedestroy($im);
		ob_end_clean();

		$html .= '<img src="data:image/gif;base64,'.base64_encode($imagedata).'">';
	} else {
		$html .= '<img src="./plugins/qrcodevotekeys/image.php?url='.urlencode($register_url).'&size='.$pixelsize.'">';
	}

	return $html;
}

add_hook('admin_menu', function($data) {
	$data['links']['pluginoptions.php?plugin=qrcodevotekeys'] = 'QR code Votekeys';

});

add_hook('votekeys_print_css', function() {
	global $settings;
	$pixelsize = ((int)($settings['qrcodevotekeys_pixelsize'] ?? 2)) ?? 2;
?>
	.qrcode {
		display: block;
		padding-bottom: 10px;
	}
	.qrcode table, img {
		display: inline-block;
	}
	.qrcode table, .qrcode tr, .qrcode td {
		border-style: none;
		border-collapse: collapse;
		margin: 0;
		padding: 0;
	}
	.qrcode td {
		width: <?php echo $pixelsize; ?>px;
		height: <?php echo $pixelsize; ?>px;
	}
	.qrcode td.dark {
		background-color: #000;
	}
<?php
});

add_hook('votekeys_print_votekey_before', function($args) {
	if (empty($args['votekey'])) { return; }
?>
	<span class="qrcode">
		<?php echo qrcodevotekeys_generate_html($args['votekey']); ?>
	</span>
<?php
});
