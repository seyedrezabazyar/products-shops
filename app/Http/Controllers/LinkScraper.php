<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\DomCrawler\Crawler;

class LinkScraper
{
    private const COLOR_GREEN = "\033[1;92m";
    private const COLOR_RED = "\033[1;91m";
    private const COLOR_YELLOW = "\033[1;93m";
    private const COLOR_PURPLE = "\033[1;95m";
    private const COLOR_BLUE = "\033[1;94m";
    private ProductDataProcessor $productProcessor;

    private array $config;
    private Client $httpClient;
    private $outputCallback = null;

    public function __construct(array $config, Client $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;

        // اضافه کردن وابستگی به ProductDataProcessor
        $this->productProcessor = new ProductDataProcessor($config);
        $this->productProcessor->setOutputCallback([$this, 'handleOutput']);
    }
    public function handleOutput(string $message): void
    {
        if ($this->outputCallback) {
            call_user_func($this->outputCallback, $message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    public function setOutputCallback(callable $callback): void
    {
        $this->outputCallback = $callback;
    }

    public function fetchProductLinks(): array
    {
        $method = $this->config['method'] ?? 1;
        $this->log("🔄 STARTING fetchProductLinks - Method: $method", self::COLOR_GREEN);

        $this->log("📄 Config check - products_urls count: " . count($this->config['products_urls'] ?? []), self::COLOR_PURPLE);
        $this->log("📄 Config check - base_urls: " . json_encode($this->config['base_urls'] ?? []), self::COLOR_PURPLE);

        if (!isset($this->config['selectors']['main_page']['product_links'])) {
            throw new \Exception("Main page product_links selector is required.");
        }

        $productLinksSelector = $this->config['selectors']['main_page']['product_links'];
        if (is_array($productLinksSelector)) {
            $this->log("✅ Product links selector found (array): " . json_encode($productLinksSelector), self::COLOR_GREEN);
        } else {
            $this->log("✅ Product links selector found: " . $productLinksSelector, self::COLOR_GREEN);
        }

        $allLinks = [];
        $totalPagesProcessed = 0;
        $processedUrls = [];

        // برای روش ۳
        if ($method === 3) {
            $this->log("🎯 Using scrapeMethodThree for method 3...", self::COLOR_GREEN);
            $result = $this->scrapeMethodThree();
            $allLinks = $result['links'] ?? [];
            $totalPagesProcessed = $result['pages_processed'] ?? 0;
            $this->log("📊 Method 3 result - Links: " . count($allLinks) . ", Pages: $totalPagesProcessed", self::COLOR_GREEN);
            return [
                'links' => array_values($allLinks),
                'pages_processed' => $totalPagesProcessed
            ];
        }

        // پردازش اولیه برای روش‌های ۱ و ۲
        $this->log("🔄 Processing " . count($this->config['products_urls']) . " product URLs...", self::COLOR_PURPLE);

        foreach ($this->config['products_urls'] as $index => $productUrl) {
            $this->log("🌐 Processing URL " . ($index + 1) . "/" . count($this->config['products_urls']) . ": $productUrl", self::COLOR_PURPLE);

            $normalizedUrl = $this->normalizeUrl($productUrl);
            if (in_array($normalizedUrl, $processedUrls)) {
                $this->log("⚠️ Skipping duplicate products_url: $productUrl", self::COLOR_YELLOW);
                continue;
            }
            $processedUrls[] = $normalizedUrl;

            try {
                $this->log("🔗 Testing connection to: $productUrl", self::COLOR_PURPLE);
                $testContent = $this->fetchPageContent($productUrl, false, false);

                if ($testContent === null) {
                    $this->log("❌ CRITICAL: Cannot fetch content from $productUrl", self::COLOR_RED);
                    continue;
                }

                $this->log("✅ Connection successful - Content length: " . strlen($testContent), self::COLOR_GREEN);
                $this->log("📄 First 200 chars of content: " . substr($testContent, 0, 200), self::COLOR_YELLOW);

                $result = match ($method) {
                    1 => $this->scrapeMethodOneForUrl($productUrl),
                    2 => $this->scrapeWithPlaywright(2, $productUrl),
                    default => throw new \Exception("Invalid method: $method"),
                };

                $this->log("📊 Scrape result: " . json_encode([
                        'links_count' => count($result['links'] ?? []),
                        'pages_processed' => $result['pages_processed'] ?? 0
                    ]), self::COLOR_YELLOW);

                $rawLinks = $result['links'] ?? [];
                $pagesProcessed = $result['pages_processed'] ?? 0;
                $totalPagesProcessed += $pagesProcessed;

                $this->log("🔗 Found " . count($rawLinks) . " raw links from $productUrl", self::COLOR_GREEN);

                if (!empty($rawLinks)) {
                    $this->log("📋 Sample links: " . json_encode(array_slice($rawLinks, 0, 3)), self::COLOR_YELLOW);
                }

                foreach ($rawLinks as $link) {
                    $url = is_array($link) ? ($link['url'] ?? $link) : $link;
                    if ($url && !$this->isUnwantedDomain($url) && !in_array($url, array_column($allLinks, 'url'))) {
                        $allLinks[] = [
                            'url' => $url,
                            'sourceUrl' => $productUrl
                        ];
                    }
                }

                $this->log("📈 Total links so far: " . count($allLinks), self::COLOR_GREEN);

            } catch (\Exception $e) {
                $this->log("💥 ERROR processing $productUrl: " . $e->getMessage(), self::COLOR_RED);
                $this->log("📍 Stack trace: " . $e->getTraceAsString(), self::COLOR_RED);
            }
        }

        $this->log("🏁 FINAL RESULT - Total unique links: " . count($allLinks), self::COLOR_GREEN);

        if (empty($allLinks)) {
            $this->log("🚨 CRITICAL: NO LINKS FOUND AT ALL!", self::COLOR_RED);
        }

        return [
            'links' => array_values($allLinks),
            'pages_processed' => $totalPagesProcessed
        ];
    }

    public function fetchPageContent(string $url, bool $useDeep, bool $isProductPage = true): ?string
    {
        $this->log("🌐 FETCHING: $url", self::COLOR_PURPLE);

        $maxRetries = $this->config['max_retries'] ?? 3;
        $attempt = 1;

        while ($attempt <= $maxRetries) {
            $userAgent = $this->randomUserAgent();
            $this->log("🔄 Attempt $attempt/$maxRetries - UserAgent: " . substr($userAgent, 0, 50) . "...", self::COLOR_GREEN);

            try {
                $parsedUrl = parse_url($url);
                $host = $parsedUrl['host'] ?? 'unknown';
                $this->log("🔍 Testing DNS for host: $host", self::COLOR_PURPLE);

                $response = $this->httpClient->get($url, [
                    'allow_redirects' => [
                        'track_redirects' => true,
                        'max' => 5
                    ],
                    'verify' => $this->config['verify_ssl'] ?? false,
                    'timeout' => 30,
                    'connect_timeout' => 10,
                    'headers' => [
                        'User-Agent' => $userAgent,
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Referer' => $this->config['base_urls'][0] ?? '',
                        'Connection' => 'keep-alive',
                        'Cache-Control' => 'no-cache',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $this->log("✅ HTTP $statusCode - Content-Type: " . $response->getHeaderLine('Content-Type'), self::COLOR_GREEN);

                $contentLength = $response->getHeaderLine('Content-Length');
                $server = $response->getHeaderLine('Server');
                $this->log("📊 Server: $server, Content-Length: $contentLength", self::COLOR_YELLOW);

                $body = (string)$response->getBody();
                $bodyLength = strlen($body);
                $this->log("📄 Response body length: $bodyLength bytes", self::COLOR_GREEN);

                if (empty($body)) {
                    $this->log("⚠️ Empty response body for $url", self::COLOR_YELLOW);
                    $attempt++;
                    continue;
                }

                $lowercaseBody = strtolower(substr($body, 0, 1000));
                $suspiciousPatterns = ['cloudflare', 'captcha', 'access denied', 'blocked', 'forbidden'];

                foreach ($suspiciousPatterns as $pattern) {
                    if (strpos($lowercaseBody, $pattern) !== false) {
                        $this->log("🚨 Suspicious pattern detected: '$pattern' in response", self::COLOR_RED);
                    }
                }

                $this->log("✅ Successfully fetched content from $url", self::COLOR_GREEN);
                return $body;

            } catch (RequestException $e) {
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
                $responseBody = $e->hasResponse() ? substr((string)$e->getResponse()->getBody(), 0, 200) : 'No response';

                $this->log("❌ Request failed (Attempt $attempt): " . $e->getMessage(), self::COLOR_RED);
                $this->log("📊 Status: $statusCode, Response: $responseBody", self::COLOR_RED);

                if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
                    $this->log("🔌 Connection error - Check network/firewall/DNS", self::COLOR_RED);
                } elseif ($e instanceof \GuzzleHttp\Exception\ClientException) {
                    $this->log("👤 Client error (4xx) - Possible blocking/authentication issue", self::COLOR_RED);
                } elseif ($e instanceof \GuzzleHttp\Exception\ServerException) {
                    $this->log("🖥️ Server error (5xx) - Target server issue", self::COLOR_RED);
                }

                if ($attempt < $maxRetries) {
                    $delay = $this->exponentialBackoff($attempt);
                    $this->log("⏳ Retrying after $delay ms...", self::COLOR_YELLOW);
                    usleep($delay * 1000);
                }
                $attempt++;

            } catch (\Exception $e) {
                $this->log("💥 Unexpected error: " . $e->getMessage(), self::COLOR_RED);
                $this->log("📍 Exception type: " . get_class($e), self::COLOR_RED);
                return null;
            }
        }

        $this->log("🔴 FAILED to fetch $url after $maxRetries attempts", self::COLOR_RED);
        return null;
    }

    public function scrapeMethodOneForUrl(string $baseUrl): array
    {
        if ($this->config['method_settings']['method_1']['pagination']['use_webdriver']) {
            return $this->scrapeWithPlaywright(1);
        }

        $links = [];
        $currentPage = 1;
        $hasMorePages = true;
        $pagesProcessed = 0;
        $consecutiveEmptyPages = 0;

        while ($hasMorePages && $currentPage <= $this->config['method_settings']['method_1']['pagination']['max_pages']) {
            $pageUrl = $this->buildPaginationUrl($baseUrl, $currentPage, $this->config['method_settings']['method_1']['pagination']);
            $this->log("Fetching page: $pageUrl", self::COLOR_GREEN);
            $body = $this->fetchPageContent($pageUrl, false);

            if ($body === null) {
                $consecutiveEmptyPages++;
                $this->log("Failed to fetch page $currentPage for $baseUrl or page was redirected. Treating as empty page. Consecutive empty pages: $consecutiveEmptyPages", self::COLOR_YELLOW);
                $pagesProcessed++;

                if ($consecutiveEmptyPages >= 3) {
                    $this->log("Stopping pagination: 3 consecutive empty pages (including failed fetches) for $baseUrl.", self::COLOR_YELLOW);
                    $hasMorePages = false;
                    break;
                }

                $currentPage++;
                continue;
            }

            $crawler = new Crawler($body);
            $linkSelector = $this->config['selectors']['main_page']['product_links']['selector'];
            $imageSelector = $this->config['selectors']['main_page']['image']['selector'] ?? '';
            $productIdSelector = $this->config['selectors']['main_page']['product_id']['selector'] ?? '';
            $productIdAttribute = $this->config['selectors']['main_page']['product_id']['attribute'] ?? 'data-product_id';
            $productIdFromLink = $this->config['selectors']['main_page']['product_links']['product_id'] ?? false;
            $productIdSource = $this->config['product_id_source'] ?? 'main_page';
            $linksFound = $crawler->filter($linkSelector)->count();
            $this->log("page$currentPage -> $linksFound link find", self::COLOR_GREEN);

            if ($linksFound === 0) {
                $consecutiveEmptyPages++;
                $this->log("No products found on page $currentPage for $baseUrl. Consecutive empty pages: $consecutiveEmptyPages", self::COLOR_YELLOW);
                $htmlSnippet = substr($body, 0, 500);
                $this->log("HTML snippet of page $currentPage: $htmlSnippet", self::COLOR_YELLOW);

                if ($consecutiveEmptyPages >= 3) {
                    $this->log("Stopping pagination: 3 consecutive pages with no products found for $baseUrl.", self::COLOR_YELLOW);
                    $hasMorePages = false;
                    break;
                }

                $currentPage++;
                $pagesProcessed++;
                continue;
            }

            $consecutiveEmptyPages = 0;

            $crawler->filter($linkSelector)->each(function (Crawler $node, $index) use (&$links, $crawler, $imageSelector, $productIdSelector, $productIdAttribute, $productIdFromLink, $productIdSource) {
                $href = $node->attr($this->config['selectors']['main_page']['product_links']['attribute']);
                if ($this->isInvalidLink($href)) {
                    $this->log("Invalid link skipped: $href", self::COLOR_YELLOW);
                    return;
                }

                $fullUrl = $this->makeAbsoluteUrl($href);
                if ($this->isUnwantedDomain($fullUrl)) {
                    $this->log("Unwanted domain skipped: $fullUrl", self::COLOR_YELLOW);
                    return;
                }

                $linkData = ['url' => $fullUrl, 'image' => '', 'product_id' => ''];

                try {
                    $parentNode = $node->ancestors()->first();
                    if (!$parentNode->count()) {
                        $this->log("No parent node found for link: $fullUrl", self::COLOR_YELLOW);
                    } else {
                        $this->log("Parent node found for link: $fullUrl", self::COLOR_GREEN);
                    }

                    if ($imageSelector) {
                        $this->log("Trying image selector: $imageSelector", self::COLOR_YELLOW);
                        try {
                            $parentNodeHtml = $parentNode->count() ? $parentNode->html() : 'No parent node';
                            $this->log("Parent node HTML: " . substr($parentNodeHtml, 0, 500), self::COLOR_YELLOW);
                            $imageElement = $parentNode->filter($imageSelector);
                            $this->log("Image elements found: {$imageElement->count()}", self::COLOR_YELLOW);
                            if ($imageElement->count() > 0) {
                                $image = $imageElement->attr($this->config['selectors']['main_page']['image']['attribute'] ?? 'src');
                                $this->log("Raw image URL: $image", self::COLOR_YELLOW);
                                $linkData['image'] = $this->makeAbsoluteUrl($image);
                                $this->log("Extracted image from main page: {$linkData['image']} for $fullUrl", self::COLOR_GREEN);
                            } else {
                                $this->log("No image found with selector '$imageSelector' for $fullUrl", self::COLOR_YELLOW);
                            }
                        } catch (\Exception $e) {
                            $this->log("Error extracting image for $fullUrl: {$e->getMessage()}", self::COLOR_RED);
                        }
                    }

                    // Product ID extraction logic...
                    if ($productIdSource === 'product_links' && $productIdFromLink) {
                        try {
                            $productId = $node->attr($productIdFromLink);
                            $this->log("Raw product_id extracted from product_links: '$productId' for $fullUrl", self::COLOR_YELLOW);
                            if ($productId) {
                                $linkData['product_id'] = $productId;
                                $this->log("Extracted product_id from product_links: {$linkData['product_id']} for $fullUrl", self::COLOR_GREEN);
                            } else {
                                $this->log("No product_id found in product_links attribute '$productIdFromLink' for $fullUrl", self::COLOR_YELLOW);
                            }
                        } catch (\Exception $e) {
                            $this->log("Error extracting product_id from product_links for $fullUrl: {$e->getMessage()}", self::COLOR_RED);
                        }
                    } elseif ($productIdSource === 'main_page') {
                        if ($productIdFromLink) {
                            try {
                                $productId = $node->attr($productIdFromLink);
                                if ($productId) {
                                    $linkData['product_id'] = $productId;
                                    $this->log("Extracted product_id from link attribute '$productIdFromLink': {$linkData['product_id']} for $fullUrl", self::COLOR_GREEN);
                                } else {
                                    $this->log("No product_id found with link attribute '$productIdFromLink' for $fullUrl", self::COLOR_YELLOW);
                                }
                            } catch (\Exception $e) {
                                $this->log("Error extracting product_id from link for $fullUrl: {$e->getMessage()}", self::COLOR_RED);
                            }
                        }

                        if (!$linkData['product_id'] && $productIdSelector) {
                            try {
                                $productIdElements = $crawler->filter($productIdSelector);
                                if ($productIdElements->count() > 0) {
                                    $productId = $productIdElements->attr($productIdAttribute);
                                    if ($productId) {
                                        $linkData['product_id'] = $productId;
                                        $this->log("Extracted product_id from selector '$productIdSelector': {$linkData['product_id']} for $fullUrl", self::COLOR_GREEN);
                                    } else {
                                        $this->log("No product_id found with selector '$productIdSelector' for $fullUrl", self::COLOR_YELLOW);
                                    }
                                } else {
                                    $this->log("No elements found with product_id selector '$productIdSelector' for $fullUrl", self::COLOR_YELLOW);
                                    $ancestorWithId = $node->ancestors()->filter($productIdSelector)->first();
                                    if ($ancestorWithId->count() > 0) {
                                        $productId = $ancestorWithId->attr($productIdAttribute);
                                        $linkData['product_id'] = $productId;
                                        $this->log("Extracted product_id from ancestor: {$linkData['product_id']} for $fullUrl", self::COLOR_GREEN);
                                    } else {
                                        $this->log("No product_id found in ancestors with selector '$productIdSelector' for $fullUrl", self::COLOR_YELLOW);
                                    }
                                }
                            } catch (\Exception $e) {
                                $this->log("Error extracting product_id for $fullUrl: {$e->getMessage()}", self::COLOR_RED);
                            }
                        }
                    }

                    $links[] = $linkData;
                    $this->log("Added link: $fullUrl", self::COLOR_GREEN);
                } catch (\Exception $e) {
                    $this->log("Error processing node for $fullUrl: {$e->getMessage()}", self::COLOR_RED);
                }
            });

            $pagesProcessed++;
            $currentPage++;
        }

        return [
            'links' => array_unique($links, SORT_REGULAR),
            'pages_processed' => $pagesProcessed
        ];
    }

    public function scrapeWithPlaywright(int $method, string $productUrl = ''): array
    {
        if ($method !== 2) {
            $this->log("Playwright is only supported for method 2", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        $this->log("Starting Playwright scraping process for URL: $productUrl...", self::COLOR_GREEN);

        // تنظیم تایم‌اوت و حافظه
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // مقادیر کانفیگ
        $config = $this->config;
        $maxPages = $config['method_settings']['method_2']['navigation']['max_pages'] ?? 10;
        $scrollDelay = $config['method_settings']['method_2']['navigation']['scroll_delay'] ?? 3000;
        $paginationMethod = $config['method_settings']['method_2']['navigation']['pagination']['method'] ?? 'url';
        $this->log("Pagination method: $paginationMethod", self::COLOR_YELLOW);

        // Selectorها و تنظیمات
        $linkSelector = addslashes($config['selectors']['main_page']['product_links']['selector'] ?? '');
        $linkAttribute = addslashes($config['selectors']['main_page']['product_links']['attribute'] ?? 'href');
        $imageSelector = addslashes($config['selectors']['main_page']['image']['selector'] ?? '');
        $imageAttribute = addslashes($config['selectors']['main_page']['image']['attribute'] ?? 'src');
        $productIdSelector = addslashes($config['selectors']['main_page']['product_id']['selector'] ?? '');
        $productIdAttribute = addslashes($config['selectors']['main_page']['product_id']['attribute'] ?? 'data-product_id');
        $productIdFromLink = addslashes($config['selectors']['main_page']['product_links']['product_id'] ?? '');
        $imageMethod = addslashes($config['image_method'] ?? 'main_page');
        $productIdSource = addslashes($config['product_id_source'] ?? 'main_page');
        $productIdMethod = addslashes($config['product_id_method'] ?? 'selector');
        $urlFilter = addslashes($config['selectors']['main_page']['product_links']['url_filter'] ?? '');
        $userAgent = addslashes($this->randomUserAgent());
        $container = addslashes($config['container'] ?? '');
        $baseUrl = addslashes($config['base_urls'][0] ?? '');

        // تنظیمات صفحه‌بندی
        $paginationConfig = $config['method_settings']['method_2']['navigation']['pagination']['url'] ?? [];
        $paginationType = addslashes($paginationConfig['type'] ?? 'query');
        $paginationParam = addslashes($paginationConfig['parameter'] ?? 'page');
        $paginationSeparator = addslashes($paginationConfig['separator'] ?? '=');
        $paginationSuffix = addslashes($paginationConfig['suffix'] ?? '');
        $useSampleUrl = $paginationConfig['use_sample_url'] ?? false;
        $sampleUrl = addslashes($paginationConfig['sample_url'] ?? '');
        $forceTrailingSlash = $paginationConfig['force_trailing_slash'] ?? false;
        $paginationConfigJson = json_encode($paginationConfig, JSON_UNESCAPED_SLASHES);

        // تنظیمات دکمه Next
        $nextButtonSelector = '';
        if ($paginationMethod === 'next_button') {
            $nextButtonSelector = addslashes($config['method_settings']['method_2']['navigation']['pagination']['next_button']['selector'] ?? '');
            $this->log("Next button selector: $nextButtonSelector", self::COLOR_YELLOW);
            if (empty($nextButtonSelector)) {
                $this->log("Next button selector is required for pagination method 'next_button'", self::COLOR_RED);
                return ['links' => [], 'pages_processed' => 0];
            }
        }

        // اسکریپت Playwright - مشابه متد 3 که کار می‌کند
        $playwrightScript = <<<'JAVASCRIPT'
const { chromium } = require('playwright');

(async () => {
    let links = [];
    let pagesProcessed = 0;
    let consoleLogs = [];

    try {
        console.log('Launching browser...');
        const browser = await chromium.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        console.log('Browser launched successfully.');

        const context = await browser.newContext({
            userAgent: USER_AGENT,
            extraHTTPHeaders: {
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.5',
                'Accept-Encoding': 'gzip, deflate, br',
                'Connection': 'keep-alive'
            }
        });
        const page = await context.newPage();
        console.log('Browser context and page created successfully.');

        page.on('console', (msg) => {
            consoleLogs.push(`[Console ${msg.type()}] ${msg.text()}`);
        });

        const paginationConfig = PAGINATION_CONFIG;
        const buildPaginationUrl = (baseUrl, pageNum) => {
            let url = baseUrl.replace(/\/$/, '');
            const config = paginationConfig;
            const param = PAGINATION_PARAM;
            const separator = PAGINATION_SEPARATOR;
            const type = PAGINATION_TYPE;
            const suffix = PAGINATION_SUFFIX;
            const useSampleUrl = USE_SAMPLE_URL;
            const sampleUrl = SAMPLE_URL;
            const forceTrailingSlash = FORCE_TRAILING_SLASH;

            if (useSampleUrl && sampleUrl && pageNum > 1) {
                const pattern = sampleUrl.replace(new RegExp(`${param}${separator}\\d+`), `${param}${separator}${pageNum}`);
                return pattern;
            }

            if (pageNum === 1 && !suffix) {
                return forceTrailingSlash && type === 'path' ? `${url}/` : url;
            }

            if (type === 'query') {
                return `${url}?${param}${separator}${pageNum}${suffix}`;
            } else if (type === 'path') {
                return forceTrailingSlash ? `${url}/${param}${separator}${pageNum}${suffix}/` : `${url}/${param}${separator}${pageNum}${suffix}`;
            }
            return `${url}?page=${pageNum}`;
        };

        const maxPages = MAX_PAGES;
        const linkSelector = LINK_SELECTOR;
        const linkAttribute = LINK_ATTRIBUTE;
        const imageSelector = IMAGE_SELECTOR;
        const imageAttribute = IMAGE_ATTRIBUTE;
        const productIdSelector = PRODUCT_ID_SELECTOR;
        const productIdAttribute = PRODUCT_ID_ATTRIBUTE;
        const productIdFromLink = PRODUCT_ID_FROM_LINK;
        const imageMethod = IMAGE_METHOD;
        const productIdSource = PRODUCT_ID_SOURCE;
        const productIdMethod = PRODUCT_ID_METHOD;
        const scrollDelay = SCROLL_DELAY;
        const urlFilter = URL_FILTER ? new RegExp(URL_FILTER) : null;
        const container = CONTAINER;
        const baseUrl = BASE_URL;
        const paginationMethod = PAGINATION_METHOD;
        const nextButtonSelector = NEXT_BUTTON_SELECTOR;
        let currentPage = 1;
        let hasMorePages = true;

        console.log(`Navigating to URL: PRODUCTS_URL`);
        await page.goto(PRODUCTS_URL, { waitUntil: 'domcontentloaded', timeout: 120000 });

        while (hasMorePages && currentPage <= maxPages) {
            let pageUrl = paginationMethod === 'url' ? buildPaginationUrl(PRODUCTS_URL, currentPage) : PRODUCTS_URL;
            console.log(`Processing page ${currentPage} at URL: ${pageUrl}...`);

            if (paginationMethod === 'url' || (paginationMethod === 'next_button' && currentPage === 1)) {
                try {
                    await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 120000 });
                } catch (error) {
                    console.log(`Failed to load page ${currentPage}: ${error.message}`);
                    hasMorePages = false;
                    break;
                }
            }

            // Wait for container if specified
            if (container && container.trim() !== '') {
                await page.waitForSelector(container, { timeout: 10000 }).catch(() => {
                    console.log('Products container not found, continuing...');
                });
            }

            // Scroll page for better content loading
            for (let i = 0; i < 3; i++) {
                await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
                await page.waitForTimeout(scrollDelay);
                console.log(`Scroll ${i + 1} completed`);
            }

            // Wait for product links to load
            await page.waitForFunction(
                (selector) => document.querySelectorAll(selector).length > 0,
                linkSelector,
                { timeout: 20000 }
            ).catch(() => {
                console.log('No links found after waiting, continuing...');
            });

            const currentPageLinks = await page.evaluate((args) => {
                const {
                    linkSel,
                    linkAttr,
                    imageSel,
                    imageAttr,
                    productIdSel,
                    productIdAttr,
                    productIdFromLink,
                    imageMethod,
                    productIdSource,
                    productIdMethod,
                    urlFilter,
                    container,
                    baseUrl
                } = args;
                const links = [];
                const elements = document.querySelectorAll(linkSel);
                console.log(`Found ${elements.length} elements with selector: ${linkSel}`);

                elements.forEach(node => {
                    let href = node.getAttribute(linkAttr);
                    if (href && !href.startsWith('javascript:') && !href.startsWith('#') && (!urlFilter || urlFilter.test(href))) {
                        const fullUrl = href.startsWith('http') ? href : new URL(href, baseUrl).href;
                        let image = '';
                        if (imageSel && imageMethod === 'main_page') {
                            const parent = container ? node.closest(container) : node.closest('div');
                            const imageElement = parent ? parent.querySelector(imageSel) : null;
                            image = imageElement ? imageElement.getAttribute(imageAttr) : '';
                        }
                        let productId = '';
                        if (productIdSource === 'product_links' && productIdFromLink) {
                            productId = node.getAttribute(productIdFromLink) || 'unknown';
                        } else if (productIdSource === 'main_page' && productIdSel) {
                            const parent = container ? node.closest(container) : node.closest('div');
                            const productIdElement = parent ? parent.querySelector(productIdSel) : null;
                            productId = productIdElement ? productIdElement.getAttribute(productIdAttr) : 'unknown';
                        } else if (productIdMethod === 'url') {
                            const match = href.match(/product\/([A-Za-z0-9]+)/);
                            productId = match ? match[1] : 'unknown';
                        } else {
                            productId = 'unknown';
                        }
                        links.push({ url: fullUrl, image, product_id: productId });
                    }
                });
                return links;
            }, {
                linkSel: linkSelector,
                linkAttr: linkAttribute,
                imageSel: imageSelector,
                imageAttr: imageAttribute,
                productIdSel: productIdSelector,
                productIdAttr: productIdAttribute,
                productIdFromLink: productIdFromLink,
                imageMethod: imageMethod,
                productIdSource: productIdSource,
                productIdMethod: productIdMethod,
                urlFilter: urlFilter,
                container: container,
                baseUrl: baseUrl
            });

            console.log(`Found ${currentPageLinks.length} links on page ${currentPage}`);
            links.push(...currentPageLinks);
            pagesProcessed++;
            currentPage++;

            if (paginationMethod === 'next_button' && hasMorePages) {
                try {
                    await page.waitForSelector(nextButtonSelector, { timeout: 10000 });
                    const nextButton = await page.$(nextButtonSelector);
                    if (nextButton) {
                        const isButtonEnabled = await page.evaluate((selector) => {
                            const btn = document.querySelector(selector);
                            return btn && !btn.disabled && btn.offsetParent !== null;
                        }, nextButtonSelector);

                        if (!isButtonEnabled) {
                            console.log('Next button is disabled or not visible. Stopping pagination.');
                            hasMorePages = false;
                            break;
                        }

                        await nextButton.scrollIntoViewIfNeeded();
                        await nextButton.click();
                        await page.waitForTimeout(5000); // Wait for new content to load
                    } else {
                        console.log('Next button not found. Stopping pagination.');
                        hasMorePages = false;
                    }
                } catch (error) {
                    console.log(`Failed to click next button: ${error.message}`);
                    hasMorePages = false;
                }
            } else if (paginationMethod === 'url') {
                await page.waitForTimeout(3000);
            }
        }

        // Remove duplicates
        const uniqueLinks = links.filter((link, index, self) =>
            index === self.findIndex(l => l.url === link.url)
        );

        await browser.close();
        console.log(JSON.stringify({ links: uniqueLinks, pagesProcessed, consoleLogs }));
    } catch (error) {
        console.error('Error in Playwright script:', error.message);
        console.log(JSON.stringify({ links: [], pagesProcessed, consoleLogs, error: error.message }));
    }
})();
JAVASCRIPT;

        // جایگذاری مقادیر - مشابه متد 3
        $playwrightScript = str_replace(
            [
                'USER_AGENT', 'PRODUCTS_URL', 'MAX_PAGES', 'LINK_SELECTOR', 'LINK_ATTRIBUTE',
                'IMAGE_SELECTOR', 'IMAGE_ATTRIBUTE', 'PRODUCT_ID_SELECTOR', 'PRODUCT_ID_ATTRIBUTE',
                'PRODUCT_ID_FROM_LINK', 'IMAGE_METHOD', 'PRODUCT_ID_SOURCE', 'PRODUCT_ID_METHOD',
                'URL_FILTER', 'CONTAINER', 'BASE_URL', 'SCROLL_DELAY', 'PAGINATION_CONFIG',
                'PAGINATION_TYPE', 'PAGINATION_PARAM', 'PAGINATION_SEPARATOR', 'PAGINATION_SUFFIX',
                'USE_SAMPLE_URL', 'SAMPLE_URL', 'FORCE_TRAILING_SLASH', 'PAGINATION_METHOD', 'NEXT_BUTTON_SELECTOR'
            ],
            [
                json_encode($userAgent), json_encode($productUrl), $maxPages, json_encode($linkSelector), json_encode($linkAttribute),
                json_encode($imageSelector), json_encode($imageAttribute), json_encode($productIdSelector), json_encode($productIdAttribute),
                json_encode($productIdFromLink), json_encode($imageMethod), json_encode($productIdSource), json_encode($productIdMethod),
                json_encode($urlFilter), json_encode($container), json_encode($baseUrl), $scrollDelay, $paginationConfigJson,
                json_encode($paginationType), json_encode($paginationParam), json_encode($paginationSeparator), json_encode($paginationSuffix),
                $useSampleUrl ? 'true' : 'false', json_encode($sampleUrl), $forceTrailingSlash ? 'true' : 'false',
                json_encode($paginationMethod), json_encode($nextButtonSelector)
            ],
            $playwrightScript
        );

        // ذخیره اسکریپت موقت - مشابه متد 3
        $tempFileBase = tempnam(sys_get_temp_dir(), 'playwright_method2_');
        $tempFile = $tempFileBase . '.cjs';
        rename($tempFileBase, $tempFile);
        file_put_contents($tempFile, $playwrightScript);
        $this->log("Temporary script file created at: $tempFile", self::COLOR_GREEN);

        // اجرای اسکریپت - مسیر درست مشابه متد 3
        $nodeModulesPath = base_path('node_modules');
        $this->log("Executing Playwright script: NODE_PATH=$nodeModulesPath node $tempFile", self::COLOR_GREEN);

        $command = "NODE_PATH=$nodeModulesPath node $tempFile 2>&1";
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            $this->log("Failed to start Playwright script process.", self::COLOR_RED);
            unlink($tempFile);
            return ['links' => [], 'pages_processed' => 0];
        }

        $output = '';
        $errorOutput = '';
        $logFile = storage_path('logs/playwright_method2_' . date('Ymd_His') . '.log');

        // Read stdout
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output .= $line;
                if (!preg_match('/^\s*\{.*"links":.*\}/', $line)) {
                    $this->log("Playwright output: " . trim($line), self::COLOR_YELLOW);
                }
                if (file_exists(dirname($logFile))) {
                    file_put_contents($logFile, "[STDOUT] " . trim($line) . PHP_EOL, FILE_APPEND);
                }
            }
        }

        // Read stderr
        while (!feof($pipes[2])) {
            $errorLine = fgets($pipes[2]);
            if ($errorLine !== false) {
                $errorOutput .= $errorLine;
                $this->log("Playwright error: " . trim($errorLine), self::COLOR_RED);
                if (file_exists(dirname($logFile))) {
                    file_put_contents($logFile, "[STDERR] " . trim($errorLine) . PHP_EOL, FILE_APPEND);
                }
            }
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        $this->log("Playwright script execution completed with return code: $returnCode", self::COLOR_GREEN);

        // Clean up temp file
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        // بررسی خطاها
        if (!empty($errorOutput)) {
            $this->log("Playwright errors detected: {$errorOutput}", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        // تجزیه خروجی - مشابه متد 3
        preg_match('/\{.*\}/s', $output, $matches);
        if (!isset($matches[0])) {
            $this->log("Failed to parse Playwright output for {$productUrl}. Raw output: {$output}", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        $result = json_decode($matches[0], true);
        if (!$result || !isset($result['links'])) {
            $this->log("Invalid Playwright output format for {$productUrl}.", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        // لاگ کردن console logs
        if (isset($result['console_logs']) && is_array($result['console_logs'])) {
            foreach ($result['console_logs'] as $log) {
                $this->log("Playwright console log: {$log}", self::COLOR_YELLOW);
            }
        }

        // پردازش لینک‌ها - استفاده از وابستگی‌ها
        $links = array_map(function ($link) use ($productUrl) {
            $url = $this->makeAbsoluteUrl($link['url'], $productUrl);
            $productId = $link['product_id'] ?? '';

            if ($productId === '' && ($this->config['product_id_method'] ?? 'selector') === 'url') {
                // استفاده از متد محلی این کلاس
                $productId = $this->extractProductIdFromUrl($url);
                $this->log("Extracted product_id from URL: {$productId} for {$url}", self::COLOR_GREEN);
            }

            return [
                'url' => $url,
                'image' => $link['image'] ? $this->makeAbsoluteUrl($link['image'], $productUrl) : '',
                'product_id' => $productId
            ];
        }, $result['links']);

        $this->log("Found " . count($links) . " unique links for {$productUrl}.", self::COLOR_GREEN);

        return [
            'links' => array_unique($links, SORT_REGULAR),
            'pages_processed' => $result['pages_processed'] ?? 0
        ];
    }
    public function scrapeMethodThree(): array
    {
        $this->log("Starting scrapeMethodThree...", self::COLOR_GREEN);
        $allLinks = [];
        $totalPagesProcessed = 0;
        $processedUrls = [];

        if (!$this->config['method_settings']['method_3']['enabled']) {
            $this->log("Method 3 is not enabled in config.", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        if (!$this->config['method_settings']['method_3']['navigation']['use_webdriver']) {
            $this->log("Method 3 requires a WebDriver (use_webdriver must be true).", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        // حلقه روی تمام products_urls
        foreach ($this->config['products_urls'] as $index => $productsUrl) {
            $normalizedUrl = $this->normalizeUrl($productsUrl);
            if (in_array($normalizedUrl, $processedUrls)) {
                $this->log("Skipping duplicate products_url: $productsUrl", self::COLOR_YELLOW);
                continue;
            }
            $processedUrls[] = $normalizedUrl;

            $this->log("Processing products_url " . ($index + 1) . ": $productsUrl", self::COLOR_PURPLE);

            // تنظیمات پیکربندی
            $baseurl = json_encode($this->config['base_urls'][0] ?? '');
            $scrool = json_encode($this->config['scrool'] ?? '');
            $userAgent = json_encode($this->config['user_agent'][0] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124');
            $linkSelector = json_encode($this->config['selectors']['main_page']['product_links']['selector'] ?? 'a[href*="/product"]');
            $linkAttribute = json_encode($this->config['selectors']['main_page']['product_links']['attribute'] ?? 'href');
            $maxPages = $this->config['method_settings']['method_3']['navigation']['max_iterations'] ?? 5;
            $scrollDelay = $this->config['method_settings']['method_3']['navigation']['timing']['scroll_delay'] ?? 3000;
            $positiveKeywords = json_encode($this->config['availability_keywords']['positive'] ?? []);
            $negativeKeywords = json_encode($this->config['availability_keywords']['negative'] ?? []);

            // سلکتورهای صفحه محصول
            $titleSelector = json_encode($this->config['selectors']['product_page']['title']['selector'] ?? '.styles__title___3F4_f');
            $priceSelector = json_encode($this->config['selectors']['product_page']['price']['selector'] ?? '.styles__final-price___1L1AM');
            $availabilitySelector = json_encode($this->config['selectors']['product_page']['availability']['selector'] ?? '#buy-button');
            $imageSelector = json_encode($this->config['selectors']['product_page']['image']['selector'] ?? 'img.styles__slide___1r6T7');
            $imageAttribute = json_encode($this->config['selectors']['product_page']['image']['attribute'] ?? 'src');
            $categorySelector = json_encode($this->config['selectors']['product_page']['category']['selector'] ?? 'a.styles__bread-crumb-item___3xa5Q:nth-child(3)');
            $guaranteeSelector = json_encode($this->config['selectors']['product_page']['guarantee']['selector'] ?? '');
            $productIdSelector = json_encode($this->config['selectors']['product_page']['product_id']['selector'] ?? 'head > meta:nth-child(9)');
            $productIdAttribute = json_encode($this->config['selectors']['product_page']['product_id']['attribute'] ?? 'content');

            // اضافه کردن تنظیمات product_id_method
            $productIdMethod = json_encode($this->config['product_id_method'] ?? 'selector');
            $productIdSource = json_encode($this->config['product_id_source'] ?? 'selector');

            // تنظیمات صفحه‌بندی
            $paginationConfig = $this->config['method_settings']['method_3']['navigation']['pagination'] ?? [];
            $paginationMethod = json_encode($paginationConfig['method'] ?? 'next_button');
            $paginationConfigJson = json_encode($paginationConfig);

            // اعتبارسنجی
            if (empty($productsUrl)) {
                $this->log("Error: Products URL is empty for index $index.", self::COLOR_RED);
                continue;
            }
            if (empty(json_decode($linkSelector, true))) {
                $this->log("Error: Link selector is not defined in the config.", self::COLOR_RED);
                continue;
            }
            if (json_decode($paginationMethod) === 'next_button' && empty($paginationConfig['next_button']['selector'])) {
                $this->log("Error: Next button selector is required when pagination method is 'next_button'.", self::COLOR_RED);
                continue;
            }
            if (json_decode($paginationMethod) === 'url' && empty($paginationConfig['url'])) {
                $this->log("Error: URL pagination settings are required when pagination method is 'url'.", self::COLOR_RED);
                continue;
            }

            // اسکریپت Playwright برای Method 3
            $playwrightScript = <<<'JAVASCRIPT'
const { chromium } = require('playwright');

(async () => {
    let allLinks = [];
    let allProducts = [];
    let pagesProcessed = 0;
    let consoleLogs = [];
    let browser = null;
    let context = null;
    let page = null;
    let pageNumber = 1;
    const seenLinks = new Set();

    const initializeBrowser = async () => {
        console.log('Launching headless Chrome browser...');
        browser = await chromium.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--disable-extensions']
        });

        console.log('Creating new browser context...');
        context = await browser.newContext({
            userAgent: USER_AGENT,
            viewport: { width: 1920, height: 1080 },
            bypassCSP: true
        });

        console.log('Creating new page...');
        page = await context.newPage();

        browser.on('disconnected', () => {
            console.log('Browser disconnected unexpectedly.');
            consoleLogs.push('Browser disconnected unexpectedly.');
        });
    };

    const closeBrowser = async () => {
        if (browser) {
            console.log('Closing browser...');
            await browser.close().catch((e) => console.log(`Error closing browser: ${e.message}`));
            browser = null;
            console.log('Browser closed.');
        }
    };

    const buildPaginationUrl = (baseUrl, pageNum) => {
        let url = baseUrl;
        const paginationConfig = PAGINATION_CONFIG;
        if (paginationConfig.method === 'url') {
            const urlConfig = paginationConfig.url;
            const param = urlConfig.parameter || 'page';
            const separator = urlConfig.separator || '=';
            const type = urlConfig.type || 'query';
            const suffix = urlConfig.suffix || '';
            const useSampleUrl = urlConfig.use_sample_url || false;
            const sampleUrl = urlConfig.sample_url || '';

            if (useSampleUrl && sampleUrl && pageNum > 1) {
                const pattern = sampleUrl.replace(new RegExp(`${param}${separator}\\d+`), `${param}${separator}${pageNum}`);
                return pattern;
            }

            baseUrl = baseUrl.replace(/\/$/, '');
            if (pageNum === 1 && !suffix) return baseUrl;

            if (type === 'query') {
                return `${baseUrl}?${param}${separator}${pageNum}${suffix}`;
            } else if (type === 'path') {
                return `${baseUrl}/${param}${separator}${pageNum}${suffix}`;
            }
        }
        return `${baseUrl}?page=${pageNum}`;
    };

    const scrollPage = async () => {
        for (let i = 0; i < 'SCROOL'; i++) {
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
            console.log(`Scroll ${i + 1} completed.`);
            await page.waitForTimeout(SCROLL_DELAY);
        }
    };

    const extractLinks = async () => {
        console.log('Waiting for product links to appear (max 30s)...');
        await page.waitForSelector(LINK_SELECTOR, { timeout: 30000 }).catch((e) => {
            console.log(`Error waiting for links: ${e.message}`);
            consoleLogs.push(`Error waiting for links on page ${pageNumber}: ${e.message}`);
        });

        const links = await page.$$eval(LINK_SELECTOR, (elements, linkAttribute) => {
            const linkData = [];
            elements.forEach((element, index) => {
                let href = element.getAttribute(linkAttribute);
                if (href) {
                    if (href.endsWith(':')) {
                        href = href.slice(0, -1);
                    }
                    linkData.push(href);
                    console.log(`Found link ${index + 1}: ${href}`);
                }
            });
            return linkData;
        }, LINK_ATTRIBUTE);

        const newLinks = [];
        for (const link of links) {
            if (!seenLinks.has(link)) {
                seenLinks.add(link);
                newLinks.push(link);
                console.log(`Added new link: ${link}`);
            } else {
                console.log(`Skipped duplicate link: ${link}`);
                consoleLogs.push(`Skipped duplicate link on page ${pageNumber}: ${link}`);
            }
        }

        console.log(`Extracted ${newLinks.length} new product links from page ${pageNumber}.`);
        return newLinks;
    };

    const processProduct = async (link, index) => {
        const absoluteLink = link.startsWith('http') ? link : `${BASEURL}${link}`;
        console.log(`Processing link ${index + 1}: ${absoluteLink}`);

        let productData = {
            url: absoluteLink,
            title: '',
            price: '',
            availability: '',
            image: '',
            category: '',
            guarantee: '',
            product_id: '',
            error: ''
        };

        try {
            console.log(`Navigating to ${absoluteLink}...`);
            await page.goto(absoluteLink, { waitUntil: 'domcontentloaded', timeout: 120000 });
            console.log('Product page navigation completed.');

            console.log('Waiting for title selector...');
            await page.waitForSelector(TITLE_SELECTOR, { timeout: 20000 }).catch((e) => {
                console.log(`Title selector not found: ${e.message}`);
            });
            productData.title = await page.evaluate((selector) => {
                const element = document.querySelector(selector);
                return element ? element.textContent.trim() : '';
            }, TITLE_SELECTOR);
            console.log(`Extracted title: ${productData.title}`);

            console.log('Waiting for price selector...');
            await page.waitForSelector(PRICE_SELECTOR, { timeout: 20000 }).catch((e) => {
                console.log(`Price selector not found: ${e.message}`);
            });
            productData.price = await page.evaluate((selector) => {
                const element = document.querySelector(selector);
                return element ? element.textContent.trim() : '';
            }, PRICE_SELECTOR);
            console.log(`Extracted price: ${productData.price}`);

            console.log('Waiting for availability selector...');
            let availabilityValue = 0;
            try {
                await page.waitForSelector(AVAILABILITY_SELECTOR, { timeout: 50000 });
                const availabilityText = await page.evaluate((selector) => {
                    const element = document.querySelector(selector);
                    return element ? element.textContent.trim() : '';
                }, AVAILABILITY_SELECTOR);

                console.log(`Raw availability text: ${availabilityText}`);
                const positiveKeywords = POSITIVE_KEYWORDS;
                const negativeKeywords = NEGATIVE_KEYWORDS;

                if (positiveKeywords.some(keyword => availabilityText.includes(keyword))) {
                    availabilityValue = 1;
                    console.log(`Product is available based on positive keyword: ${availabilityText}`);
                } else if (negativeKeywords.some(keyword => availabilityText.includes(keyword))) {
                    availabilityValue = 0;
                    console.log(`Product is out of stock based on negative keyword: ${availabilityText}`);
                } else {
                    console.log(`No matching availability keywords found for text: ${availabilityText}, defaulting to unavailable`);
                }
            } catch (e) {
                console.log(`Availability selector not found: ${e.message}`);
                availabilityValue = 0;
            }
            console.log(`Final availability value: ${availabilityValue}`);
            productData.availability = availabilityValue;

            console.log('Waiting for image selector...');
            await page.waitForSelector(IMAGE_SELECTOR, { timeout: 20000 }).catch((e) => {
                console.log(`Image selector not found: ${e.message}`);
            });
            productData.image = await page.evaluate(({ selector, attr }) => {
                const element = document.querySelector(selector);
                return element ? element.getAttribute(attr) : '';
            }, { selector: IMAGE_SELECTOR, attr: IMAGE_ATTRIBUTE });
            console.log(`Extracted image: ${productData.image}`);

            console.log('Waiting for category selector...');
            await page.waitForSelector(CATEGORY_SELECTOR, { timeout: 20000 }).catch((e) => {
                console.log(`Category selector not found: ${e.message}`);
            });
            productData.category = await page.evaluate((selector) => {
                const element = document.querySelector(selector);
                return element ? element.textContent.trim() : '';
            }, CATEGORY_SELECTOR);
            console.log(`Extracted category: ${productData.category}`);

            if (GUARANTEE_SELECTOR && GUARANTEE_SELECTOR.trim() !== '') {
                console.log('Waiting for guarantee selector...');
                await page.waitForSelector(GUARANTEE_SELECTOR, { timeout: 20000 }).catch((e) => {
                    console.log(`Guarantee selector not found: ${e.message}`);
                });
                productData.guarantee = await page.evaluate((selector) => {
                    const element = document.querySelector(selector);
                    return element ? element.textContent.trim() : '';
                }, GUARANTEE_SELECTOR);
                console.log(`Extracted guarantee: ${productData.guarantee}`);
            } else {
                console.log('No guarantee selector provided. Skipping guarantee extraction.');
                productData.guarantee = '';
            }

            console.log('Extracting product_id...');

            if (PRODUCT_ID_METHOD === 'url' || PRODUCT_ID_SOURCE === 'url') {
                console.log('Using URL method for product_id extraction...');
                const urlPattern = /product\/(\d+)/;
                const match = absoluteLink.match(urlPattern);
                productData.product_id = match ? match[1] : '';
                console.log(`Extracted product_id from URL: ${productData.product_id}`);
            } else if (PRODUCT_ID_SELECTOR && PRODUCT_ID_SELECTOR.trim() !== '') {
                console.log('Using selector method for product_id extraction...');
                console.log('Waiting for product_id selector...');
                await page.waitForSelector(PRODUCT_ID_SELECTOR, { timeout: 5000 }).catch((e) => {
                    console.log(`Product ID selector not found: ${e.message}`);
                });
                productData.product_id = await page.evaluate(({ selector, attr }) => {
                    const element = document.querySelector(selector);
                    return element ? element.getAttribute(attr) || element.textContent.trim() : '';
                }, { selector: PRODUCT_ID_SELECTOR, attr: PRODUCT_ID_ATTRIBUTE });
                console.log(`Extracted product_id from selector: ${productData.product_id}`);
            } else {
                console.log('No product_id method specified, trying URL fallback...');
                const urlPattern = /product\/(\d+)/;
                const match = absoluteLink.match(urlPattern);
                productData.product_id = match ? match[1] : '';
                console.log(`Extracted product_id from URL (fallback): ${productData.product_id}`);
            }

            allProducts.push(productData);
            console.log(`Processed product ${index + 1}: ${absoluteLink}`);

            const randomDelay = Math.floor(Math.random() * (5000 - 3000 + 1)) + 3000;
            await page.waitForTimeout(randomDelay);

        } catch (error) {
            console.error(`Error processing ${absoluteLink}: ${error.message}`);
            consoleLogs.push(`Error processing ${absoluteLink}: ${error.message}`);
            productData.error = error.message;
            allProducts.push(productData);
            await closeBrowser();
            await initializeBrowser();
        }
    };

    try {
        await initializeBrowser();

        while (pageNumber <= MAX_PAGES) {
            console.log(`Processing page ${pageNumber}...`);

            let pageUrl = PRODUCTS_URL;
            if (PAGINATION_METHOD === 'url') {
                pageUrl = buildPaginationUrl(PRODUCTS_URL, pageNumber);
                console.log(`Navigating to page: ${pageUrl}...`);
                await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 120000 });
                console.log('Page navigation completed.');
                allLinks.push(...await extractLinks());
                await page.waitForTimeout(5000);
            } else if (PAGINATION_METHOD === 'next_button') {
                if (pageNumber === 1) {
                    console.log(`Navigating to main page: ${pageUrl}...`);
                    await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 120000 });
                    console.log('Main page navigation completed.');
                }
                await scrollPage();
                allLinks.push(...await extractLinks());

                console.log('Checking for "Next Page" button...');
                const nextButton = await page.$(NEXT_BUTTON_SELECTOR);
                if (!nextButton || !await nextButton.isVisible()) {
                    console.log('No "Next Page" button found or not visible. Stopping pagination.');
                    break;
                }

                console.log('Clicking "Next Page" button...');
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
                    nextButton.click()
                ]);
                console.log('Next page loaded successfully.');
                await page.waitForTimeout(5000);
            }

            pageNumber++;
            pagesProcessed++;
        }

        console.log(`Total unique links extracted: ${allLinks.length}`);

        for (let index = 0; index < allLinks.length; index++) {
            await processProduct(allLinks[index], index);
        }

        console.log('Final result:', JSON.stringify({ links: allLinks, products: allProducts, pages_processed: pagesProcessed, console_logs: consoleLogs }));

    } catch (error) {
        console.error(`Error occurred: ${error.message}`);
        consoleLogs.push(`Error: ${error.message}`);
    } finally {
        await closeBrowser();
        console.log('Final result:', JSON.stringify({ links: allLinks, products: allProducts, pages_processed: pagesProcessed, console_logs: consoleLogs }));
    }
})();
JAVASCRIPT;

            // جایگزینی placeholderها
            $playwrightScript = str_replace(
                [
                    'USER_AGENT', 'PRODUCTS_URL', 'MAX_PAGES', 'LINK_SELECTOR', 'LINK_ATTRIBUTE',
                    'NEXT_BUTTON_SELECTOR', 'SCROLL_DELAY', 'TITLE_SELECTOR', 'PRICE_SELECTOR',
                    'AVAILABILITY_SELECTOR', 'IMAGE_SELECTOR', 'IMAGE_ATTRIBUTE', 'CATEGORY_SELECTOR',
                    'GUARANTEE_SELECTOR', 'PRODUCT_ID_SELECTOR', 'PRODUCT_ID_ATTRIBUTE',
                    'PRODUCT_ID_METHOD', 'PRODUCT_ID_SOURCE', 'BASEURL', 'POSITIVE_KEYWORDS',
                    'NEGATIVE_KEYWORDS', 'PAGINATION_METHOD', 'PAGINATION_CONFIG', 'SCROOL'
                ],
                [
                    $userAgent, json_encode($productsUrl), $maxPages, $linkSelector, $linkAttribute,
                    json_encode($paginationConfig['next_button']['selector'] ?? 'a.next-page'),
                    $scrollDelay, $titleSelector, $priceSelector, $availabilitySelector,
                    $imageSelector, $imageAttribute, $categorySelector, $guaranteeSelector,
                    $productIdSelector, $productIdAttribute, $productIdMethod, $productIdSource,
                    $baseurl, $positiveKeywords, $negativeKeywords, $paginationMethod,
                    $paginationConfigJson, $scrool
                ],
                $playwrightScript
            );

            // ذخیره اسکریپت موقت
            $tempFileBase = tempnam(sys_get_temp_dir(), 'playwright_method3_');
            $tempFile = $tempFileBase . '.cjs';
            rename($tempFileBase, $tempFile);
            file_put_contents($tempFile, $playwrightScript);

            $this->log("Temporary script file created at: $tempFile", self::COLOR_GREEN);

            // اجرای اسکریپت
            $nodeModulesPath = base_path('node_modules');
            $this->log("Executing Playwright script: NODE_PATH=$nodeModulesPath node $tempFile", self::COLOR_GREEN);

            $command = "NODE_PATH=$nodeModulesPath node $tempFile 2>&1";
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $process = proc_open($command, $descriptors, $pipes);

            if (!is_resource($process)) {
                $this->log("Failed to start Playwright script process.", self::COLOR_RED);
                unlink($tempFile);
                continue;
            }

            $output = '';
            $logFile = storage_path('logs/playwright_method3_' . date('Ymd_His') . '.log');
            while (!feof($pipes[1])) {
                $line = fgets($pipes[1]);
                if ($line !== false) {
                    $output .= $line;
                    $this->log("Playwright output: " . trim($line), self::COLOR_YELLOW);
                    file_put_contents($logFile, "[STDOUT] " . trim($line) . PHP_EOL, FILE_APPEND);
                }
            }

            while (!feof($pipes[2])) {
                $errorLine = fgets($pipes[2]);
                if ($errorLine !== false) {
                    $this->log("Playwright error: " . trim($errorLine), self::COLOR_RED);
                    file_put_contents($logFile, "[STDERR] " . trim($errorLine) . PHP_EOL, FILE_APPEND);
                }
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($process);

            $this->log("Playwright script execution completed with return code: $returnCode", self::COLOR_GREEN);

            // تجزیه خروجی
            preg_match('/Final result: ({.*})/', $output, $matches);
            if (!isset($matches[1])) {
                $this->log("Failed to parse Playwright output.", self::COLOR_RED);
                $this->log("Raw output: $output", self::COLOR_RED);
                unlink($tempFile);
                continue;
            }

            $result = json_decode($matches[1], true);
            if (!$result || !isset($result['products'])) {
                $this->log("Invalid Playwright output format.", self::COLOR_RED);
                unlink($tempFile);
                continue;
            }

            if (isset($result['console_logs']) && is_array($result['console_logs'])) {
                foreach ($result['console_logs'] as $log) {
                    $this->log("Playwright console log: $log", self::COLOR_YELLOW);
                }
            }

            // پردازش محصولات و ذخیره با استفاده از وابستگی‌ها
            $links = [];
            $successfulProducts = 0;
            $failedProducts = 0;

            foreach ($result['products'] as $productData) {
                $this->log("Processing product: {$productData['url']}", self::COLOR_YELLOW);

                if (!empty($productData['error'])) {
                    $this->log("Error processing product {$productData['url']}: {$productData['error']}", self::COLOR_RED);
                    $failedProducts++;
                    continue;
                }

                if (empty($productData['title']) && empty($productData['price'])) {
                    $this->log("Product has no title or price: {$productData['url']}", self::COLOR_RED);
                    $failedProducts++;
                    continue;
                }

                // پردازش داده‌های محصول با استفاده از وابستگی‌ها
                $processedData = [
                    'page_url' => $productData['url'],
                    'url' => $productData['url'],
                    'title' => $productData['title'] ?? '',
                    'price' => $this->config['keep_price_format'] ?? false
                        ? $this->productProcessor->cleanPriceWithFormat($productData['price'] ?? '')
                        : (string)$this->productProcessor->cleanPrice($productData['price'] ?? ''),
                    'availability' => isset($productData['availability']) ? (int)$productData['availability'] : 0,
                    'image' => $this->makeAbsoluteUrl($productData['image'] ?? ''),
                    'category' => ($this->config['category_method'] ?? 'selector') === 'title' && !empty($productData['title'])
                        ? $this->extractCategoryFromTitle($productData['title'], $this->config['category_word_count'] ?? 1)
                        : $productData['category'] ?? '',
                    'guarantee' => $this->productProcessor->cleanGuarantee($productData['guarantee'] ?? ''),
                    'product_id' => $productData['product_id'] ?? '',
                    'off' => (int)$this->productProcessor->cleanOff($productData['off'] ?? '0')
                ];

                // validation با استفاده از ProductDataProcessor
                if ($this->productProcessor->validateProductData($processedData)) {
                    $links[] = [
                        'url' => $processedData['page_url'],
                        'image' => $processedData['image'],
                        'product_id' => $processedData['product_id']
                    ];

                    $successfulProducts++;
                    $this->log("✅ Product processed successfully: {$processedData['page_url']}", self::COLOR_GREEN);
                } else {
                    $this->log("Invalid product data for {$processedData['page_url']}", self::COLOR_RED);
                    $failedProducts++;
                }
            }

            $this->log("Products processing summary - Successful: $successfulProducts, Failed: $failedProducts", self::COLOR_PURPLE);
            $allLinks = array_merge($allLinks, $links);
            $totalPagesProcessed += $result['pages_processed'] ?? 0;

            unlink($tempFile);
        }

        $this->log("All products_urls processed. Total links: " . count($allLinks) . ", Total pages: $totalPagesProcessed", self::COLOR_GREEN);

        return [
            'links' => array_unique($allLinks, SORT_REGULAR),
            'pages_processed' => $totalPagesProcessed
        ];
    }

// متد کمکی که در LinkScraper نیاز است اما در کلاس‌های دیگر نیست
    private function extractCategoryFromTitle(string $title, $wordCount = 1): string
    {
        // استفاده از ProductDataProcessor برای این عملیات نیز
        // اما چون این متد خاص LinkScraper است، آن را به صورت محلی تعریف می‌کنیم
        $cleanTitle = $this->cleanCategoryText($title);
        $words = preg_split('/\s+/', trim($cleanTitle), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) return '';

        $categories = [];

        if (is_array($wordCount)) {
            foreach ($wordCount as $count) {
                if (is_numeric($count) && $count > 0 && $count <= count($words)) {
                    $categoryWords = array_slice($words, 0, $count);
                    $category = implode(' ', $categoryWords);
                    if (!empty($category)) {
                        $categories[] = $category;
                    }
                }
            }
        } else {
            if (is_numeric($wordCount) && $wordCount > 0) {
                $categoryWords = array_slice($words, 0, min($wordCount, count($words)));
                $category = implode(' ', $categoryWords);
                if (!empty($category)) {
                    $categories[] = $category;
                }
            }
        }

        $categories = array_filter(array_unique($categories), function ($cat) {
            return !empty(trim($cat));
        });

        return implode(', ', $categories);
    }

    private function cleanCategoryText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[^\p{L}\p{N}\s\-_,]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

// متد extractProductIdFromUrl که در همین کلاس نیاز است
    private function extractProductIdFromUrl(string $url): string
    {
        if (($this->config['product_id_method'] ?? 'selector') === 'url') {
            $pattern = $this->config['product_id_url_pattern'] ?? 'products/(\d+)';
            try {
                if (preg_match("#$pattern#", $url, $matches)) {
                    return $matches[1];
                }
            } catch (\Exception $e) {
                // Pattern failed
            }

            $path = parse_url($url, PHP_URL_PATH);
            $parts = explode('/', trim($path, '/'));
            $productIndex = array_search('products', $parts);
            if ($productIndex !== false && isset($parts[$productIndex + 1])) {
                $potentialId = $parts[$productIndex + 1];
                if (is_numeric($potentialId)) {
                    return $potentialId;
                }
            }
        }
        return '';
    }

    // Helper methods
    private function randomUserAgent(): string
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
            // ... more user agents
        ];
        return $agents[array_rand($agents)];
    }

    private function exponentialBackoff(int $attempt): int
    {
        return (int)(100 * pow(2, $attempt - 1));
    }

    private function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (!$parsed) {
            return $url;
        }

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : 'https://';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        $path = rtrim($path, '/') . '/';
        $path = preg_replace('/\/+/', '/', $path);
        $queryPart = $query ? '?' . $query : '';

        $normalizedUrl = $scheme . $host . $path . $queryPart;
        $this->log("Normalized URL: $url -> $normalizedUrl", self::COLOR_YELLOW);
        return $normalizedUrl;
    }

    private function buildPaginationUrl(string $baseUrl, int $page, array $pagination): string
    {
        $param = $pagination['parameter'] ?? 'page';
        $type = $pagination['type'] ?? 'query';
        $separator = $pagination['separator'] ?? '=';
        $suffix = $pagination['suffix'] ?? '';
        $useSampleUrl = $pagination['use_sample_url'] ?? false;
        $sampleUrl = $pagination['sample_url'] ?? '';
        $forceTrailingSlash = $pagination['force_trailing_slash'] ?? false;

        if ($useSampleUrl && $sampleUrl && $page > 1) {
            try {
                $pattern = $this->extractPaginationPatternFromSample($sampleUrl, $pagination);
                $url = str_replace('{page}', $page, $pattern);
                $this->log("Built pagination URL from sample: $url", self::COLOR_GREEN);
                return $url;
            } catch (\Exception $e) {
                $this->log("Failed to build URL from sample: {$e->getMessage()}. Falling back to standard logic.", self::COLOR_YELLOW);
            }
        }

        $baseUrl = rtrim($baseUrl, '/?');
        if ($forceTrailingSlash) {
            $baseUrl .= '/';
        }

        if ($page === 1 && !$suffix) {
            return $baseUrl;
        }

        if ($type === 'query') {
            return $baseUrl . "?{$param}{$separator}{$page}{$suffix}";
        } elseif ($type === 'path') {
            return $baseUrl . "/{$param}{$separator}{$page}" . ($suffix ? "/{$suffix}" : '');
        } else {
            throw new \Exception("Invalid pagination type: $type. Use 'query' or 'path'.");
        }
    }

    private function extractPaginationPatternFromSample(string $sampleUrl, array $pagination): string
    {
        $param = $pagination['parameter'] ?? 'page';
        $separator = $pagination['separator'] ?? '=';
        $escapedParam = preg_quote($param, '/');
        $escapedSeparator = preg_quote($separator, '/');
        $pattern = "/{$escapedParam}{$escapedSeparator}(\\d+)/";

        if (!preg_match($pattern, $sampleUrl, $matches)) {
            throw new \Exception("Could not extract page number from sample URL: $sampleUrl");
        }

        $pageNumber = $matches[1];
        $basePart = preg_replace($pattern, "{$param}{$separator}{page}", $sampleUrl);

        if (strpos($basePart, "صفحه-$pageNumber") !== false) {
            $basePart = str_replace("صفحه-$pageNumber", "صفحه-{page}", $basePart);
        }

        return $basePart;
    }

    private function isUnwantedDomain(string $url): bool
    {
        $unwantedDomains = [
            'telegram.me',
            't.me',
            'wa.me',
            'whatsapp.com',
            'aparat.com',
            'rubika.ir',
            'sapp.ir',
            'igap.net',
            'bale.ai',
        ];

        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if (!$parsedUrl) {
            return true;
        }

        foreach ($unwantedDomains as $domain) {
            if (stripos($parsedUrl, $domain) !== false) {
                $this->log("Skipping unwanted domain: $url", self::COLOR_YELLOW);
                return true;
            }
        }

        return false;
    }

    private function isInvalidLink(?string $href): bool
    {
        return empty($href) || $href === '#' || stripos($href, 'javascript:') === 0;
    }

    private function makeAbsoluteUrl(string $href): string
    {
        if (empty($href) || $href === '#' || stripos($href, 'javascript:') === 0) {
            return '';
        }

        if (stripos($href, 'http://') === 0 || stripos($href, 'https://') === 0) {
            return urldecode($href);
        }

        $baseUrl = $this->config['base_urls'][0] ?? '';
        if (empty($baseUrl)) {
            $this->log("No base_url defined, cannot create absolute URL for: $href", self::COLOR_RED);
            return $href;
        }

        $baseUrl = rtrim($baseUrl, '/');
        $href = ltrim($href, '/');

        $fullUrl = "$baseUrl/$href";
        return urldecode($fullUrl);
    }

    private function log(string $message, ?string $color = null): void
    {
        $colorReset = "\033[0m";
        $formattedMessage = $color ? $color . $message . $colorReset : $message;

        if ($this->outputCallback) {
            call_user_func($this->outputCallback, $formattedMessage);
        } else {
            echo $formattedMessage . PHP_EOL;
        }
    }
}
