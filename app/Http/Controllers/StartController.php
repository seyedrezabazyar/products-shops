<?php

namespace App\Http\Controllers;

use App\Models\FailedLink;
use App\Models\Link;
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;

class StartController
{
    public array $config;
    private Client $httpClient;
    private $outputCallback = null;
    private int $processedCount = 0;

    // Color constants
    private const COLOR_GREEN = "\033[1;92m";
    private const COLOR_RED = "\033[1;91m";
    private const COLOR_PURPLE = "\033[1;95m";
    private const COLOR_YELLOW = "\033[1;93m";
    private const COLOR_BLUE = "\033[1;94m";
    private const COLOR_GRAY = "\033[1;90m";
    private const COLOR_CYAN = "\033[1;36m";
    private const COLOR_RESET = "\033[0m";

    // Helper classes
    private ConfigValidator $configValidator;
    private DatabaseManager $databaseManager;
    private ProductDataProcessor $productProcessor;
    private LinkScraper $linkScraper;

    public function __construct(array $config)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->config = $config;

        // Initialize helper classes
        $this->configValidator = new ConfigValidator();
        $this->configValidator->setOutputCallback([$this, 'handleOutput']);

        $this->databaseManager = new DatabaseManager($config);
        $this->databaseManager->setOutputCallback([$this, 'handleOutput']);

        $this->productProcessor = new ProductDataProcessor($config);
        $this->productProcessor->setOutputCallback([$this, 'handleOutput']);

        // بررسی حالت تست محصول قبل از اعتبارسنجی معمولی
        $isProductTestMode = $this->config['product_test'] ?? false;

        if ($isProductTestMode) {
            $this->log("🧪 Product Test Mode - Skipping standard config validation", self::COLOR_PURPLE);
            $this->configValidator->validateProductTestConfig($this->config);
        } else {
            $this->configValidator->validateAndFixConfig($this->config);
        }

        // تنظیم زمان تاخیر
        $delay = $this->config['request_delay'] ?? mt_rand(
            $this->config['request_delay_min'] ?? 500,
            $this->config['request_delay_max'] ?? 2000
        );
        $this->setRequestDelay($delay);

        // تنظیم HTTP Client
        $baseUrl = '';
        if ($isProductTestMode && !empty($this->config['product_urls'])) {
            $firstUrl = $this->config['product_urls'][0];
            $parsedUrl = parse_url($firstUrl);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        } elseif (!empty($this->config['base_urls'])) {
            $baseUrl = $this->config['base_urls'][0];
        }

        $this->httpClient = new Client([
            'timeout' => $this->config['timeout'] ?? 120,
            'verify' => $this->config['verify_ssl'] ?? false,
            'headers' => [
                'User-Agent' => $this->randomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => $baseUrl,
                'Connection' => 'keep-alive',
            ],
        ]);

