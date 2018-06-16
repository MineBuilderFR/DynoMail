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

use mail\commands\MailCommandEvent;
use mail\manager\Event;
use pocketmine\plugin\PluginBase;

class listener extends PluginBase implements \pocketmine\event\Listener
{
    public static $instance = null;
    /** @var Main */
    private $plugin;

    /**
     * listener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        self::$instance = $this;
    }

    /**
     * @return listener
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function register()
    {
        $plugin = $this->plugin;
        $listener = array(
            new Event($plugin),
            new MailCommandEvent($plugin)
        );
        foreach ($listener as $list) {
            $plugin->getServer()->getPluginManager()->registerEvents($list, $plugin);
        }
    }
}