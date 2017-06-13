<?php
namespace NCUCC\CoreAPI;

APIEntry::static_init();

class APIEntry {
	public $path;
	public $name;
	public $method = 'get';
	public $data;
	public $requireToken = false;
	public $requireAuth = false;

	public static $funcMap = null;

	public static function static_init() {
		if (self::$funcMap != null) return;

		self::$funcMap = [
			"nop" => function($ctrl, $var) { return $var; },
			"base64" => function($ctrl, $var) { return base64_encode ($var); },
			"encrypt_v1" => function($ctrl, $var) { return $ctrl->encrypt_v1($var); }
		];
	}

	public function __construct($ctrl, $name, $args, $parent = null) {
		$this->name = $name;

		if ($parent != null) {
			$p = property_exists($args, 'path') ? $args->path : "";

			if  (! empty($parent->path) and ! empty($p)) {
				$this->path = $parent->path . '/' . $args->path;
			} else if (empty ($parent->path)) {
				$this->path = $p;
			} else {
				$this->path = $parent->path;
			}

			$this->requireToken = $parent->requireToken;
			$this->requireAuth= $parent->requireAuth;
		} else {
			$this->path = $args->path;
		}

		if (property_exists($args, 'requireToken')) {
			$this->requireToken = $args->requireToken;
		}
		if (property_exists($args, 'requireAuth')) {
			$this->requireAuth= $args->requireAuth;
		}

		if (property_exists($args, 'apis')) {
			foreach ($args->apis as $k => $v) {
				# print_r ($v);
				$entry = new APIEntry($ctrl, $k, $v, $this);
			}
		} else {
			if (property_exists($args, 'method')) {
				$this->method = $args->method;
			}
			$this->data = property_exists($args, 'data') ? $args->data : null;
			$ctrl->regist($name, $this);
		}
	}

	public function generateDATA(BaseAPI $ctrl, array $args) {
		if ($this->data == null) {
			return null;
		} else {
			$data = [];
			foreach ($this->data as $k => $v) {
				if (preg_match('/^(.*):(.*)$/', $k, $m)) {
					$k = $m[1];
					$opts = explode(',', $m[2]);
				} else {
					$opts = [];
				}

				$i = 0;
				$fcname = "nop";

				if (preg_match('/^\$(\d+)$/', $v, $m)) {
					$i = $m[1];
				} else if (preg_match('/^([a-z][_a-z0-9]+)\(\$(\d+)\)$/', $v, $m)) {
					$fcname = $m[1];
					$i = $m[2];
				} else if (preg_match('/^@([a-z]+)$/', $v, $m)) {
					$data[$k] = $ctrl->get_var_by_name($m[1]);
				}

				if ($i > 0) {
					if ($i > 0 and $i <= count($args)) {
						$func = self::$funcMap[$fcname];
						$data[$k] = $func($ctrl, $args[$i - 1]);
					} else {
						throw new \Exception("arguments not exists");
					}
				}
			}
			return $data;
		}
	}

	private function replace_vars(BaseAPI $ctrl, $path) {
		$pattern = $path;
		$url = '';
		while (preg_match('/^(.*)\@([a-z][_a-z]+)(.*)$/', $pattern, $m) == 1) {
			$varval = $ctrl->get_var_by_name($m[2]);
			$url = $varval . $m[3] . $url;
			$pattern = $m[1];
		}
		return $pattern . $url;
	}

	private function replace_args($path, array $args) {
		$pattern = $path;
		$url = '';
		while (preg_match('/^(.*)\$(\d+)(.*)$/', $pattern, $m) == 1) {
			if ($m[2] > count($args)) {
				throw new \Exception("not enought arguments");
			}
			$url = $args[$m[2] - 1] . $m[3] . $url;
			$pattern = $m[1];
		}
		return $pattern . $url;
	}

	public function generateURL(BaseAPI $ctrl, array $args) {
		$path = $this->replace_vars($ctrl, $this->path);

		return $this->replace_args($path, $args);
	}
}