        // Initialize LinkScraper with HTTP client
        $this->linkScraper = new LinkScraper($config, $this->httpClient);
        $this->linkScraper->setOutputCallback([$this, 'handleOutput']);
    }

    public function setOutputCallback(callable $callback): void
    {
        $this->outputCallback = $callback;
    }

    public function setRequestDelay(int $delay): void
    {
        $this->config['request_delay_min'] = $delay;
        $this->config['request_delay_max'] = $delay;
    }

    public function scrapeMultiple(?int $start_id = null): array
    {
        $this->log("Inside scrapeMultiple method", self::COLOR_PURPLE);
        $this->log("Starting scraper with start_id: " . ($start_id ?? 'not set'), self::COLOR_GREEN);

        // بررسی حالت تست محصول
        $isProductTestMode = $this->config['product_test'] ?? false;
        if ($isProductTestMode) {
            $this->log("🧪 Product Test Mode Detected - Testing individual products", self::COLOR_PURPLE);
            return $this->runProductTestMode();
        }

        // بررسی حالت update
        $isUpdateMode = $this->config['update_mode'] ?? false;
        if ($isUpdateMode) {
            $this->log("Update mode detected", self::COLOR_PURPLE);
        }

        // اعتبارسنجی کانفیگ
        $this->configValidator->validateConfig($this->config);

        // تنظیم دیتابیس
        $this->databaseManager->setupDatabase();

        // اگر در حالت update هستیم، ابتدا reset انجام میدهیم
        if ($isUpdateMode) {
            $this->databaseManager->resetProductsAndLinks();
        }

        // تنظیم اولیه
        $this->processedCount = 0;

        // اعتبارسنجی start_id
        if ($start_id !== null && $start_id <= 0) {
            $this->log("Invalid start_id: $start_id. Must be a positive integer. Ignoring start_id.", self::COLOR_RED);
            $start_id = null;
        }

        // بررسی run_method
        $runMethod = $this->config['run_method'] ?? 'new';
        $this->log("Run method: $runMethod", self::COLOR_GREEN);

        $links = [];
        $pagesProcessed = 0;

        if ($runMethod === 'continue' || $isUpdateMode) {
            $this->log("Continuing with links from database" . ($start_id ? " starting from ID $start_id" : "") . "...", self::COLOR_GREEN);

            if ($isUpdateMode) {
                $totalLinksInDb = Link::count();
                $this->log("Total links in database: $totalLinksInDb", self::COLOR_BLUE);

                if ($totalLinksInDb == 0) {
                    $this->log("No links found in database for update mode. Need to fetch from web first.", self::COLOR_YELLOW);

                    $this->log("Fetching product links from web for update mode...", self::COLOR_GREEN);
                    $result = $this->linkScraper->fetchProductLinks();
                    $links = $result['links'] ?? [];
                    $pagesProcessed = $result['pages_processed'] ?? 0;

                    $this->log("Got " . count($links) . " unique product links from web", self::COLOR_GREEN);

                    if (!empty($links)) {
                        $this->databaseManager->saveProductLinksToDatabase($links);
                        $result = $this->databaseManager->getProductLinksFromDatabase($start_id);
                        $links = $result['links'] ?? [];
                        $pagesProcessed = $result['pages_processed'] ?? 0;
                    } else {
                        $this->log("No links collected from web. Stopping scrape.", self::COLOR_YELLOW);
                        return [
                            'status' => 'success',
                            'total_products' => 0,
                            'failed_links' => 0,
                            'total_pages_count' => $pagesProcessed,
                            'products' => []
                        ];
                    }
                } else {
                    $result = $this->databaseManager->getProductLinksFromDatabase($start_id);
                    $links = $result['links'] ?? [];
                    $pagesProcessed = $result['pages_processed'] ?? 0;
                }
            } else {
                $result = $this->databaseManager->getProductLinksFromDatabase($start_id);
                $links = $result['links'] ?? [];
                $pagesProcessed = $result['pages_processed'] ?? 0;
            }

            $this->log("Got " . count($links) . " links from database", self::COLOR_GREEN);
            if (empty($links)) {
                $this->log("No links found in database" . ($start_id ? " for ID >= $start_id" : "") . ". Stopping scrape.", self::COLOR_YELLOW);
                return [
                    'status' => 'success',
                    'total_products' => 0,
                    'failed_links' => 0,
                    'total_pages_count' => $pagesProcessed,
                    'products' => []
                ];
            }
        } else {
            $this->log("Fetching product links from web...", self::COLOR_GREEN);
            $result = $this->linkScraper->fetchProductLinks();
            $links = $result['links'] ?? [];
            $pagesProcessed = $result['pages_processed'] ?? 0;

            $this->log("Got " . count($links) . " unique product links from web", self::COLOR_GREEN);

            if (!empty($links)) {
                $this->databaseManager->saveProductLinksToDatabase($links);
            } else {
                $this->log("No links collected from web. Stopping scrape.", self::COLOR_YELLOW);
                return [
                    'status' => 'success',
                    'total_products' => 0,
                    'failed_links' => 0,
                    'total_pages_count' => $pagesProcessed,
                    'products' => []
                ];
            }
        }

        // حذف لینک‌های تکراری
        $uniqueLinks = array_values(array_unique(array_map(function ($link) {
            return is_array($link) ? $link['url'] : $link;
        }, $links)));
        $this->log("After deduplication, processing " . count($uniqueLinks) . " unique links", self::COLOR_GREEN);

        // پردازش لینک‌های جمع‌آوری‌شده
        $processingMethod = $this->config['processing_method'] ?? $this->config['method'] ?? 1;
        $this->log("Processing links using method: $processingMethod", self::COLOR_GREEN);
        $this->processPagesInBatches($uniqueLinks, $processingMethod);

        // Get failed links count from database
        $failedLinksCount = FailedLink::count();

        // تلاش مجدد برای لینک‌های شکست‌خورده
        if ($failedLinksCount > 0) {
            $this->log("Found $failedLinksCount failed links in database. Attempting to retry...", self::COLOR_PURPLE);
            $processedBefore = $this->processedCount;
            $this->retryFailedLinks();
            $processedDuringRetry = $this->processedCount - $processedBefore;
            $this->log("Successfully processed $processedDuringRetry failed links during retry", self::COLOR_GREEN);
        }

        // Get updated failed links count after retries
        $remainingFailedLinksCount = FailedLink::count();

        $this->log("Scraping completed! Processed: {$this->processedCount}, Failed: {$remainingFailedLinksCount}", self::COLOR_GREEN);

        // جمع‌آوری محصولات از دیتابیس
        $products = Product::all()->map(function ($product) {
            return [
                'title' => $product->title,
                'price' => $product->price,
                'product_id' => $product->product_id ?? '',
                'page_url' => $product->page_url,
                'availability' => (int)$product->availability,
                'off' => (int)$product->off,
                'image' => $product->image,
                'guarantee' => $product->guarantee,
                'category' => $product->category,
            ];
        })->toArray();

        return [
            'status' => 'success',
            'total_products' => $this->processedCount,
            'failed_links' => $remainingFailedLinksCount,
            'total_pages_count' => $pagesProcessed,
            'products' => $products
        ];
    }

    private function processPagesInBatches(array $links, int $processingMethod = null): array
    {
        $this->log("Processing " . count($links) . " product links in batches...", self::COLOR_GREEN);

        $totalProducts = count($links);
        $this->processedCount = 0;
        $processedUrls = [];

        // فیلتر کردن لینک‌های نامعتبر
        $filteredProducts = array_filter($links, function ($product) {
            $url = is_array($product) ? $product['url'] : $product;
            $isValid = !$this->isUnwantedDomain($url) && !$this->isInvalidLink($url);
            if (!$isValid) {
                $this->log("Filtered out unwanted/invalid link: $url", self::COLOR_YELLOW);
            }
            return $isValid;
        });

        $this->log("Filtered to " . count($filteredProducts) . " valid product links", self::COLOR_GREEN);

        // تعیین روش پردازش
        $method = $processingMethod ?? $this->config['method'] ?? 1;
        $this->log("Using processing method: $method", self::COLOR_GREEN);

        // Method 1: Concurrent HTTP requests using Guzzle Pool
        if ($method === 1) {
            $requests = function () use ($filteredProducts) {
                foreach ($filteredProducts as $product) {
                    yield new Request('GET', is_array($product) ? $product['url'] : $product);
                }
            };

            $pool = new Pool($this->httpClient, $requests(), [
                'concurrency' => $this->config['concurrency'] ?? 5,
                'fulfilled' => function ($response, $index) use ($filteredProducts, &$processedUrls, $totalProducts) {
                    $product = $filteredProducts[$index];
                    $url = is_array($product) ? $product['url'] : $product;
                    $image = is_array($product) && isset($product['image']) ? $product['image'] : null;
                    $productId = is_array($product) && isset($product['product_id']) ? $product['product_id'] : '';

                    if (in_array($url, $processedUrls)) {
                        $this->log("Skipping duplicate URL: $url", self::COLOR_YELLOW);
                        return;
                    }

                    $this->processedCount++;
                    $this->log("Processing product {$this->processedCount}/{$totalProducts}: $url", self::COLOR_GREEN);

                    try {
                        $productData = $this->productProcessor->extractProductData($url, (string)$response->getBody(), $image, $productId);

                        if ($productData && $this->productProcessor->validateProductData($productData)) {
                            if (is_array($product) && isset($product['off'])) {
                                $productData['off'] = $product['off'];
                            }

                            DB::beginTransaction();
                            try {
                                $this->productProcessor->saveProductToDatabase($productData);
                                $this->databaseManager->updateLinkProcessedStatus($url);
                                DB::commit();

                                $processedUrls[] = $url;
                                $this->log("Successfully processed: $url", self::COLOR_GREEN);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                                $this->log("Failed to save product: $url - {$e->getMessage()}", self::COLOR_RED);
                            }
                        } else {
                            $this->saveFailedLink($url, "Invalid or missing product data");
                            $this->log("Failed to extract valid data: $url", self::COLOR_RED);
                        }
                    } catch (\Exception $e) {
                        $this->saveFailedLink($url, "Processing error: " . $e->getMessage());
                        $this->log("Processing error: $url - {$e->getMessage()}", self::COLOR_RED);
                    }
                },
                'rejected' => function ($reason, $index) use ($filteredProducts) {
                    $url = is_array($filteredProducts[$index]) ? $filteredProducts[$index]['url'] : $filteredProducts[$index];
                    $this->saveFailedLink($url, "Failed to fetch: " . $reason->getMessage());
                    $this->log("Fetch failed: $url - {$reason->getMessage()}", self::COLOR_YELLOW);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
        }
        // Method 2 & 3: Sequential processing
        else {
            $batchSize = $this->config['batch_size'] ?? 75;
            $batches = array_chunk($filteredProducts, $batchSize);

            foreach ($batches as $batchIndex => $batch) {
                $this->log("Processing batch " . ($batchIndex + 1) . "/" . count($batches) . " with " . count($batch) . " products", self::COLOR_GREEN);

                foreach ($batch as $product) {
                    $url = is_array($product) ? $product['url'] : $product;
                    $image = is_array($product) && isset($product['image']) ? $product['image'] : null;
                    $productId = is_array($product) && isset($product['product_id']) ? $product['product_id'] : '';

                    if (in_array($url, $processedUrls)) {
                        $this->log("Skipping duplicate URL: $url", self::COLOR_YELLOW);
                        continue;
                    }

                    $this->processedCount++;
                    $this->log("Processing product {$this->processedCount}/{$totalProducts}: $url", self::COLOR_GREEN);

                    try {
                        // برای متد 2 یا 3، ابتدا محتوای صفحه را با LinkScraper بگیریم
                        $body = $this->linkScraper->fetchPageContent($url, false, true);

                        if ($body === null) {
                            $this->saveFailedLink($url, "Failed to fetch page content");
                            $this->log("Failed to fetch content: $url", self::COLOR_RED);
                            continue;
                        }

                        $productData = $this->productProcessor->extractProductData($url, $body, $image, $productId);

                        if ($productData === null) {
                            $this->saveFailedLink($url, "Failed to extract product data");
                            $this->log("Failed to extract data: $url", self::COLOR_RED);
                            continue;
                        }

                        if (!$this->productProcessor->validateProductData($productData)) {
                            $this->saveFailedLink($url, "Invalid product data");
                            $this->log("Invalid product data: $url", self::COLOR_RED);
                            continue;
                        }

                        if (is_array($product) && isset($product['off'])) {
                            $productData['off'] = $product['off'];
                        }

                        DB::beginTransaction();
                        try {
                            $this->productProcessor->saveProductToDatabase($productData);
                            $this->databaseManager->updateLinkProcessedStatus($url);
                            DB::commit();

                            $processedUrls[] = $url;
                            $this->log("Successfully processed: $url", self::COLOR_GREEN);
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                            $this->log("Failed to save product: $url - {$e->getMessage()}", self::COLOR_RED);
                        }

                        // Add delay between requests to avoid rate limiting
                        $delay = mt_rand(
                            $this->config['request_delay_min'] ?? 500,
                            $this->config['request_delay_max'] ?? 2000
                        );
                        usleep($delay * 1000);

                    } catch (\Exception $e) {
                        $this->saveFailedLink($url, "Processing error: " . $e->getMessage());
                        $this->log("Processing error: $url - {$e->getMessage()}", self::COLOR_RED);
                    }
                }

                // Add delay between batches
                if ($batchIndex < count($batches) - 1) {
                    $batchDelay = $this->config['batch_delay'] ?? 5000;
                    $this->log("Waiting {$batchDelay}ms before next batch...", self::COLOR_YELLOW);
                    usleep($batchDelay * 1000);
                }
            }
        }

        return $processedUrls;
    }

    private function runProductTestMode(): array
    {
        $this->log("🧪 Running in Product Test Mode", self::COLOR_PURPLE);

        $productUrls = $this->config['product_urls'] ?? [];
        if (empty($productUrls)) {
            $this->log("❌ No product URLs provided for testing", self::COLOR_RED);
            return [
                'status' => 'error',
                'message' => 'No product URLs provided for testing',
                'total_products' => 0,
                'failed_links' => 0,
                'products' => []
            ];
        }

        $this->log("Testing " . count($productUrls) . " product URLs", self::COLOR_GREEN);

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($productUrls as $index => $url) {
            $this->log("Testing product " . ($index + 1) . "/" . count($productUrls) . ": $url", self::COLOR_CYAN);

            try {
                $body = $this->linkScraper->fetchPageContent($url, false, true);

                if ($body === null) {
                    $this->log("❌ Failed to fetch content for: $url", self::COLOR_RED);
                    $failedCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'failed',
                        'error' => 'Failed to fetch page content',
                        'data' => null
                    ];
                    continue;
                }

                $productData = $this->productProcessor->extractProductData($url, $body);

                if ($productData === null) {
                    $this->log("❌ Failed to extract data for: $url", self::COLOR_RED);
                    $failedCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'failed',
                        'error' => 'Failed to extract product data',
                        'data' => null
                    ];
                    continue;
                }

                if (!$this->productProcessor->validateProductData($productData)) {
                    $this->log("❌ Invalid product data for: $url", self::COLOR_RED);
                    $failedCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'failed',
                        'error' => 'Invalid product data',
                        'data' => $productData
                    ];
                    continue;
                }

                $this->log("✅ Successfully extracted data for: $url", self::COLOR_GREEN);
                $this->log("   Title: " . $productData['title'], self::COLOR_GRAY);
                $this->log("   Price: " . $productData['price'], self::COLOR_GRAY);
                $this->log("   Availability: " . ($productData['availability'] ? 'Available' : 'Unavailable'), self::COLOR_GRAY);

                $successCount++;
                $results[] = [
                    'url' => $url,
                    'status' => 'success',
                    'error' => null,
                    'data' => $productData
                ];

            } catch (\Exception $e) {
                $this->log("❌ Exception for $url: " . $e->getMessage(), self::COLOR_RED);
                $failedCount++;
                $results[] = [
                    'url' => $url,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'data' => null
                ];
            }

            // Add delay between tests
            $delay = mt_rand(1000, 3000);
            usleep($delay * 1000);
        }

        $this->log("🧪 Product Test Mode Complete", self::COLOR_PURPLE);
        $this->log("✅ Successful: $successCount", self::COLOR_GREEN);
        $this->log("❌ Failed: $failedCount", self::COLOR_RED);

        return [
            'status' => 'success',
            'total_products' => $successCount,
            'failed_links' => $failedCount,
            'test_results' => $results,
            'products' => array_column(array_filter($results, fn($r) => $r['status'] === 'success'), 'data')
        ];
    }

    private function retryFailedLinks(): void
    {
        $failedLinks = FailedLink::where('attempts', '<', 3)->get();

        if ($failedLinks->isEmpty()) {
            $this->log("No failed links to retry", self::COLOR_YELLOW);
            return;
        }

        $this->log("Retrying " . $failedLinks->count() . " failed links...", self::COLOR_PURPLE);

        foreach ($failedLinks as $failedLink) {
            try {
                $this->log("Retrying failed link (attempt {$failedLink->attempts}): {$failedLink->url}", self::COLOR_PURPLE);

                $body = $this->linkScraper->fetchPageContent($failedLink->url, false, true);

                if ($body === null) {
                    $failedLink->increment('attempts');
                    $failedLink->update(['error_message' => 'Failed to fetch content on retry']);
                    continue;
                }

                $productData = $this->productProcessor->extractProductData($failedLink->url, $body);

                if ($productData && $this->productProcessor->validateProductData($productData)) {
                    DB::beginTransaction();
                    try {
                        $this->productProcessor->saveProductToDatabase($productData);
                        $this->databaseManager->updateLinkProcessedStatus($failedLink->url);
                        $failedLink->delete();
                        DB::commit();

                        $this->processedCount++;
                        $this->log("✅ Successfully recovered failed link: {$failedLink->url}", self::COLOR_GREEN);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $failedLink->increment('attempts');
                        $failedLink->update(['error_message' => "Database error on retry: " . $e->getMessage()]);
                        $this->log("Failed to save recovered product: {$failedLink->url}", self::COLOR_RED);
                    }
                } else {
                    $failedLink->increment('attempts');
                    $failedLink->update(['error_message' => 'Invalid product data on retry']);
                }

                // Add delay between retry attempts
                usleep(mt_rand(1000, 2000) * 1000);

            } catch (\Exception $e) {
                $failedLink->increment('attempts');
                $failedLink->update(['error_message' => "Retry error: " . $e->getMessage()]);
                $this->log("Retry failed for {$failedLink->url}: {$e->getMessage()}", self::COLOR_RED);
            }
        }
    }

    private function saveFailedLink(string $url, string $errorMessage): void
    {
        try {
            $existingFailedLink = FailedLink::where('url', $url)->first();

            if ($existingFailedLink) {
                $existingFailedLink->increment('attempts');
                $existingFailedLink->update([
                    'error_message' => $errorMessage,
                    'updated_at' => now()
                ]);
            } else {
                FailedLink::create([
                    'url' => $url,
                    'attempts' => 1,
                    'error_message' => $errorMessage,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            $this->log("Failed to save failed link: $url - {$e->getMessage()}", self::COLOR_RED);
        }
    }

    private function isUnwantedDomain(string $url): bool
    {
        $unwantedDomains = $this->config['unwanted_domains'] ?? [];

        foreach ($unwantedDomains as $domain) {
            if (str_contains($url, $domain)) {
                return true;
            }
        }

        return false;
    }

    private function isInvalidLink(string $url): bool
    {
        // بررسی صحت URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        // بررسی لینک‌های جاوااسکریپت یا anchor
        if (str_starts_with($url, 'javascript:') || str_starts_with($url, '#')) {
            return true;
        }

        // بررسی فرمت‌های فایل غیرمجاز
        $invalidExtensions = ['.pdf', '.doc', '.docx', '.zip', '.rar', '.exe'];
        foreach ($invalidExtensions as $ext) {
            if (str_ends_with(strtolower($url), $ext)) {
                return true;
            }
        }

        return false;
    }

    private function randomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0'
        ];

        return $userAgents[array_rand($userAgents)];
    }

    public function handleOutput(string $message): void
    {
        if ($this->outputCallback) {
            call_user_func($this->outputCallback, $message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    private function log(string $message, ?string $color = null): void
    {
        $colorReset = self::COLOR_RESET;
        $formattedMessage = $color ? $color . $message . $colorReset : $message;

        // Log to file
        $logFile = storage_path('logs/scraper_' . date('Ymd') . '.log');
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);

        // Output to console/callback
        $this->handleOutput($formattedMessage);
    }
}
