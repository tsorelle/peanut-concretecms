<?php
/**
 * Mailgun is an excellent and reasonably priced email service provider: https://www.mailgun.com/
 * The Mailgun API is fully supported in Peanut.
 *
 * To use, place this entry in application/config/classes.ini
 *
 * [tops.mailer]
 * type='Tops\mail\TMailgunMailer'
 *
 * Store your Mailgun configuration in application/config/mailgun.ini
 * Be careful not to store this file in any publically accessible area such as a GitHub public repository.
 * Doing so will expose security codes and Mailgun will Be most displeased.
 *
 * We recommend you use this class in production and a different mailer for development and testing
 * to avoid unexpected charges and other problems.  Use TPhpMailer, TDevMailer or TNullMailer.  See classes.ini
 *
 * Example mailgun.ini configuration.  Obtain your keys and passwords from Mailgun
 *
 * [settings]
 * domain=austinquakers.org
 * apikey='[your key here]'
 * validationkey='[your key here]'
 * smtp-user='postmaster@austinquakers.org'
 * smtp-pwd='[your password here]'
 *
 * [options]
 * ; option defaults = all supported on/off options must be listed.
 * dkim=0
 * testmode=0
 * tracking=0
 * tracking-clicks=0
 * tracking-opens=0
 * require-tls=0
 * skip-verification=0
 *
 */

namespace Tops\mail;
use Mailgun\Mailgun;
use Tops\sys\TConfiguration;
use Tops\sys\TIniSettings;
use Tops\sys\TPath;
use Tops\sys\TWebSite;


class TMailgunMailer implements IMailer
{
    public static $SendLog = [];
    public static function getSendLog() {
        return self::$SendLog;
    }
    private function logSendResult($domain,$parameters,$result) {
        $entry = new \stdClass();
        $entry->domain = $domain;
        $entry->time = date(DATE_ISO8601);
        $entry->result = $result;

        if (array_key_exists('to',$parameters)) {
            $parameters['to'] = htmlspecialchars($parameters['to']);
        }
        if (array_key_exists('from',$parameters)) {
            $parameters['from'] = htmlspecialchars($parameters['from']);
        }
        $entry->parameters = $parameters;
        self::$SendLog[] = $entry;

        // debug
/*
        $to = @$parameters['to'] ?? 'No to address';
        if (is_string($result)) {
            mail('terry.sorelle@outlook.com','Message queue test -mailgun message log.',"$to:\n".$result);
        }
        else {
            mail('terry.sorelle@outlook.com','Message queue test -mailgun message log.',"$to:\n".(print_r($result,true)));
        }*/

    }

    public static $sendEnabled = true;
    private $settingsError;
    private $sendOptions;

    private function getSettings(TEMailMessage $message)
    {

        $mailgunSettings = TMailgunConfiguration::GetSettings();
        if (!$mailgunSettings->valid) {
            $this->settingsError = $mailgunSettings->error;
            return false;
        }
        if (empty($mailgunSettings->apikey)) {
            $this->settingsError = 'No apikey in mailgun settings';
            return false;
        }

        $this->sendOptions = [];
        $options = $mailgunSettings->options;
        $messageOptions = $message->getOptions();
        if ($options) {
            foreach ($options as $option => $value) {
                if (array_key_exists($option, $messageOptions)) {
                    $value = $messageOptions[$option];
                }
                if (!empty($value)) {
                    $this->sendOptions['o:' . $option] = true;
                }
            }
        }
        if ($mailgunSettings->sendEnabled !== true) {
            $this->setSendEnabled($mailgunSettings->sendEnabled);
        }
        return $mailgunSettings;
    }

    /**
     * @param TEMailMessage $message
     * @return bool | string
     *
     * Return true if successfull for error message e.g.
     * $result = $mailer->send($message);
     * if ($result !== true) {
     *      logError($result);
     * }
     */
    public function send(TEMailMessage $message)
    {
        if (!class_exists('Mailgun\Mailgun')) {
            return 'Mailgun API is not installed.';
        }
        $settings = $this->getSettings($message);
        if ($settings === false) {
            return $this->settingsError;
        }
        try {
            $mg = Mailgun::create($settings->apikey); // For US servers
            if (empty($mg)) {
                exit ("Failed to create message");
            }
            $sendProperties = $message->getSendProperties();
            if ($sendProperties === false) {
                return $message->getLastValidationError();
            }
           $parameters = [
                'from' => $sendProperties->from,
                'to' => $sendProperties->to,
                'subject' => $sendProperties->subject
            ];

            if (!empty($sendProperties->text)) {
                $parameters['text'] = $sendProperties->text;
            }
            if (!empty($sendProperties->html)) {
                $parameters['html'] = $sendProperties->html;
            }
            if (!empty($sendProperties->cc)) {
                $parameters['cc'] = explode(',', $sendProperties->cc);
            }
            if (!empty($sendProperties->bcc)) {
                $parameters['bcc'] = explode(',',$sendProperties->bcc);
            }

            if (!empty($sendProperties->attachments)) {
                //trigger_error("Experimental: Add attachments feature for this mailer has not been tested. ", E_USER_WARNING);
                $parameters['attachments'] = [];
                foreach ($sendProperties->attachments as $attachment) {
                    $parameters['attachments'][] = [
                        'filePath' => dirname($attachment),
                        'filename' => basename($attachment)
                    ];
                }
            }

            if (!empty($this->sendOptions)) {
                $parameters = array_merge($parameters, $this->sendOptions);
            }

            $tags = $message->getTags();
            if ($tags) {
                $parameters['o:tag'] = $tags;
            }
            $delivery = $message->getDeliveryTime();
            if ($delivery) {
                $parameters['o:deliverytime'] =  $delivery->format(\DateTime::RFC2822);
            }

            foreach ($message->getHeaders() as $key => $value) {
                $parameters["h:$key"] = $value;
            }

/*
            $dump = print_r($parameters,true);
            mail('terry.sorelle@outlook.com','Debug mail queue parameters',
                $dump);
*/
            /*

                        $dump = print_r($message,true);
                        mail('terry.sorelle@outlook.com','Debug mail queue message',
                            $dump);
            */

            /*
                        echo '<pre>';
                        echo "\n******\nsettings\n************\n";
                        var_dump($settings);
                        echo "\n******\nparameters\n************\n";
                        var_dump($parameters);
                        echo "\n***********************\n";
                        exit('end mail test');
            */
            $messages = $mg->messages();
            if (self::$sendEnabled && $settings->sendEnabled) {
                $result = $messages->send($settings->domain, $parameters);
                $this->logSendResult($settings->domain,$parameters,$result);
/*
                $dump = print_r($result,true);
                $params = print_r($parameters,true);
                mail('terry.sorelle@outlook.com','Message sent',"Message was sent:\n$dump\n\n$params");
*/
            }
            else {
                $this->logSendResult($settings->domain,$parameters,'send disabled');
                // mail('terry.sorelle@outlook.com','Debug mail queue message','sending was disabled. 3');
            }
        } catch (\Exception $ex) {
            // mail('terry.sorelle@outlook.com',$ex->getMessage(), $ex->getTraceAsString());
            $this->logSendResult($settings->domain,$parameters,'Exception: '.$ex->getMessage());
            return "Mailgun send failed: " . $ex->getMessage();
        }
        return true;
    }

    public function setSendEnabled($value = true)
    {
        self::$sendEnabled = $value;
    }
}