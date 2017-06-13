<?php
namespace NCUCC\CoreAPI;

class APIException extends \Exception {
	public $status;
	public $message;
	public $code;

	public function __construct($data) {
		$this->code = $data->code;
		$this->status = $data->status;
		$this->message = $data->message;
	}
}
