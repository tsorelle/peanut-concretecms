<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/30/2019
 * Time: 11:18 AM
 */

namespace Tops\mail;

interface IForwardingListManager
{
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
    public function addList($address,  $name=null,  $description = null, $accessLevel = 'everyone');

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
    public function addMember($listAddress, $memberAddress, $memberName = null, $extra = []);

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
    public function getMailingLists($limit = 100);

    public function updateMember($listAddress, $memberAddress, $name = null, $subscribed = true);

    /**
     * @param $members
     * @param $members
     *        array of stdclass[] =
     *            {
     *                address: string;
     *                name: string; (optional)
     *                vars : {
     *                    (var name) : value,
     *                    . . .
     *                }
     *            }
     *
     * @return string
     */
    public function memberListToString($members);

    public function removeMember($listAddress, $listMember);

    public function removeList($listAddress);

    /**
     * @param $listAddress
     * @param $members (see memberListToString
     * @param bool $upsert
     */
    public function addUpdateMembers($listAddress, $members, $upsert = true);

    public function getListMembers($listAddress);

    public function sychronizeList($listAddress,$memberList,$createList = false,$subscribeAll=true);
}