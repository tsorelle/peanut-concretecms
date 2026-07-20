<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/9/2019
 * Time: 4:23 AM
 */

namespace Tops\mail;
use Mailgun\Exception;
use Mailgun\Mailgun;


class TMailgunEmailValidator implements IEmailValidator
{
    public function __construct()
    {
        $settings = TMailgunConfiguration::GetSettings();
        $enabled = false;
        if (empty($settings->error)) {
            $enabled = $settings->validationsEnabled;
        }
        else {
            $this->errorMessage = $settings->error;
        }
        if ($enabled) {
            $this->domain = $settings->domain;
            $this->enabled = true;
            $this->client = new Mailgun($settings->validationKey);
        }
    }

    /**
     * @var Mailgun
     */
    private $client = null;
    public $enabled = false;
    public $errorMessage = null;
    public $domain = null;

    public static $ValidationLog = [];
    public static function getValidationLog() {
        return self::$ValidationLog;
    }
    private function logValidationResult($email,$response) {
        $entry = new \stdClass();
        $entry->domain = $this->domain;
        $entry->time = date(DATE_ISO8601);
        $entry->email = htmlspecialchars($email);
        $entry->valid = !empty($response->http_response_body->is_valid);
        $entry->response = $response;
        self::$ValidationLog[] = $entry;
    }

    /**
     * @param $emailAddress
     * @return true | \stdClass
     *
     * stdClass is error information.  May include:
     *      error - translateable string
     *      enabled - true/false   is validation service working at all
     *      suggestion = closest suggestion
     */
    public function validate($emailAddress)
    {
        $errorResult = new \stdClass();
        $errorResult->enabled = false;
        if (!$this->enabled) {
            return $errorResult;
        }
        try {
            $result = $this->client->get("address/validate", array('address' => $emailAddress));
            $this->logValidationResult($emailAddress,$result);
            if(!empty($result->http_response_body->is_valid)) {
                return true;
            }
            if (!empty($result->did_you_mean)) {
                $errorResult->suggestion = $result->did_you_mean;
            }

            $errorResult->enabled = true;
            return $errorResult;
        }
        catch (\Exception $ex) {
            $errorResult->error = 'Exception: '.$ex->getMessage();
        }
        return $errorResult;
    }
}