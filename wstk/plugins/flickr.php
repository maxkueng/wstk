<?php

class FlickrPlugin extends WeAbstractPlugin {

	private $app;
	private $flickr;

	public static function info () {
		return array(
			'name'          => 'Flickr', 
			'description'   => 'Flickr', 
			'version'       => '0.1', 
			'dependencies'  => array( 'liquid' )
		);
	}

	public function __construct ($app) {
		$this->app = $app;
		$conf = $app->config();
		require_once($conf['lib_dir'] .'/phpflickr/phpFlickr.php');
		$this->flickrInit();

		$app->hook('page.parser.tag.flickrphoto', $this, 'onFlickrPhotoTag');
		$app->hook('page.parser.tag.flickrset', $this, 'onFlickrSetTag');
	}

	private function flickrInit () {
		$conf = $this->app->config();
		$this->flickr = new phpFlickr($conf['flickr_api_key']);
		$this->flickr->cache = 'fs';
		$this->flickr->cache_dir = $conf['flickr_cache_dir'];
	}

	public function onFlickrPhotoTag ($hook, $parser, $params) {
		return;
	}

	public function onFlickrSetTag ($hook, $parser, $params) {
		if (!isset($params['set'])) return;
		if (!isset($params['template'])) return;

		$app = $this->app;
		$set = $params['set'];
		$name = $params['name'];
		$template = $params['template'];
		$show = (isset($params['show'])) ? $params['show'] : 'latest';
		$limit = $params['limit'];

		$set_info = $this->flickr->photosets_getInfo($set);
		$set_info['url'] = 'http://www.flickr.com/photos/'. $set_info['owner'] .'/sets/'. $set;

		if ($show == 'random') {
			$slice = $limit;
			$limit = null;
			$page = null;
		} else if ($show == 'oldest') {
			$page = 1;
		} else if ($show == 'latest') {
			$page = floor($set_info['photos'] / $limit);
		}

		$set_photos = $this->flickr->photosets_getPhotos($set, 'url_sq, url_t, url_s, url_m, url_o', 1, $limit, $page, 'photos');
		$photos = $set_photos['photoset']['photo'];
		foreach ($photos as &$photo) {
			$url = 'http://www.flickr.com/photos/'. $set_info['owner'] .'/'. $photo['id'];
			$photo['photopage_url'] = $url;
		}

		if ($show == 'random') {
			shuffle($photos);
			$photos = array_slice($photos, 0, $slice);
		}

		$tpl = $app->page($template);
		$tpl->data()->insert('page', array( 
			$name => array(
				'set' => $set_info, 
				'photos' => $photos
			) 
		));

		return $tpl->render();
	}

}

WeApplication::instance()->registerPlugin('title', 'FlickrPlugin');
