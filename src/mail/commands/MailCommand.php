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

namespace mail\commands;

use dynoLibPacket\inputPacketLib;
use dynoPM\Dyno;
use dynoPM\network\packages\executor\inputPacket;
use mail\Main;
use pocketmine\command\{
    Command, CommandSender, PluginIdentifiableCommand
};
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class MailCommand extends Command implements PluginIdentifiableCommand
{

    /** @var string */
    private $commandName = "mail";
    /** @var Main */
    private $plugin;

    /**
     * MailCommand constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        parent::__construct($this->commandName, "Base Mail command");
        $this->setUsage("/" . $this->commandName);
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->plugin->isEnabled()) {
            return false;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("§2Please use this command in-game.");
            return true;
        }

        if (!isset($args[0]) or strtolower($args[0]) == "help") {
            $this->helpUsage($sender);
            return true;
        }

        if (strtolower($args[0]) == "reads") {
            if (strtolower($args[1]) == "all"
                or strtolower($args[1]) == "new") {
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->getTable($sender->getName())
                    ->getArray("mails", "mails")
                    ->getInt("mailNumberNotRead", "mailNumber")
                    ->finalInput();
                $pk->tunnelKey = "commandMailReads" . ucfirst($args[1]);
                $pk->input = $final;
                $pk->want = [
                    "pn" => $sender->getName(),
                    "wantSendPn"
                ];
                $this->getDyno()->sendDataPacket($pk);
            } else {
                $this->helpUsage($sender);
                return true;
            }
        } elseif (strtolower($args[0]) == "send") {
            if (!isset($args[1]) or !isset($args[2])) {
                $this->helpUsage($sender);
                return true;
            }
            if ($sender->getName() == $args[1]) {
                $sender->sendMessage(TextFormat::RED . "You can not send a message to yourself");
                return true;
            }
            $pk = new inputPacket();
            $final = new inputPacketLib();
            $final = $final
                ->getBase(Main::MAIL_BASE_NAME)
                ->tableExist($args[1], "lookTableExist")
                ->getTable($sender->getName())
                ->getArray("mails", "mails")
                ->finalInput();
            $pk->input = $final;
            $pk->want = [];
            $pk->want["pn"] = $sender->getName();
            $pk->want["wantSendPn"] = $args[1];
            unset($args[0], $args[1]);
            $pk->want["msg"] = implode(" ", $args);
            $pk->tunnelKey = "CommandMailSend";
            $this->getDyno()->sendDataPacket($pk);
        } else {
            $this->helpUsage($sender);
        }
        return true;
    }

    /**
     * @param Player $player
     */
    public function helpUsage(Player $player)
    {
        $ms = array(
            TextFormat::YELLOW . "-- Help --",
            $this->getUsage() . " reads <all/new>",
            $this->getUsage() . " send <PlayerName> <Message>"
        );
        foreach ($ms as $message) {
            $player->sendMessage($message);
        }
    }

    /**
     * @return Dyno
     */
    public function getDyno(): Dyno
    {
        return $this->plugin->getDyno();
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}