<?php

namespace App\Twig;

use App\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private SettingService $settings) {}

    public function getGlobals(): array
    {
        return [
            'church_name' => $this->settings->getChurchName(),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', fn($v) => is_string($v) ? json_decode($v, true) : $v),
            new TwigFilter('min', fn($v) => is_array($v) ? min($v) : $v),
            new TwigFilter('max', fn($v) => is_array($v) ? max($v) : $v),
        ];
    }
}
