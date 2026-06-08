<?php

declare(strict_types=1);

namespace Siroko\Shared\Infrastructure\Console;

use Doctrine\ORM\EntityManagerInterface;
use Siroko\Cart\Infrastructure\Catalog\ProductRecord;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Siembra el catálogo con productos de prueba (SKU-0001..SKU-N), precio en céntimos y stock.
 * Suficientes productos para que la medición de performance (consulta batch) sea real.
 */
#[AsCommand(name: 'app:seed-catalog', description: 'Siembra la tabla products con productos de prueba.')]
final class SeedCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('count', null, InputOption::VALUE_REQUIRED, 'Número de productos a crear', '1000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = max(1, (int) $input->getOption('count'));

        // Vacía la tabla para que el comando sea idempotente.
        $this->em->getConnection()->executeStatement('DELETE FROM products');

        for ($i = 1; $i <= $count; $i++) {
            $sku = sprintf('SKU-%04d', $i);
            $priceCents = 500 + (($i * 137) % 9500); // entre 5.00 y ~100.00 EUR
            $stock = 50 + ($i % 50);

            $this->em->persist(new ProductRecord($sku, $priceCents, 'EUR', $stock));

            if ($i % 200 === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();

        $io->success(sprintf('Catálogo sembrado con %d productos (SKU-0001..SKU-%04d).', $count, $count));

        return Command::SUCCESS;
    }
}
