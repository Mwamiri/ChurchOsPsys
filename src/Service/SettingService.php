<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SettingService
{
    private array $cache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CacheInterface $appCache,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key] ?? $default;
        }
        $setting = $this->em->find(Setting::class, $key);
        $value = $setting?->getValue();
        $this->cache[$key] = $value;
        return $value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $setting = $this->em->find(Setting::class, $key);
        if (!$setting) {
            $setting = new Setting();
            $setting->setKey($key);
            $this->em->persist($setting);
        }
        $setting->setValue(is_string($value) ? $value : json_encode($value));
        $this->em->flush();
        $this->cache[$key] = $setting->getValue();
    }

    public function all(): array
    {
        $rows = $this->em->getRepository(Setting::class)->findAll();
        $out = [];
        foreach ($rows as $r) { $out[$r->getKey()] = $r->getValue(); }
        return $out;
    }

    public function getChurchName(): string
    {
        return $this->get('church_name', 'ChurchOS P Edition');
    }
}
