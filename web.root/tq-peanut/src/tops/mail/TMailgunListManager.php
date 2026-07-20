<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/22/2019
 * Time: 7:32 AM
 */

namespace Tops\mail;


use Mailgun\Mailgun;

class TMailgunListManager implements IForwardingListManager
{
    /**
     * @var Mailgun
     */
    private $mailgunClient;
    /**
     * @var TMailgunConfiguration
     */
    private $settings;

    public function __construct()
    {
        $this->settings = TMailgunConfiguration::GetSettings();
        $this->mailgunClient = new Mailgun($this->settings->apikey);
    }

    /**
     * @param $address
     * @param null $description
     * @param string $accessLevel
     * @return \stdClass
     *
     * Response:
     *     {
     *         "message": "Mailing list has been created",
     *         "list": {
     *         "created_at": "Tue, 06 Mar 2012 05:44:45 GMT",
     *             "address": "dev@samples.mailgun.org",
     *             "members_count": 0,
     *             "description": "Mailgun developers list",
     *             "name": ""
     *         }
     *     }
     */
    public function addList($address, $name=null, $description = null, $accessLevel = 'everyone')
    {
        return false;
        if (!$description) {
            $description = explode('@', $address)[0];
        }
        # Issue the call to the client.
        $result = $this->mailgunClient->post("lists", array(
            'name' => $name,
            'address' => $address,
            'description' => $description,
            'access_level' => $accessLevel
        ));
        return $result;
    }

    /**
     * @param $listAddress
     * @param $memberAddress
     * @param null $memberName
     * @param array $extra
     * @return \stdClass
     *
     * Response:
     * {
     *     "member": {
     *     "vars": {
     *         "age": 26
     *     },
     *     "name": "Bob Bar",
     *         "subscribed": true,
     *         "address": "bar@example.com"
     *     },
     *     "message": "Mailing list member has been created"
     * }
     */
    public function addMember($listAddress, $memberAddress, $memberName = null, $extra = [])
    {
        return false;
        $args = $this->createMemberNameArray($memberAddress,$memberName);
        $args['subscribed'] = true;
        if (!empty($extra)) {
            $args = array_merge($args, $extra);
        }
        return $this->mailgunClient->post("lists/$listAddress/members", $args);
    }

    private function createMemberNameArray($memberAddress, $memberName = null) {
        if (!$memberName) {
            $address = TEmailAddress::FromString($memberAddress);
            $memberName = $address->getName();
            $memberAddress = $address->getAddress();
        }
        $args = [
            'address' => $memberAddress
        ];
        if ($memberName) {
            $args['name'] = $memberName;
        }
        return $args;
    }

    /**
     * @param int $limit
     * @return array
     *
     * Response:
     *
     * {
     *     "items": [
     *        {
     *            "access_level": "everyone",
     *            "address": "dev@samples.mailgun.org",
     *            "created_at": "Tue, 06 Mar 2012 05:44:45 GMT",
     *            "description": "Mailgun developers list",
     *            "members_count": 1,
     *            "name": ""
     *         },
     *         {
     *            "access_level": "readonly",
     *            "address": "bar@example.com",
     *            "created_at": "Wed, 06 Mar 2013 11:39:51 GMT",
     *            "description": "",
     *            "members_count": 2,
     *            "name": ""
     *        }
     *            ],
     *     paging": {
     *         first": "https://url_to_next_page",
     *         last": "https://url_to_last_page",
     *         next": "https://url_to_next_page",
     *         previous": "https://url_to_previous_page"
     *     }
     * }
     */
    public function getMailingLists($limit = 100)
    {
        $response = $this->mailgunClient->get("lists/pages", array(
            'limit' => $limit
        ));
        $items = @$response->http_response_body->items;
        $next = @$response->http_response_body->paging->next;
        // var_dump(@$response->http_response_body->paging);
        // print "\nNext = $next\n";
        if ($next) {
            @list($url,$params) = explode('?',$next);
            @list($page,$address) = explode('&',$params);
            @list($p,$address) = explode('=',$address);
            $address = str_replace('%40','@',$address);
            $response = $this->mailgunClient->get("lists/pages", array(
                'limit' => $limit,
                'page' => 'next',
                'address' => $address

            ));
            $more = @$response->http_response_body->items;
            if ($more) {
                // var_dump($more);
                $items = array_merge($items, $more);
            }
            else {
                // print "No more\n";
                return $items ?? [];
            }
        }
        return $items;
    }


