<?php

namespace App\Twig;

use App\Service\SettingService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private SettingService $settings) {}

    public function getGlobals(): array
    {
        return [
            'church_name' => $this->settings->getChurchName(),
        ];
    }
}
