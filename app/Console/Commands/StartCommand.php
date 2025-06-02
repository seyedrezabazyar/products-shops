<?php

namespace App\Console\Commands;

use App\Http\Controllers\StartController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StartCommand extends Command
{
    protected $signature = 'scrape:start
                            {--config= : Path to the configuration JSON file}
                            {--delay=500 : Delay between requests in milliseconds}
                            {--urls= : Comma-separated list of base URLs}
                            {--products-urls= : Comma-separated list of product listing URLs}
                            {--batch-size=100 : Number of products per batch}
                            {--update : Reset products and mark all links as unprocessed for re-scraping}';

    protected $description = 'A flexible web scraper configurable via CLI or JSON file.';

    public function handle(): int
    {
        $this->info('Starting flexible scraper...');

        $config = $this->loadConfiguration();
        if (!$config) {
            return 1;
        }

        // اگر گزینه --update فعال است، کانفیگ را برای حالت update تنظیم می‌کنیم
        if ($this->option('update')) {
            $this->info('Update mode activated - will reset products and reprocess all links');
            $config['database'] = 'continue'; // از دیتابیس موجود استفاده کن
            $config['run_method'] = 'continue'; // از لینک‌های موجود استفاده کن
            $config['update_mode'] = true; // فلگ جدید برای شناسایی حالت update
        }

        $scraper = new StartController($config);
        $scraper->setOutputCallback(fn($message) => $this->line($message));

        if ($this->option('delay') !== null) {
            $delay = (int)$this->option('delay') * 1000;
            $scraper->setRequestDelay($delay);
            $this->info("Request delay set to {$this->option('delay')} ms.");
        }

        $this->info('Starting scrape operation...');
        $result = $scraper->scrapeMultiple();

        if ($result['status'] === 'success') {
            $this->info('Scraping completed successfully!');
            $this->info("Total products scraped: {$result['total_products']}");
            $this->info("Failed links: {$result['failed_links']}");
            return 0;
        }

        $this->error("Scraping failed: {$result['message']}");
        return 1;
    }

    private function loadConfiguration(): array|bool
    {
        $config = [];

        $configPath = $this->option('config');
        if ($configPath && File::exists($configPath)) {
            $this->info("Loading configuration from $configPath");
            $jsonConfig = File::get($configPath);
            $fileConfig = json_decode($jsonConfig, true);

            if (!$fileConfig) {
                $this->error('Invalid JSON configuration file.');
                return false;
            }
            $config = $fileConfig;
        }
        $isProductTestMode = $config['product_test'] ?? false;

        if ($isProductTestMode) {
            $this->info('🧪 Product Test Mode detected');

            // در حالت تست محصول فقط product_urls نیاز داریم
            if (empty($config['product_urls'])) {
                $this->error('Product Test Mode requires product_urls in configuration.');
                return false;
            }

            $this->info('Found ' . count($config['product_urls']) . ' product URLs for testing');
            return $config; // در حالت تست، نیازی به بررسی base_urls و products_urls نیست
        }


        if ($this->option('urls')) {
            $config['base_urls'] = array_map('trim', explode(',', $this->option('urls')));
        }

        if ($this->option('products-urls')) {
            $config['products_urls'] = array_map('trim', explode(',', $this->option('products-urls')));
        }

        if ($this->option('batch-size')) {
            $config['batch_size'] = (int)$this->option('batch-size');
        }

        if (empty($config['base_urls']) || empty($config['products_urls'])) {
            $this->error('Required options missing or mismatched: base_urls and products_urls must be provided and match in count.');
            return false;
        }

        return $config;
    }
}
