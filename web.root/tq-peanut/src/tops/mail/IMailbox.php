<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/26/2015
 * Time: 7:04 AM
 */
namespace Tops\mail;


/**
 * Class TMailbox
 * @package Tops\sys
 */
interface IMailbox
{
    /**
     * @return int
     */
    public function getMailboxId();

    /**
     * @param int $mailBoxId
     */
    public function setMailboxId($mailBoxId);

    /**
     * @return string
     */
    public function getMailboxCode();

    /**
     * @param string $mailBoxCode
     */
    public function setMailboxCode($mailBoxCode);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @param string $email
     */
    public function setEmail($email);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     */
    public function setDescription($description);
}