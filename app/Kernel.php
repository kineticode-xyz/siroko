<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Kernel mínimo de Symfony. Es el único punto del framework que vive fuera de Siroko\;
 * toda la lógica (dominio/aplicación) y los adaptadores viven bajo Siroko\.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
