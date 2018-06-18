<?php
/**
 *
 * This program is free: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @copyright (c) 2018
 * @author Y&SS-MineBuilderFR
 */

declare(strict_types=1);

namespace mail;

use dynoLibPacket\inputPacketLib;
use dynoLibPacket\libOptions\BaseOptionsInterface;
use dynoPM\{
    Dyno, DynoPM
};
use dynoPM\network\packages\executor\inputPacket;
use mail\commands\MailCommand;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
    public const MAIL_BASE_NAME = "dynoMail";

    /** @var Dyno $dyno */
    private $dyno;

    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $dynoDesc = (string)$this->getConfig()->get("DynoDescription");
        DynoPM::getInstance()->addPluginSyncWithDynoDescription($this, $dynoDesc);
        if (($this->dyno = DynoPM::getInstance()->getDynoByDescription($dynoDesc)) === null) {
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->getServer()->getLogger()->info("Dyno mail been enabled!");
        $this->getServer()->getLogger()->info(
            TextFormat::AQUA . "- DynoMail is demo plugin not a full mail system! -"
        );
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents(($list = new \mail\listener($this)), $this);
        $list->register();
        $mailCommand = new MailCommand($this);
        $this->getServer()->getCommandMap()->register($mailCommand->getName(), $mailCommand);
        $this->createBase();
    }

    private function createBase()
    {
        $pk = new inputPacket();
        $final = new inputPacketLib();
        $final = $final
            ->createBase(self::MAIL_BASE_NAME, [
                BaseOptionsInterface::ONLY_IF_BASE_NOT_EXIST
            ])
            ->finalInput();
        $pk->input = $final;
        $this->dyno->sendDataPacket($pk);
    }

    /**
     * @return Dyno
     */
    public function getDyno(): Dyno
    {
        return $this->dyno;
    }
}