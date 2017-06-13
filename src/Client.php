<?php
namespace NCUCC\CoreAPI;

class Client extends BaseAPI {
	const APIVersion = 'v1';
	const JSONfile = 'coreapi-v1.json';

	public function __construct($config) {
		//$m = require __DIR__.'/methods.php';
		if (property_exists($config, 'apiMetaFile') and file_exists($config->apiMetaFile)) {
			$jsonfile = $config->apiMetaFile;
		} else {
			$jsonfile = $config->base.'/resources/'.self::JSONfile;
		}
		$m = json_decode(file_get_contents($jsonfile));

		if ($m == null) {
			throw new \Exception("$jsonfile : api metafile error");
		}

		$baseurl = $config->base . '/'. self::APIVersion;
		parent::__construct($m, $baseurl, $config->token, $config->magickey,
			$config->appkey, $config->secret, $config->publicKey);
	}
}
