<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-15 13:11:28
 */ 

namespace Peanut\PeanutMailings\db\model\entity;

class EmailMessageRecipient
{
    public $id;
    public $mailMessageId;
    public $personId;
    public $toAddress;
    public $toName;
}