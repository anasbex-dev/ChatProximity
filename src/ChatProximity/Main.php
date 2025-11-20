<?php

namespace ChatProximity;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private int $radius;
    private int $whisperRadius;
    private int $shoutRadius;
    private bool $hideSelf;
    private array $toggle = []; // player toggle system
    private array $worldSettings = [];
    private array $format = [];
    private array $distanceTag = [];

    public function onEnable() : void {
        $this->saveDefaultConfig();
        $cfg = $this->getConfig();

        $this->radius = $cfg->get("radius", 20);
        $this->whisperRadius = $cfg->get("whisper-radius", 6);
        $this->shoutRadius = $cfg->get("shout-radius", 40);
        $this->hideSelf = $cfg->get("hide-self", false);
        $this->worldSettings = $cfg->get("world-settings", []);
        $this->format = $cfg->get("format", []);
        $this->distanceTag = $cfg->get("distance-tag", []);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** Handle toggle command */
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $e) : void {
        $msg = strtolower($e->getMessage());
        $p = $e->getPlayer();

        if ($msg === "/togglechat") {
            if (!$p->hasPermission("chatprox.toggle")) {
                $p->sendMessage("§cYou don't have permission.");
                return;
            }

            if (!isset($this->toggle[$p->getName()])) $this->toggle[$p->getName()] = true;

            $this->toggle[$p->getName()] = !$this->toggle[$p->getName()];

            $p->sendMessage($this->toggle[$p->getName()]
                ? "§aProximity chat enabled"
                : "§cProximity chat disabled"
            );
            $e->cancel();
        }
    }

    /** Main chat handler */
    public function onPlayerChat(PlayerChatEvent $e) : void {
        $p = $e->getPlayer();
        $name = $p->getName();
        $msg = $e->getMessage();

        // Toggle off → global chat
        if (isset($this->toggle[$name]) && $this->toggle[$name] === false) {
            return;
        }

        // Bypass → global
        if ($p->hasPermission("chatprox.bypass")) {
            return;
        }

        $world = $p->getWorld()->getDisplayName();

        // If world disabled
        if (isset($this->worldSettings[$world]) && !$this->worldSettings[$world]["enabled"]) {
            return;
        }

        // Detect whisper & shout
        $radius = $this->radius;
        $format = $this->format["normal"];

        if (str_starts_with($msg, "@w ")) {
            $radius = $this->whisperRadius;
            $msg = substr($msg, 3);
            $format = $this->format["whisper"];
        } elseif (str_starts_with($msg, "@s ")) {
            $radius = $this->shoutRadius;
            $msg = substr($msg, 3);
            $format = $this->format["shout"];
        }

        $recipients = [];

        foreach ($p->getWorld()->getPlayers() as $pl) {
            if (!$pl->isOnline()) continue;

            $dist = $pl->distance($p);

            if ($dist <= $radius) {
                if ($this->hideSelf && $pl->getName() === $p->getName()) {
                    continue;
                }
                $recipients[] = $pl;
            }
        }

        // If nobody hears except player itself
        if (count($recipients) === 0) {
            $p->sendMessage("§7(§8Nobody near hears you§7)");
        }

        // Format replace
        $format = str_replace(
            ["{player}", "{msg}"],
            [$name, $msg],
            $format
        );

        $e->setFormat($format);
        $e->setRecipients($recipients);
    }
}