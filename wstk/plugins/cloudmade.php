<?php

class CloudMadePlugin extends WeAbstractPlugin {

	private $title = array();

	public static function info () {
		return array(
			'name'          => 'CloudMade', 
			'description'   => 'CloudMade Map', 
			'version'       => '0.1', 
			'dependencies'  => array( 'javascript', 'jquery' )
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$conf = $app->config();
		require_once($conf['lib_dir'] .'/geshi/geshi.php');
		$app->hook('page.parser.tag.map', $this, 'onMapTag');
	}

	public function onMapTag ($hook, $parser, $params) {
		if (!isset($params['kml'])) return '';

		$conf = $this->app->config();
		$id = (isset($params['id'])) ? $params['id'] : 'cloudmade-map';
		$name = (isset($params['id'])) ? $params['name'] : 'cloudmade';
		$kml = $params['kml'];
		$width = $params['width'];
		$height = $params['height'];

		if ($js = $this->app->pluginInstance('javascript')) {
			$js->addExternal('eod', 'http://tile.cloudmade.com/wml/latest/web-maps-lite.js');

			$code = '
$(document).ready( function () {
	var '. $name .' = new CM.Tiles.CloudMade.Web({key: \''. $conf['cloudmade_apikey'] .'\'});
	var map = new CM.Map(\''. $id .'\', '. $name .');
	var original_width = \''. $width .'\';
	var original_height = \''. $height .'\';

	var kml = new CM.GeoXml(\''. $kml .'\');
	CM.Event.addListener(kml, \'load\', function() {
		map.zoomToBounds(kml.getDefaultBounds());
		map.addOverlay(kml);
	}); 
	$(\'a.enlargemap.enlargemap-'. $id .'\').click(function (ev) {
		ev.preventDefault();
		var container = $(\'<div></div>\');
		$(\'body\').append(container);
		container.css({
			position: \'fixed\',
			top: 0, 
			left: 0, 
			height: \'100%\', 
			width: \'100%\',
			backgroundColor: \'#000\', 
			zIndex: 1000
		});

		var themap = $(\'#'. $id .'\');

		var fspanel = $(\'<div class="fspanel fapanel-'. $id .'"></div>\');
		fspanel.css({
			position: \'fixed\',
			top: 0, 
			left: 0, 
			width: \'100%\',
			height: \'18px\', 
			backgroundColor: \'#ccc\', 
			borderBottom: \'1px solid #000\', 
			zIndex: 1002
		});
		var closefs = $(\'<a href="">Leave fullscreen</a>\');
		closefs.click(function (ev) {
			ev.preventDefault();
			themap.detach();
			themap.prependTo($(\'#container-'. $id .'\'));
			themap.css({
				position: \'relative\',
				height: original_height, 
				width: original_width,
				zIndex: 1001
			});
			map.zoomToBounds(kml.getDefaultBounds());

			container.detach();
			container.remove();
		});

		fspanel.append(closefs);
		fspanel.appendTo(container);
		
		themap.detach();
		themap.appendTo(container);
		themap.css({
			position: \'fixed\',
			top: 0, 
			left: 0, 
			height: \'100%\', 
			width: \'100%\',
			backgroundColor: \'#000\', 
			zIndex: 1001
		});
		map.zoomToBounds(kml.getDefaultBounds());

	});
});

		';
			$js->addCode('eod', $code);
		}

		$out = '
<div id="container-'. $id .'" class="cloudmade-map-container">
	<div class="cloudmade-map" id="'. $id .'" style="width: '. $width .'; height: '. $height .';"></div>
	<!--p><a href="" class="enlargemap enlargemap-'. $id .'">Enlarge map</a></p-->
</div>';

		return $out;
	}

}

WeApplication::instance()->registerPlugin('title', 'CloudMadePlugin');
