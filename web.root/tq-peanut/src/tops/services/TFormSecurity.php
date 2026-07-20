<?php

namespace Tops\services;

use Peanut\contacts\db\model\repository\ContactsRepository;
use Peanut\QnutDirectory\db\model\PersonsRepository;
use Tops\db\IBasicContact;
use Tops\db\IContactsRepository;
use Tops\db\IProfilesRepository;
use Tops\sys\TObjectContainer;
use Tops\sys\TUser;

class TFormSecurity
{
    private static $instance;

    private static function GetInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function checkSpamPhrases($text)
    {
        $spamPhrases =
            ['#1',
                '100% more',
                '100% free',
                '100% satisfied',
                'additional income',
                'be your own boss',
                'best price',
                'big bucks',
                'cash bonus',
                'cents on the dollar',
                'consolidate debt',
                'double your cash',
                'double your income',
                'earn extra cash',
                'earn money',
                'eliminate bad credit',
                'extra cash',
                'extra income',
                'expect to earn',
                'fast cash',
                'financial freedom',
                'free access',
                'free consultation',
                'free gift',
                'free hosting',
                'free info',
                'free investment',
                'free membership',
                'free money',
                'free preview',
                'free quote',
                'free trial',
                'full refund',
                'get out of debt',
                'get paid',
                'increase sales',
                'increase traffic',
                'incredible deal',
                'lower rates',
                'lowest price',
                'make money',
                'million dollars',
                'money back',
                'once in a lifetime',
                'pennies a day',
                'potential earnings',
                'pure profit',
                'risk-free',
                'satisfaction guaranteed',
                'save big money',
                'save up to',
                'special promotion',
                'apply now',
                'call now',
                'click below',
                'click here',
                'get it now',
                'do it today',
                'don’t delete',
                'exclusive deal',
                'get started now',
                'important information regarding',
                'information you requested',
                'instant',
                'limited time',
                'new customers only',
                'order now',
                'sign up free',
                'this won’t last',
                'what are you waiting for?',
                'while supplies last',
                'will not believe your eyes',
                'you are a winner',
                'you have been selected',
                'bulk email',
                'buy direct',
                'cancel at any time',
                'check or money order',
                'direct email',
                'direct marketing',
                'hidden charges',
                'human growth hormone',
                'internet marketing',
                'lose weight',
                'mass email',
                'meet singles',
                'multi-level marketing',
                'no catch',
                'no cost',
                'no credit check',
                'no fees',
                'no gimmick',
                'no hidden costs',
                'no hidden fees',
                'no interest',
                'no investment',
                'no obligation',
                'no purchase necessary',
                'no questions asked',
                'no strings attached',
                'not junk',
                'not spam',
                'requires initial investment',
                'social security number',
                'this isn’t a scam',
                'this isn’t junk',
                'this isn’t spam',
                'undisclosed',
                'unsecured credit',
                'unsecured debt',
                'unsolicited',
                'valium',
                'viagra',
                'vicodin',
                'we hate spam',
                'weight loss',
                'xanax',
                'accept credit cards',
                'all new',
                'as seen on',
                'bonus',
                'cards accepted',
                'compare rates',
                'credit card offers',
                'in accordance with laws',
                'join millions',
                'loans',
                'marketing solution',
                'message contains',
                'mortgage rates',
                'name brand',
                'online marketing',
                'pre-approved',
                'rates',
                'refinance',
                'reserves the right',
                'search engine',
                'sent in compliance',
                'subject to',
                'terms and conditions',
                'warranty',
                'web traffic',
                'work from home',
                'fuck',
                'shit',
                'bastard',
                'bastards',
                'bitch',
                'bitches',
                'ass hole',
                'ass holes',
                'asshole',
                'assholes',
                'shutup',
                'shut up',
                'warn you',
                'warning you',
                'screw yourself',
                'screw yourselves'
            ];
        $score = 0;
        // $startTime = microtime(true);
        foreach ($spamPhrases as $phrase) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/', $text)) {
                $score++;
            }
        }
        // $endTime = microtime(true);
        // $duration = ($endTime - $startTime) * 1000;
        return $score;
    }

    const FIND_URL_PATTERN = '/\b(?:https?:\/\/)?(?:www\.)?[a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s?#]*)?(?:\?[^\s#]*)?(?:#[^\s]*)?\b/i';
    const EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    const EMAIL_DOT_TOKEN = '[[DOT_TOKEN]]';

    // for testing
    public static function getUrlCount($text): int
    {
        return self::GetInstance()->countUrls($text);
    }

    private function countUrls(string $text): int
    {
        $this->hideEmails($text);
        preg_match_all(self::FIND_URL_PATTERN, $text, $matches);
        $count = count($matches[0]);
        return $count;
    }


    private function hideEmails(&$body): bool
    {
        // Find all matching email addresses
        preg_match_all(self::EMAIL_PATTERN, $body, $matches);
        $found = false;
        if (!empty($matches[0])) {
            foreach ($matches[0] as $email) {
                $newemail = str_replace('.', self::EMAIL_DOT_TOKEN, $email);
                $body = str_replace($email, $newemail, $body);
                $found = true;
            }
        }
        return $found;
    }


    public static function disableUrls(&$body): int
    {
        return self::GetInstance()->fixUrls($body);
    }

    /**
     * Disable URLs in email text by removing protocol (http?://) and prefix 'www.'
     * @param $body
     * @return array|string|string[]
     */
    private function fixUrls(&$body): int
    {
        $hasEmails = $this->hideEmails($body);
        $text = strtolower($body);
        $found = 0;
        while (preg_match(self::FIND_URL_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE)) {
            $url = $matches[0][0]; // The matched URL
            $newurl = str_ireplace(['https://', 'http://'], '', $url);
            $newurl = str_ireplace('.', '(dot)', $newurl);
            $newurl = sprintf('[url:%s]', $newurl);
            $position = $matches[0][1]; // Position of the URL in the string
            $body = \Tops\sys\TStrings::Insert($newurl, $body, $position, strlen($url));
            $text = strtolower($body);
            $found++;
        }
        if ($hasEmails) {
            $body = str_replace(self::EMAIL_DOT_TOKEN, '.', $body);
        }
        return $found;
    }



    /**
     * @param $message
     *  export interface IMailMessage {
     *       mailboxCode: string;
     *      fromName : string;
     *      fromAddress : string;
     *      subject : string;
     *      body : string;
     * }
     * @return \stdClass
     * @throws \Exception
     */
    public static function checkForProblems($message): \stdClass
    {
        if (!$message instanceof \stdClass) {
            throw new \Exception('Message is not an object');
        }

        $instance = self::GetInstance();
        $problems = [];
        if (isset($message->fromName)) {
            $scanResult = $instance->scanText($message->fromName, 'name');
            if (!empty($scanResult->problems)) {
                $message->error = 'Cannot send message.'; // fatal
                return $message;
            }
        }

        if (isset($message->subject)) {
            $scanResult = $instance->scanText($message->subject, 'subject');
            if (!empty($scanResult->problems)) {
                $message->error = 'Cannot send message.'; // fatal
                return $message;
            }
        }
        if (isset($message->body)) {
            $scanResult = $instance->scanText($message->body, 'body');
            if (!empty($scanResult->problems)) {
                if (empty($scanResult->fixed)) {
                    $message->error = 'Cannot send message.'; // fatal
                    return $message;
                }
                $problems = $scanResult->problems;
                $message->body = $scanResult->fixed;
            }
        }

        $message->footer = $instance->getSecurityMessage($message,$problems);
        return $message;
    }

    public static function detectSuspiciousText($content, $fieldName = 'body'): \stdClass
    {
        $result = new \stdClass();
        return self::GetInstance()->scanText($content, $fieldName = 'body',$result);
    }

    private function scanText($content, $fieldName = 'body'): \stdClass
    {
        $result = new \stdClass();
        $text = strtolower($content);
        $result->score = 0;
        $result->problems = array();
        $authenticated = TUser::getCurrent()->isAuthenticated();

        $stripped = strip_tags($text);
        if ($stripped !== $text) {
            $result->score += 10;
            $result->problems[] = 'markup';
            if ($fieldName == 'body') {
                $stripped = trim($stripped);
                $result->fixed = $stripped;
                $text = strtolower($stripped);
            } else {
                $result->fixed = false;
            }
        }

        $linkcount = TFormSecurity::getUrlCount($content);
        $allowed = ($authenticated && $fieldName == 'body') ? 10 : 0;

        if ($linkcount > $allowed) {
            $result->score = $linkcount - $allowed;
            $result->problems[] = 'urls';
            if ($fieldName == 'body' && $linkcount < 10) {
                $this->fixUrls($content);
                $result->fixed = trim($content);
                $text = strtolower($content);
            } else {
                $result->fixed = false;
            }
        }

        if (!$authenticated) {
            $badwords = self::checkSpamPhrases($text);
            if ($badwords > 0) {
                $result->score += $badwords;
                $result->problems[] = 'spam-phrases';
                $result->fixed = false; // no fix for this!
            }
        }

        return $result;
    }

    public static function FormatSecurityMessage($securityResult, $email): string
    {
        return self::GetInstance()->getSecurityMessage($securityResult, $email);
    }

    public static function GetSecurityFooter($message)
    {
        return self::GetInstance()->getSecurityMessage($message);
    }

    private function getSecurityMessage($message, $problems=null): string
    {
        $authenticated = TUser::getCurrent()->isAuthenticated();
        $securityMessage = sprintf('Sender is an <strong>%s</strong> user<br>',
            $authenticated ? 'authenticated' : 'ANONYMOUS');

        /**
         * @var $repository IContactsRepository
         */
        $repository = TObjectContainer::Get('contacts.repository');

        /**
         * @var $persons IBasicContact[]
         */
        $persons = $repository->getAllByEmail($message->fromAddress);

        $registered = '';
        if (!empty($persons)) {
            $personName = $persons[0]->getFullName() ?? '';
            foreach ($persons as $person) {
                $accountId = $person->getAccountId();
                if (!empty($accountId)) {
                    $personName = $person->fullname;
                    $registered = '(registered user)';
                    break;
                }
            }
            $securityMessage .= sprintf("Sender email listed in directory for <strong>%s</strong> %s<br>",
                $personName,$registered);
        } else {
            $securityMessage .= "Sender email not listed in directory<br>";
        }
        if (!empty($problems)) {
            foreach ($problems as $problem) {
                switch ($problem) {
                    case 'markup':
                        $securityMessage .= "Message body contained html markup, which was removed<br>";
                        break;
                    case 'urls':
                        if ($authenticated) {
                            $securityMessage .= "Message body contains a large number of URLs<br>";
                        } else {
                            $securityMessage .= "URLs in the message body were disabled<br>" .
                                "To enable them, replace 'url:' with 'https://', '(dot)' with '.' and remove the brackets ('[..]')<br>".
                                "[url:example(dot)com] = https://example.com/<br>";
                        }
                }
            }
        }
        return $securityMessage;
    }

}