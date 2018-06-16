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

namespace mail\manager;

use dynoLibPacket\inputPacketLib;
use dynoPM\event\packet\OutputPacketReceivedEvent;
use dynoPM\network\packages\executor\inputPacket;
use mail\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Event extends PluginBase implements Listener
{
    /** @var Main */
    private $plugin;

    /**
     * Event constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param OutputPacketReceivedEvent $event
     */
    public function outputJoin(OutputPacketReceivedEvent $event)
    {
        $packet = $event->getPacket();
        $server = $this->plugin->getServer();
        if ($packet->pluginClass === self::class) {
            if ($packet->tunnelKey === "CheckTableMailExist") {
                $player = $server->getPlayer($packet->want["pn"]);
                if ((bool)$packet->getters["CheckTableMailExist"] === false) {
                    $this->JoinTable(1, $player);
                }
                $this->JoinTable(2, $player);
            }
            if ($packet->tunnelKey === "LookMail") {
                /** @var string $playerName */
                $playerName = $packet->want["pn"];
                if (($countMs = (int)$packet->getters["mailNumber"]) === 0) {
                    $server->getPlayer($playerName)
                        ->sendMessage(TextFormat::RED . "You have not received any mail in your absence");
                } else {
                    $server->getPlayer($playerName)
                        ->sendMessage(TextFormat::GREEN . "You have: " . TextFormat::YELLOW . $countMs . TextFormat::GREEN . " mail, use the command '/mail reads new' to read them");
                }
            }
        }
    }

    /**
     * @param int $type
     * @param Player $player
     */
    public function JoinTable(int $type, Player $player)
    {
        switch ($type) {
            case 0: //Check Table Exist
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->tableExist($player->getName(), "CheckTableMailExist")
                    ->finalInput();
                $pk->tunnelKey = "CheckTableMailExist";
                $pk->input = $final;
                $pk->pluginClass = self::class;
                $pk->want = [
                    "pn" => $player->getName()
                ];
                $this->plugin->getDyno()->sendDataPacket($pk);
                break;
            case 1: //Create Table if does not exist
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->createTable($player->getName())
                    ->getTable($player->getName())
                    ->putArray("mails", ($ar = [
                        "DynoMail:" . uniqid() => [
                            "msg" => "This is your mailbox!",
                            "read" => false
                        ]
                    ]))
                    ->putInt("mailNumberNotRead", count($ar))
                    ->finalInput();
                $pk->tunnelKey = "CreateTableMail";
                $pk->input = $final;
                $pk->pluginClass = self::class;
                $this->plugin->getDyno()->sendDataPacket($pk);
                break;
            case 2: //look if player has mail
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->getTable($player->getName())
                    ->getInt("mailNumberNotRead", "mailNumber")
                    ->finalInput();
                $pk->tunnelKey = "LookMail";
                $pk->input = $final;
                $pk->pluginClass = self::class;
                $pk->want = [
                    "pn" => $player->getName()
                ];
                $this->plugin->getDyno()->sendDataPacket($pk);
                break;
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function playerJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->JoinTable(0, $player);
    }
}