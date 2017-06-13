<?php
namespace NCUCC\CoreAPI;

class BaseAPI {
	public static $whitelist = [ 'appkey', 'magickey' ];
	protected	$baseURL;
	protected	$client;
	protected	$bearerToken;
	private		$apiMethods;
	protected	$magickey;
	protected	$appkey;
	protected	$secret;
	protected	$publicKey;

	protected function __construct($m, $base, $token, $magickey, $appkey, $secret, $publicKey) {
		$this->baseURL = $base;
		$this->bearerToken = $token;
		$this->magickey = $magickey;
		$this->appkey = $appkey;
		$this->secret = $secret;
		$this->publicKey = $publicKey;
		$this->client = new \GuzzleHttp\Client();

		$this->apiMethods = [];

		foreach ($m->apis as $f => $item) {
			new APIEntry($this, $f, (object) $item);
		}
	}

	public function get_var_by_name($name) {
		if (in_array ($name, self::$whitelist)) {
			foreach (get_object_vars($this) as $vn => $vv) {
				if ($vn == $name) {
					return $vv;
				}
			}
		}
		return "";
	}

	public function encrypt_v1($var) {
		$pstring = gmdate("YmdHis") . ':' . $var;

		if (openssl_public_encrypt($pstring, $output, $this->publicKey)) {
			return base64_encode ($output);
		} else {
			throw new \Exception("Faile to encrypt with public key");
		}
	}

	public function regist($name, $item) {
		$this->apiMethods[$name] = $item;
	}

	protected function doApiRequest(APIEntry $entry, array $args) {
		$url = $entry->generateURL($this, $args);
		$data = $entry->generateDATA($this, $args);

		$urlpath = $this->baseURL . ($url[0] == '/' ? '' : '/') . $url;

		$opts = [];
		$header = [
			'Accept' => 'application/json',
		];

		if ($entry->requireToken) {
			$header['Authorization'] = 'Bearer ' . $this->bearerToken;
		}

		$opts = [ 'headers' => $header ];

		if ($entry->requireAuth) {
			$opts['auth'] = [ $this->appkey, $this->secret ];
		}

		if ($data != null) {
			$opts['body'] = json_encode($data);
			# print_r ($opts);
		}

		$method = strtoupper($entry->method);

		# printf("URL: [%s] %s\n", $method, $urlpath);

		$res = $this->client->request($method, $urlpath, $opts);

		if (($code = $res->getStatusCode()) == 200) {
			$data = json_decode($res->getBody());
			if ($data->code == 20000) {
				return $data->result;
			} else {
				print_r ($data);
				throw new APIException($data);
			}
		} else {
			throw new \Exception("http status code = " . $code);
		}
	}

	public function __call($name, array $args) {
		if (array_key_exists ($name, $this->apiMethods)) {
			$entry = $this->apiMethods[$name];
			return $this->doApiRequest($entry, $args);
		} else {
			throw new \Exception("method $name not found");
		}
	}
}
