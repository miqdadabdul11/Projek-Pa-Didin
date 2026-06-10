<?php
namespace App\View\Components;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
class AppBrand extends Component
{
    public function __construct() {}
    public function render(): View|Closure|string
    {
        return <<<'HTML'
            <a href="/" wire:navigate>
                <!-- Hidden when collapsed -->
                <div {{ $attributes->class(["hidden-when-collapsed"]) }}>
                    <div class="flex items-center px-3 py-3">
                        <img src="/images/logo-eldiablo.png" alt="ElDiablo" class="h-14 w-auto object-contain" />
                    </div>
                </div>
                <!-- Display when collapsed -->
                <div class="display-when-collapsed hidden mx-2 mt-3 mb-1">
                    <img src="/images/logo-eldiablo.png" alt="ElDiablo" class="h-8 w-auto object-contain" />
                </div>
            </a>
        HTML;
    }
}
