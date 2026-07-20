<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2018-04-18 11:37:49
 */ 

namespace Peanut\QnutCalendar\db\model\entity;

class NotificationSubscription  extends \Tops\db\TAbstractEntity 
{ 
    public $id;
    public $notificationTypeId;
    public $itemId;
    public $personId;
    public $leadDays;

}
