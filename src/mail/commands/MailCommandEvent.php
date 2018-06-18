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
use dynoPM\event\packet\OutputPacketReceivedEvent;
use dynoPM\network\packages\executor\inputPacket;
use mail\Main;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class MailCommandEvent implements Listener
{
    /** @var Main */
    private $plugin;

    /**
     * MailCommandEvent constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param OutputPacketReceivedEvent $event
     */
    public function outputCommandSend(OutputPacketReceivedEvent $event)
    {
        $packet = $event->getPacket();
        if ($packet->tunnelKey === "CommandMailSend") {
            $playerName = $packet->want["pn"];
            if ((bool)$packet->getters["lookTableExist"] === true) { //Player Exist, Good to send Message !
                $wantSendPn = $packet->want["wantSendPn"];
                $msg = $packet->want["msg"];
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->getTable($wantSendPn)
                    ->getArray("mails", "mails")
                    ->finalInput();
                $pk->input = $final;
                $pk->tunnelKey = "BaseArrayTableBeforeSend";
                $pk->pluginClass = self::class;
                $pk->want = [
                    "pn" => $playerName,
                    "wantSendPn" => $wantSendPn,
                    "msg" => $msg
                ];
                $this->getDyno()->sendDataPacket($pk);
            } else {
                $this->plugin->getServer()->getPlayer($playerName)
                    ->sendMessage(TextFormat::RED . "The player who you want to send a mail does not exist!");
            }
        }
        if ($packet->pluginClass === self::class) {
            if ($packet->tunnelKey === "BaseArrayTableBeforeSend") {
                $mails = $packet->getters["mails"];
                $wantSendPn = $packet->want["wantSendPn"];
                $pn = $packet->want["pn"];
                $mails[$pn . ":" . uniqid()] = [
                    "msg" => $packet->want["msg"],
                    "read" => false
                ];
                $pk = new inputPacket();
                $final = new inputPacketLib();
                $final = $final
                    ->getBase(Main::MAIL_BASE_NAME)
                    ->getTable($wantSendPn)
                    ->putArray("mails", $mails)
                    ->valueMath("mailNumberNotRead")->addition(1)
                    ->finalInput();
                $pk->input = $final;
                $this->getDyno()->sendDataPacket($pk);
                $player = $this->plugin->getServer()->getPlayer($pn);
                $player->sendMessage(TextFormat::GREEN . "Your mail has been sent to " . $wantSendPn);
            }
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
     * @param OutputPacketReceivedEvent $event
     */
    public function outputCommandReads(OutputPacketReceivedEvent $event)
    {
        $packet = $event->getPacket();
        if ($packet->tunnelKey == "commandMailReadsNew") {
            /** @var string $playerName */
            $playerName = $packet->want["pn"];
            $player = $this->plugin->getServer()->getPlayer($playerName);
            /** @var array $mails */
            $mails = $packet->getters["mails"];
            $countNew = 0;
            /**
             * @var string[]|bool[] $mailOp
             */
            foreach ($mails as $mailOp) {
                if ($mailOp["read"] == false) {
                    $countNew++;
                }
            }
            if ($countNew === 0) {
                $player->sendMessage(TextFormat::RED . "You do not have new mail");
                return;
            }
            $player->sendMessage(
                TextFormat::AQUA . "Your new mail: (" . TextFormat::YELLOW . $countNew . TextFormat::AQUA . ")"
            );
            /**
             * @var string $senderName
             * @var string[]|bool[] $mailOp
             */
            foreach ($mails as $senderNameBase => $mailOp) {
                if ($mailOp["read"] == false) {
                    $senderName = explode(":", $senderNameBase);
                    $senderName = $senderName[0];
                    $player->sendMessage(
                        TextFormat::YELLOW . $senderName . " : " . TextFormat::clean($mailOp["msg"])
                    );
                    $mails[$senderNameBase]["read"] = true;
                }
            }
            $pk = new inputPacket();
            $final = new inputPacketLib();
            $final = $final
                ->getBase(Main::MAIL_BASE_NAME)
                ->getTable($playerName)
                ->putArray("mails", $mails)
                ->putInt("mailNumberNotRead", 0)
                ->finalInput();
            $pk->input = $final;
            $this->getDyno()->sendDataPacket($pk);
        }
        if ($packet->tunnelKey == "commandMailReadsAll") {
            /** @var string $playerName */
            $playerName = $packet->want["pn"];
            $player = $this->plugin->getServer()->getPlayer($playerName);
            /** @var array $mails */
            $mails = $packet->getters["mails"];
            $player->sendMessage(
                TextFormat::AQUA . "List of all your mail: (" . TextFormat::YELLOW . count($mails) . TextFormat::AQUA . ")"
            );
            /**
             * @var string $senderName
             * @var string[]|bool[] $mailOp
             */
            foreach ($mails as $senderName => $mailOp) {
                $senderName = explode(":", $senderName);
                $senderName = $senderName[0];
                $player->sendMessage(
                    TextFormat::YELLOW . $senderName . " : " . TextFormat::clean($mailOp["msg"])
                );
                if ($mailOp["read"] == false) {
                    $mailOp["read"] = true;
                    $pk = new inputPacket();
                    $final = new inputPacketLib();
                    $final = $final
                        ->getBase(Main::MAIL_BASE_NAME)
                        ->getTable($playerName)
                        ->putArray("mails", $mailOp)
                        ->putInt("mailNumberNotRead", 0)
                        ->finalInput();
                    $pk->input = $final;
                    $this->getDyno()->sendDataPacket($pk);
                }
            }
        }
    }
}