    public function updateMember($listAddress,$memberAddress,$name=null,$subscribed=true) {
        return false;
        $arg = http_build_query(array(
            'subscribed' => $subscribed,
            'name'       => $name
        ));

        // functions expects array not string, example wrong?
        $result = $this->mailgunClient->put(
            "lists/$listAddress/members/$memberAddress",$arg);
    }

    /**
     * @param $members
     * @param $members
     * 		array of stdclass[] =
     *			{
     *				address: string;
     *				name: string; (optional)
     *				vars : {
     *					(var name) : value,
     *					. . .
     *				}
     *			}
     *
     * @return string
     */
    public function memberListToString($members) {
        $memberList = [];
        foreach ($members as $member) {
            $memberList[] = json_encode($member);
        }
        return '[' . implode(',', $memberList) . ']';
    }

    public function removeMember($listAddress,$listMember) {
        return $this->mailgunClient->delete("lists/$listAddress/members/$listMember");
    }

    public function removeList($listAddress) {
        return $this->mailgunClient->delete("lists/$listAddress");
    }

    /**
     * @param $listAddress
     * @param $members (see memberListToString
     * @param bool $upsert
     */
    public function addUpdateMembers($listAddress, $members, $upsert=true)
    {
        return false;
        $memberList = $this->memberListToString($members);
        $result = $this->mailgunClient->post(
            "lists/$listAddress/members.json",
            array(
                'members' => $memberList,
                'upsert' => $upsert
            )
        );
    }

    public function getListMembers($listAddress) {
        $result = $this->mailgunClient->get("lists/$listAddress/members/pages", array(
            'subscribed' => 'yes',
            'limit'      =>  100
        ));

        // return $result;
        return @$result->http_response_body->items ?? [];
    }

    public function sychronizeList($listAddress,$memberList,$createList = false,$subscribeAll=true) {
        $response = new \stdClass();
        $response->removed = 0;
        $response->added = 0;
        $response->memberCount = 0;
        try {
            $members = $this->getListMembers($listAddress);
            $response->memberCount = count($members);
        }
        catch (\Exception $ex) {
            if ($createList) {
                $this->addList($listAddress);
                $members = [];
            }
            else {
                return $response;
            }
        }

        if (!empty($members)) {
            $new = $this->getaddresses($memberList);
            $current = $this->getaddresses($members);
            $delete = array_diff($current, $new);
            @list($listName,$domain) = explode('@',$memberList);
            $additional = $listName.'-additional@'.$domain;
            foreach ($delete as $memberAddress) {
                if ($memberAddress != $additional ) {
                    $response->removed++;
                    $this->removeMember($listAddress, $memberAddress);
                }
            }
            $added = array_diff($new,$current);
            $response->added = count($added);
            if (!$response->added) {
               $response->memberCount = $response->memberCount - $response->removed;
               return $response;
            }
        }

        $count = count($memberList);
        if ($count) {
            if ($subscribeAll) {
                for ($i = 0; $i < $count; $i++) {
                    $memberList[$i]->subscribed = true;
                }
            }
            $this->addUpdateMembers($listAddress,$memberList);
        }
        $response->memberCount = $response->memberCount + $response->added - $response->removed;
        return $response;
    }

    private function getaddresses($members) {
        $result = [];
        foreach ($members as $member) {
            $result[] = $member->address;
        }
        return $result;
    }

}