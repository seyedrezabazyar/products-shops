<?php

namespace App\Http\Controllers;

use App\Models\FailedLink;
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class StartController
{
    private array $config;
    private Client $httpClient;
    private $chromedriverPid = null;
    private array $failedUrlsDueToInternalError = [];
    private $outputCallback = null;
    private int $processedCount = 0;
    private int $failedLinks = 0;
    private array $sharedProductIds = [];

    private const COLOR_GREEN = "\033[1;92m";
    private const COLOR_RED = "\033[1;91m";
    private const COLOR_PURPLE = "\033[1;95m";
    private const COLOR_YELLOW = "\033[1;93m";
    private const COLOR_BLUE = "\033[1;94m";
    private const COLOR_RESET = "\033[0m";
    private const COLOR_BOLD = "\033[1m";

    public function __construct(array $config)
    {
        $this->config = $config;
        ini_set('memory_limit', '1024M'); // افزایش حافظه
        set_time_limit(0); // حذف محدودیت زمانی
        $this->httpClient = new Client([

            'timeout' => $this->config['timeout'] ?? 120, // افزایش تایم‌اوت به 120 ثانیه
            'verify' => $this->config['verify_ssl'] ?? false,
            'headers' => [
                'User-Agent' => $this->randomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => $this->config['base_urls'][0],
                'Connection' => 'keep-alive',
            ],
        ]);
    }

    private function scrapeMethodThree(): array
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

        foreach ($this->config['products_urls'] as $index => $productsUrl) {
            $normalizedUrl = $this->normalizeUrl($productsUrl);
            if (in_array($normalizedUrl, $processedUrls)) {
                $this->log("Skipping duplicate products_url: $productsUrl", self::COLOR_YELLOW);
                continue;
            }
            $processedUrls[] = $normalizedUrl;

            $this->log("Processing products_url " . ($index + 1) . ": $productsUrl", self::COLOR_PURPLE);

            $baseurl = json_encode($this->config['base_urls'][0] ?? '');
            $scrool = json_encode($this->config['scrool'] ?? '');
            $userAgent = json_encode($this->config['user_agent'][0] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124');
            $linkSelector = json_encode($this->config['selectors']['main_page']['product_links']['selector'] ?? 'a[href*="/product"]');
            $linkAttribute = json_encode($this->config['selectors']['main_page']['product_links']['attribute'] ?? 'href');
            $maxPages = $this->config['method_settings']['method_3']['navigation']['max_iterations'] ?? 5;
            $scrollDelay = $this->config['method_settings']['method_3']['navigation']['timing']['scroll_delay'] ?? 3000;
            $positiveKeywords = json_encode($this->config['availability_keywords']['positive'] ?? []);
            $negativeKeywords = json_encode($this->config['availability_keywords']['negative'] ?? []);

            $titleSelector = json_encode($this->config['selectors']['product_page']['title']['selector'] ?? '.styles__title___3F4_f');
            $priceSelector = json_encode($this->config['selectors']['product_page']['price']['selector'][0] ?? '.styles__final-price___1L1AM');
            $availabilitySelector = json_encode($this->config['selectors']['product_page']['availability']['selector'][0] ?? '#buy-button');
            $imageSelector = json_encode($this->config['selectors']['product_page']['image']['selector'] ?? 'img.styles__slide___1r6T7');
            $imageAttribute = json_encode($this->config['selectors']['product_page']['image']['attribute'] ?? 'src');
            $categorySelector = json_encode($this->config['selectors']['product_page']['category']['selector'] ?? 'a.styles__bread-crumb-item___3xa5Q:nth-child(3)');
            $guaranteeSelector = json_encode($this->config['selectors']['product_page']['guarantee']['selector'] ?? '');
            $productIdSelector = json_encode($this->config['selectors']['product_page']['product_id']['selector'] ?? 'head > meta:nth-child(9)');
            $productIdAttribute = json_encode($this->config['selectors']['product_page']['product_id']['attribute'] ?? 'content');

            $paginationConfig = $this->config['method_settings']['method_3']['navigation']['pagination'] ?? [];
            $paginationMethod = json_encode($paginationConfig['method'] ?? 'next_button');
            $paginationConfigJson = json_encode($paginationConfig);

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
            consoleLogs.push(`Error YELLOW: ${e.message}`);
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

        console.log(`page${pageNumber} -> ${newLinks.length} link find`);
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

                console.log(`Raw availability text: Retrospective ${availabilityText}`);
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
            if (PRODUCT_ID_SELECTOR && PRODUCT_ID_SELECTOR.trim() !== '') {
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
                const urlPattern = /product\/(\d+)/;
                const match = absoluteLink.match(urlPattern);
                productData.product_id = match ? match[1] : '';
                console.log(`Extracted product_id from URL: ${productData.product_id}`);
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

            $playwrightScript = str_replace(
                [
                    'USER_AGENT',
                    'PRODUCTS_URL',
                    'MAX_PAGES',
                    'LINK_SELECTOR',
                    'LINK_ATTRIBUTE',
                    'NEXT_BUTTON_SELECTOR',
                    'SCROLL_DELAY',
                    'TITLE_SELECTOR',
                    'PRICE_SELECTOR',
                    'AVAILABILITY_SELECTOR',
                    'IMAGE_SELECTOR',
                    'IMAGE_ATTRIBUTE',
                    'CATEGORY_SELECTOR',
                    'GUARANTEE_SELECTOR',
                    'PRODUCT_ID_SELECTOR',
                    'PRODUCT_ID_ATTRIBUTE',
                    'BASEURL',
                    'POSITIVE_KEYWORDS',
                    'NEGATIVE_KEYWORDS',
                    'PAGINATION_METHOD',
                    'PAGINATION_CONFIG',
                    'SCROOL'
                ],
                [
                    $userAgent,
                    json_encode($productsUrl),
                    $maxPages,
                    $linkSelector,
                    $linkAttribute,
                    json_encode($paginationConfig['next_button']['selector'] ?? 'a.next-page'),
                    $scrollDelay,
                    $titleSelector,
                    $priceSelector,
                    $availabilitySelector,
                    $imageSelector,
                    $imageAttribute,
                    $categorySelector,
                    $guaranteeSelector,
                    $productIdSelector,
                    $productIdAttribute,
                    $baseurl,
                    $positiveKeywords,
                    $negativeKeywords,
                    $paginationMethod,
                    $paginationConfigJson,
                    $scrool
                ],
                $playwrightScript
            );

            $tempFileBase = tempnam(sys_get_temp_dir(), 'playwright_method3_');
            $tempFile = $tempFileBase . '.cjs';
            rename($tempFileBase, $tempFile);
            file_put_contents($tempFile, $playwrightScript);

            $this->log("Temporary script file created at: $tempFile", self::COLOR_GREEN);

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
                    if (strpos($line, 'page') !== false && strpos($line, 'link find') !== false) {
                        $this->log(trim($line), self::COLOR_GREEN);
                    } else {
                        $this->log("Playwright output: " . trim($line), self::COLOR_YELLOW);
                    }
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

            $links = [];
            foreach ($result['products'] as $productData) {
                $this->log("Processing product: {$productData['url']}, Availability: {$productData['availability']}", self::COLOR_YELLOW);
                if (!empty($productData['error'])) {
                    $this->log("Error processing product {$productData['url']}: {$productData['error']}", self::COLOR_RED);
                    $this->saveFailedLink($productData['url'], $productData['error']);
                    continue;
                }

                $processedData = [
                    'page_url' => $productData['url'],
                    'url' => $productData['url'],
                    'title' => $productData['title'] ?? '',
                    'price' => $this->config['keep_price_format'] ?? false
                        ? $this->cleanPriceWithFormat($productData['price'] ?? '')
                        : $this->cleanPrice($productData['price'] ?? ''),
                    'availability' => isset($productData['availability']) ? (int)$productData['availability'] : 0,
                    'image' => $this->makeAbsoluteUrl($productData['image'] ?? ''),
                    'category' => ($this->config['category_method'] ?? 'selector') === 'title' && !empty($productData['title'])
                        ? $this->extractCategoryFromTitle($productData['title'], $this->config['category_word_count'] ?? 1)
                        : $productData['category'] ?? '',
                    'guarantee' => $this->cleanGuarantee($productData['guarantee'] ?? ''),
                    'product_id' => $productData['product_id'] ?? '',
                    'off' => (int)$this->cleanOff($productData['off'] ?? '0')
                ];

                if ($this->validateProductData($processedData)) {
                    try {
                        $this->saveProductToDatabase($processedData);
                        $this->logProduct($processedData);
                        $links[] = [
                            'url' => $processedData['page_url'],
                            'image' => $processedData['image'],
                            'product_id' => $processedData['product_id']
                        ];
                    } catch (\Exception $e) {
                        $this->log("Failed to save product {$processedData['page_url']}: {$e->getMessage()}", self::COLOR_RED);
                        $this->saveFailedLink($processedData['page_url'], "Database error: {$e->getMessage()}");
                    }
                } else {
                    $this->log("Invalid product data for {$processedData['page_url']}", self::COLOR_RED);
                    $this->saveFailedLink($processedData['page_url'], "Invalid product data");
                }
            }

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

    private function scrapeMethodOneForUrl(string $baseUrl): array
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

    private function extractCategoryFromTitle(string $title, int $wordCount = 1): string
    {
        $this->log("Extracting category from title: '$title' with word count: $wordCount", self::COLOR_YELLOW);

        // تقسیم عنوان به کلمات
        $words = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY);

        // گرفتن تعداد کلمات موردنظر
        $categoryWords = array_slice($words, 0, min($wordCount, count($words)));

        // ترکیب کلمات به‌عنوان دسته‌بندی
        $category = implode(' ', $categoryWords);

        $this->log("Extracted category: '$category'", self::COLOR_GREEN);
        return $category;
    }

    private function scrapeWithPlaywright(int $method, string $productUrl): array
    {
        if ($method !== 2) {
            $this->log("Playwright is only supported for method 2", self::COLOR_RED);
            return ['links' => [], 'pages_processed' => 0];
        }

        $this->log("Starting Playwright scraping process for URL: $productUrl...", self::COLOR_GREEN);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $maxPages = $this->config['method_settings']['method_2']['navigation']['max_pages'] ?? 10;
        $scrollDelay = $this->config['method_settings']['method_2']['navigation']['scroll_delay'] ?? 3000;
        $paginationMethod = $this->config['method_settings']['method_2']['navigation']['pagination']['method'] ?? 'url';
        $this->log("Pagination method: $paginationMethod", self::COLOR_YELLOW);

        $linkSelector = addslashes($this->config['selectors']['main_page']['product_links']['selector']);
        $linkAttribute = addslashes($this->config['selectors']['main_page']['product_links']['attribute']);
        $imageSelector = addslashes($this->config['selectors']['main_page']['image']['selector'] ?? '');
        $imageAttribute = addslashes($this->config['selectors']['main_page']['image']['attribute'] ?? 'src');
        $productIdSelector = addslashes($this->config['selectors']['main_page']['product_id']['selector'] ?? '');
        $productIdAttribute = addslashes($this->config['selectors']['main_page']['product_id']['attribute'] ?? 'data-product_id');
        $productIdFromLink = addslashes($this->config['selectors']['main_page']['product_links']['product_id'] ?? '');
        $imageMethod = addslashes($this->config['image_method']);
        $productIdSource = addslashes($this->config['product_id_source']);
        $productIdMethod = addslashes($this->config['product_id_method'] ?? 'selector');
        $urlFilter = addslashes($this->config['selectors']['main_page']['product_links']['url_filter'] ?? '');
        $userAgent = addslashes($this->config['user_agent']);
        $container = addslashes($this->config['container']);
        $baseUrl = addslashes($this->config['base_urls'][0] ?? '');

        $nextButtonSelector = '';
        if ($paginationMethod === 'next_button') {
            $nextButtonSelector = addslashes($this->config['method_settings']['method_2']['navigation']['pagination']['next_button']['selector'] ?? '');
            $this->log("Next button selector: $nextButtonSelector", self::COLOR_YELLOW);
            if (empty($nextButtonSelector)) {
                $this->log("Next button selector is required for pagination method 'next_button'", self::COLOR_RED);
                return ['links' => [], 'pages_processed' => 0];
            }
        }

        $paginationConfig = $this->config['method_settings']['method_2']['navigation']['pagination']['url'] ?? [];
        $paginationType = addslashes($paginationConfig['type'] ?? 'query');
        $paginationParam = addslashes($paginationConfig['parameter'] ?? 'page');
        $paginationSeparator = addslashes($paginationConfig['separator'] ?? '=');
        $paginationSuffix = addslashes($paginationConfig['suffix'] ?? '');
        $useSampleUrl = $paginationConfig['use_sample_url'] ?? false;
        $sampleUrl = addslashes($paginationConfig['sample_url'] ?? '');
        $forceTrailingSlash = $paginationConfig['force_trailing_slash'] ?? false;
        $paginationConfigJson = json_encode($paginationConfig, JSON_UNESCAPED_SLASHES);

        $playwrightScript = <<<'JAVASCRIPT'
const { chromium } = require('playwright');

(async () => {
    let links = [];
    let pagesProcessed = 0;
    let consoleLogs = [];

    try {
        console.log('Launching browser...');
        const browser = await chromium.launch({ headless: true });
        console.log('Browser launched successfully.');

        console.log('Creating new browser context with user agent: USER_AGENT');
        const context = await browser.newContext({
            userAgent: "USER_AGENT",
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
            const param = "PAGINATION_PARAM";
            const separator = "PAGINATION_SEPARATOR";
            const type = "PAGINATION_TYPE";
            const suffix = "PAGINATION_SUFFIX";
            const useSampleUrl = USE_SAMPLE_URL;
            const sampleUrl = "SAMPLE_URL";
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
        const linkSelector = "LINK_SELECTOR";
        const linkAttribute = "LINK_ATTRIBUTE";
        const imageSelector = "IMAGE_SELECTOR";
        const imageAttribute = "IMAGE_ATTRIBUTE";
        const productIdSelector = "PRODUCT_ID_SELECTOR";
        const productIdAttribute = "PRODUCT_ID_ATTRIBUTE";
        const productIdFromLink = "PRODUCT_ID_FROM_LINK";
        const imageMethod = "IMAGE_METHOD";
        const productIdSource = "PRODUCT_ID_SOURCE";
        const productIdMethod = "PRODUCT_ID_METHOD";
        const scrollDelay = SCROLL_DELAY;
        const urlFilter = "URL_FILTER" ? new RegExp("URL_FILTER") : null;
        const container = "CONTAINER";
        const baseUrl = "BASE_URL";
        const paginationMethod = "PAGINATION_METHOD";
        const nextButtonSelector = "NEXT_BUTTON_SELECTOR";
        let currentPage = 1;
        let hasMorePages = true;

        console.log(`Using pagination method: ${paginationMethod}`);

        while (hasMorePages && currentPage <= maxPages) {
            let pageUrl = "PRODUCTS_URL";
            if (paginationMethod === 'url' || (paginationMethod === 'next_button' && currentPage === 1)) {
                pageUrl = paginationMethod === 'url' ? buildPaginationUrl("PRODUCTS_URL", currentPage) : "PRODUCTS_URL";
                console.log(`Processing page ${currentPage} at URL: ${pageUrl}...`);
                try {
                    await page.goto(pageUrl, { waitUntil: 'domcontentloaded', timeout: 120000 });
                } catch (error) {
                    console.log(`Failed to load page ${currentPage}: ${error.message}`);
                    hasMorePages = false;
                    break;
                }
            } else {
                console.log(`Processing page ${currentPage} via next button...`);
            }

            await page.waitForSelector('CONTAINER', { timeout: 10000 }).catch(() => {
                console.log('Products container not found, continuing...');
            });

            for (let i = 0; i < 3; i++) {
                await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
                await page.waitForTimeout(scrollDelay);
                console.log(`Scroll ${i + 1} completed`);
            }

            await page.waitForFunction(
                (selector) => document.querySelectorAll(selector).length > 0,
                linkSelector,
                { timeout: 20000 }
            ).catch(() => {
                console.log('No links found after waiting, continuing...');
            });
            console.log('Page scrolled and dynamic content loaded.');

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
                    console.log(`Checking href: ${href}`);
                    if (href && !href.startsWith('javascript:') && !href.startsWith('#') && (!urlFilter || urlFilter.test(href))) {
                        const fullUrl = href.startsWith('http') ? href : new URL(href, baseUrl).href;
                        let image = '';
                        if (imageSel && imageMethod === 'main_page') {
                            const parent = node.closest(container);
                            const imageElement = parent ? parent.querySelector(imageSel) : null;
                            image = imageElement ? imageElement.getAttribute(imageAttr) : '';
                            console.log(`Image for link ${fullUrl}: ${image}`);
                        }
                        let productId = '';
                        if (productIdSource === 'product_links' && productIdFromLink) {
                            productId = node.getAttribute(productIdFromLink) || 'unknown';
                            console.log(`Extracted product_id from link attribute ${productIdFromLink}: ${productId}`);
                        } else if (productIdSource === 'main_page' && productIdSel) {
                            const parent = node.closest(container);
                            const productIdElement = parent ? parent.querySelector(productIdSel) : null;
                            productId = productIdElement ? productIdElement.getAttribute(productIdAttr) : 'unknown';
                            console.log(`Extracted product_id from selector ${productIdSel}: ${productId}`);
                        } else if (productIdMethod === 'url') {
                            const match = href.match(/product\/(\d+)/);
                            productId = match ? match[1] : 'unknown';
                            console.log(`Extracted product_id from URL ${href}: ${productId}`);
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

            console.log(`page${currentPage} -> ${currentPageLinks.length} link find`);
            if (currentPageLinks.length === 0) {
                console.log(`No products found on page ${currentPage}. Stopping...`);
                hasMorePages = false;
            } else {
                links.push(...currentPageLinks);
            }

            pagesProcessed++;
            currentPage++;

            if (paginationMethod === 'next_button' && hasMorePages) {
                try {
                    console.log(`Attempting to find next button with selector: ${nextButtonSelector}`);
                    await page.waitForSelector(nextButtonSelector, { timeout: 10000 });
                    const nextButton = await page.$(nextButtonSelector);
                    if (nextButton) {
                        console.log(`Next button found. Checking if enabled...`);
                        const isButtonEnabled = await page.evaluate((selector) => {
                            const btn = document.querySelector(selector);
                            return btn && !btn.disabled && btn.offsetParent !== null;
                        }, nextButtonSelector);

                        if (!isButtonEnabled) {
                            console.log('Next button is disabled or not visible. Stopping pagination.');
                            hasMorePages = false;
                            break;
                        }

                        console.log(`Clicking next button with selector: ${nextButtonSelector}`);
                        const currentUrl = page.url();
                        console.log(`Current URL before click: ${currentUrl}`);
                        await nextButton.scrollIntoViewIfNeeded();
                        await nextButton.click();
                        await page.waitForTimeout(10000);

                        const newUrl = page.url();
                        console.log(`New URL after click: ${newUrl}`);

                        const newProductCount = await page.evaluate((selector) => {
                            return document.querySelectorAll(selector).length;
                        }, linkSelector);
                        console.log(`New product count after click: ${newProductCount}`);

                        if (newUrl === currentUrl && newProductCount <= currentPageLinks.length) {
                            console.log('URL or content did not change after click. Stopping pagination.');
                            hasMorePages = false;
                        }
                    } else {
                        console.log('Next button not found. Stopping pagination.');
                        hasMorePages = false;
                    }
                } catch (error) {
                    console.log(`Failed to click next button: ${error.message}`);
                    hasMorePages = false;
                }
            } else if (paginationMethod === 'url') {
                console.log(`Using URL pagination for page ${currentPage}`);
                await page.waitForTimeout(7000);
            }
        }

        links = [...new Set(links.map(link => JSON.stringify(link)))].map(str => JSON.parse(str));
        console.log('Scraping completed.');
        await browser.close();

        console.log(JSON.stringify({ links, pagesProcessed, consoleLogs }));
    } catch (error) {
        console.error('Error in Playwright script:', error.message);
        console.log(JSON.stringify({ links: [], pagesProcessed, consoleLogs, error: error.message }));
    }
})();
JAVASCRIPT;

        $playwrightScript = str_replace(
            [
                'USER_AGENT',
                'PRODUCTS_URL',
                'MAX_PAGES',
                'LINK_SELECTOR',
                'LINK_ATTRIBUTE',
                'IMAGE_SELECTOR',
                'IMAGE_ATTRIBUTE',
                'PRODUCT_ID_SELECTOR',
                'PRODUCT_ID_ATTRIBUTE',
                'PRODUCT_ID_FROM_LINK',
                'IMAGE_METHOD',
                'PRODUCT_ID_SOURCE',
                'PRODUCT_ID_METHOD',
                'URL_FILTER',
                'CONTAINER',
                'BASE_URL',
                'SCROLL_DELAY',
                'PAGINATION_CONFIG',
                'PAGINATION_TYPE',
                'PAGINATION_PARAM',
                'PAGINATION_SEPARATOR',
                'PAGINATION_SUFFIX',
                'USE_SAMPLE_URL',
                'SAMPLE_URL',
                'FORCE_TRAILING_SLASH',
                'PAGINATION_METHOD',
                'NEXT_BUTTON_SELECTOR'
            ],
            [
                $userAgent,
                addslashes($productUrl),
                $maxPages,
                $linkSelector,
                $linkAttribute,
                $imageSelector,
                $imageAttribute,
                $productIdSelector,
                $productIdAttribute,
                $productIdFromLink,
                $imageMethod,
                $productIdSource,
                $productIdMethod,
                $urlFilter,
                $container,
                $baseUrl,
                $scrollDelay,
                $paginationConfigJson,
                $paginationType,
                $paginationParam,
                $paginationSeparator,
                $paginationSuffix,
                $useSampleUrl ? 'true' : 'false',
                $sampleUrl,
                $forceTrailingSlash ? 'true' : 'false',
                addslashes($paginationMethod),
                $nextButtonSelector
            ],
            $playwrightScript
        );

        $this->log("Generated Playwright script for $productUrl:", self::COLOR_YELLOW);

        $tempFileBase = tempnam(sys_get_temp_dir(), 'playwright_');
        $tempFile = $tempFileBase . '.cjs';
        rename($tempFileBase, $tempFile);
        file_put_contents($tempFile, $playwrightScript);

        $this->log("Temporary script file created at: $tempFile", self::COLOR_GREEN);

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
        $logFile = storage_path('logs/playwright_' . date('Ymd_His') . '.log');
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output .= $line;
                if (strpos($line, 'page') !== false && strpos($line, 'link find') !== false) {
                    $this->log(trim($line), self::COLOR_GREEN);
                } else {
                    $this->log("Playwright output: " . trim($line), self::COLOR_YELLOW);
                }
                file_put_contents($logFile, "[STDOUT] " . trim($line) . PHP_EOL, FILE_APPEND);
            }
        }

        while (!feof($pipes[2])) {
            $errorLine = fgets($pipes[2]);
            if ($errorLine !== false) {
                $errorOutput .= $errorLine;
                $this->log("Playwright error: " . trim($errorLine), self::COLOR_RED);
                file_put_contents($logFile, "[STDERR] " . trim($errorLine) . PHP_EOL, FILE_APPEND);
            }
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);

        $this->log("Playwright script execution completed with return code: $returnCode", self::COLOR_GREEN);

        if ($returnCode !== 0 || strpos($errorOutput, "Target page, context or browser has been closed") !== false || strpos($output, "Target page, context or browser has been closed") !== false) {
            $this->log("Internal Playwright error detected for URL: $productUrl. Will retry later.", self::COLOR_YELLOW);
            $this->failedUrlsDueToInternalError[] = $productUrl;
            unlink($tempFile);
            return ['links' => [], 'pages_processed' => 0];
        }

        preg_match('/\{.*\}/s', $output, $matches);
        if (!isset($matches[0])) {
            $this->log("Failed to parse Playwright output for $productUrl.", self::COLOR_RED);
            $this->log("Raw output: $output", self::COLOR_RED);
            unlink($tempFile);
            return ['links' => [], 'pages_processed' => 0];
        }

        $result = json_decode($matches[0], true);
        if (!$result || !isset($result['links'])) {
            $this->log("Invalid Playwright output format for $productUrl.", self::COLOR_RED);
            unlink($tempFile);
            return ['links' => [], 'pages_processed' => 0];
        }

        if (isset($result['console_logs']) && is_array($result['console_logs'])) {
            foreach ($result['console_logs'] as $log) {
                $this->log("Playwright console log: $log", self::COLOR_YELLOW);
            }
        }

        $links = array_map(function ($link) use ($productUrl) {
            $url = $this->makeAbsoluteUrl($link['url']);
            $productId = $link['product_id'] ?? 'unknown';

            if ($productId === 'unknown' && ($this->config['product_id_method'] ?? 'selector') === 'url') {
                $productId = $this->extractProductIdFromUrl($url, '', new Crawler());
                $this->log("Extracted product_id from URL in PHP: $productId for $url", self::COLOR_GREEN);
            }

            return [
                'url' => $url,
                'image' => $link['image'] ? $this->makeAbsoluteUrl($link['image']) : '',
                'product_id' => $productId
            ];
        }, $result['links']);

        $this->log("Found " . count($links) . " unique links for $productUrl.", self::COLOR_GREEN);

        unlink($tempFile);
        return [
            'links' => array_unique($links, SORT_REGULAR),
            'pages_processed' => $result['pages_processed'] ?? 0
        ];
    }

    private function getDatabaseNameFromBaseUrl(): string
    {
        $baseUrl = $this->config['base_urls'][0] ?? '';
        if (empty($baseUrl)) {
            throw new \Exception("No base_url defined for generating database name.");
        }

        $host = parse_url($baseUrl, PHP_URL_HOST); // مثل mrstock25.ir
        if (!$host) {
            throw new \Exception("Invalid base URL: $baseUrl");
        }

        // حذف www. و تبدیل . به _
        $host = preg_replace('/^www\./', '', $host);
        $dbName = str_replace('.', '_', $host);
        $this->log("Generated database name: $dbName", self::COLOR_GREEN);
        return $dbName;
    }

    private function setupDatabase(): void
    {
        $dbName = $this->getDatabaseNameFromBaseUrl();

        // اتصال به دیتابیس پیش‌فرض برای مدیریت دیتابیس‌ها
        $defaultConnection = config('database.connections.mysql.database');

        // بررسی وجود دیتابیس
        $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

        if (!empty($exists)) {
            $this->log("Database $dbName exists, dropping it...", self::COLOR_YELLOW);
            DB::statement("DROP DATABASE `$dbName`");
        }

        // ایجاد دیتابیس جدید
        $this->log("Creating database $dbName...", self::COLOR_GREEN);
        DB::statement("CREATE DATABASE `$dbName`");

        // تنظیم اتصال داینامیک
        config(["database.connections.dynamic" => [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => $dbName,
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        // پاک‌سازی اتصال قبلی و تنظیم اتصال جدید
        DB::purge('mysql');
        DB::setDefaultConnection('dynamic');

        $this->log("Switched to database: $dbName", self::COLOR_GREEN);

        // اجرای مهاجرت‌های خاص
        $this->log("Running specific migrations for database $dbName...", self::COLOR_GREEN);

        // لیست فایل‌های مهاجرت مورد نیاز
        $migrationFiles = [
            database_path('migrations/2025_04_08_162133_create_products_table.php'),
            database_path('migrations/2025_04_13_073528_create_failed_links_table.php'),
        ];

        foreach ($migrationFiles as $file) {
            try {
                if (!file_exists($file)) {
                    throw new \Exception("Migration file $file not found");
                }

                // بارگذاری دستی فایل مهاجرت
                require_once $file;

                // استخراج نام کلاس از فایل
                $className = $this->getMigrationClassName($file);
                if (!class_exists($className)) {
                    throw new \Exception("Migration class $className not found in $file");
                }

                $migration = new $className();
                $migration->up();
                $this->log("Applied migration: " . basename($file), self::COLOR_GREEN);
            } catch (\Exception $e) {
                $this->log("Failed to apply migration " . basename($file) . ": {$e->getMessage()}", self::COLOR_RED);
                throw $e;
            }
        }

        $this->log("Specific migrations completed for database $dbName", self::COLOR_GREEN);
    }

    private function getMigrationClassName(string $file): string
    {
        $contents = file_get_contents($file);
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $contents, $matches)) {
            return $matches[1];
        }
        throw new \Exception("Could not determine migration class name for $file");
    }

    private function processPagesInBatches(array $links, int $processingMethod = null): array
    {
        $this->log("Processing " . count($links) . " product links in batches...", self::COLOR_GREEN);
        $totalProducts = count($links);
        $this->processedCount = 0;
        $this->failedLinks = 0;
        $processedUrls = [];

        $filteredProducts = array_filter($links, function ($product) {
            $url = is_array($product) ? $product['url'] : $product;
            $isValid = !$this->isUnwantedDomain($url);
            if (!$isValid) {
                $this->log("Filtered out unwanted link before processing: $url", self::COLOR_YELLOW);
            }
            return $isValid;
        });
        $this->log("Filtered to " . count($filteredProducts) . " valid product links for processing", self::COLOR_GREEN);

        $method = $processingMethod ?? $this->config['method'] ?? 1;

        if ($method === 1) {
            $this->log("Generating requests for " . count($filteredProducts) . " products", self::COLOR_GREEN);
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
                    $productId = is_array($product) && isset($product['product_id']) ? $product['product_id'] : 'unknown';

                    if (in_array($url, $processedUrls)) {
                        $this->log("Skipping duplicate: $url", self::COLOR_GREEN);
                        return;
                    }

                    $this->processedCount++;
                    $this->log("Processing product {$this->processedCount} of {$totalProducts}");

                    $productData = $this->extractProductData($url, (string)$response->getBody(), $image, $productId);
                    if ($productData && $this->validateProductData($productData)) {
                        if (is_array($product) && isset($product['off'])) {
                            $productData['off'] = $product['off'];
                        }
                        $this->saveProductToDatabase($productData);
                        $processedUrls[] = $url;
                        $this->logProduct($productData, $totalProducts); // پاس دادن totalProducts
                        $this->log("DEBUG: logProduct called for {$productData['title']}", self::COLOR_YELLOW); // پیام دیباگ
                    } else {
                        $this->failedLinks++;
                        $this->saveFailedLink($url, "Failed to extract valid product data");
                        $this->log("Added to failed links: $url", self::COLOR_RED);
                    }
                },
                'rejected' => function ($reason, $index) use ($filteredProducts) {
                    $this->failedLinks++;
                    $url = is_array($filteredProducts[$index]) ? $filteredProducts[$index]['url'] : $filteredProducts[$index];
                    $this->saveFailedLink($url, "Failed to fetch product page: " . $reason->getMessage());
                    $this->log("Added to failed links: $url", self::COLOR_YELLOW);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
        } elseif ($method === 2) {
            $batchSize = $this->config['batch_size'] ?? 75;
            $batches = array_chunk($filteredProducts, $batchSize);

            foreach ($batches as $batchIndex => $batch) {
                $this->log("Processing batch " . ($batchIndex + 1) . " with " . count($batch) . " products", self::COLOR_GREEN);

                foreach ($batch as $product) {
                    $url = is_array($product) ? $product['url'] : $product;
                    $image = is_array($product) && isset($product['image']) ? $product['image'] : null;
                    $productId = is_array($product) && isset($product['product_id']) ? $product['product_id'] : 'unknown';

                    if (in_array($url, $processedUrls)) {
                        $this->log("Skipping duplicate: $url", self::COLOR_GREEN);
                        continue;
                    }

                    $this->processedCount++;
                    $this->log("Processing product {$this->processedCount} of {$totalProducts}: $url", self::COLOR_GREEN);

                    $productData = $this->processProductPageWithPlaywright($url);
                    if (isset($productData['error'])) {
                        $this->failedLinks++;
                        $this->saveFailedLink($url, $productData['error']);
                        $this->log("Added to failed links: $url - {$productData['error']}", self::COLOR_RED);
                        continue;
                    }

                    $productData['page_url'] = $url;
                    $productData['image'] = $image ?? ($productData['image'] ?? '');
                    $productData['product_id'] = $productId !== 'unknown' ? $productId : ($productData['product_id'] ?? 'unknown');
                    $productData['availability'] = isset($productData['availability']) ? (int)$productData['availability'] : 0;
                    $productData['off'] = isset($productData['off']) ? (int)$productData['off'] : 0;
                    $productData['category'] = $productData['category'] ?? '';
                    $productData['guarantee'] = $productData['guarantee'] ?? '';

                    if ($this->validateProductData($productData)) {
                        $this->log("DEBUG: Valid product data for $url, proceeding to save", self::COLOR_YELLOW);
                        try {
                            $this->saveProductToDatabase($productData);
                            $processedUrls[] = $url;
                            $this->log("DEBUG: Calling logProduct for {$productData['title']}", self::COLOR_YELLOW);
                            $this->logProduct($productData, $totalProducts);
                        } catch (\Exception $e) {
                            $this->failedLinks++;
                            $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                            $this->log("Added to failed links due to database error: $url", self::COLOR_RED);
                        }
                    } else {
                        $this->log("DEBUG: Invalid product data for $url", self::COLOR_RED);
                    }

                    usleep(rand($this->config['request_delay_min'] ?? 1000, $this->config['request_delay_max'] ?? 3000) * 1000);
                }
            }
        } elseif ($method === 3) {
            $batchSize = $this->config['batch_size'] ?? 75;
            $batches = array_chunk($filteredProducts, $batchSize);

            foreach ($batches as $batchIndex => $batch) {
                $this->log("Processing batch " . ($batchIndex + 1) . " with " . count($batch) . " products", self::COLOR_GREEN);

                foreach ($batch as $product) {
                    $url = is_array($product) ? $product['url'] : $product;
                    $image = is_array($product) && isset($product['image']) ? $product['image'] : null;
                    $productId = is_array($product) && isset($product['product_id']) ? $product['product_id'] : 'unknown';

                    if (in_array($url, $processedUrls)) {
                        $this->log("Skipping duplicate: $url", self::COLOR_GREEN);
                        continue;
                    }

                    $this->processedCount++;
                    $this->log("Processing product {$this->processedCount} of {$totalProducts}: $url", self::COLOR_GREEN);

                    $productData = $this->extractProductData($url, null, $image, $productId);
                    if ($productData === null) {
                        $this->failedLinks++;
                        $this->saveFailedLink($url, "Failed to extract product data");
                        $this->log("Added to failed links due to null product data: $url", self::COLOR_RED);
                        continue;
                    }

                    $productData['page_url'] = $url;
                    $productData['image'] = $image ?? ($productData['image'] ?? '');
                    $productData['product_id'] = $productId !== 'unknown' ? $productId : ($productData['product_id'] ?? 'unknown');
                    $productData['availability'] = isset($productData['availability']) ? (int)$productData['availability'] : 0;
                    $productData['off'] = isset($productData['off']) ? (int)$productData['off'] : 0;
                    $productData['category'] = $productData['category'] ?? '';
                    $productData['guarantee'] = $productData['guarantee'] ?? '';

                    if ($this->validateProductData($productData)) {
                        try {
                            $this->saveProductToDatabase($productData);
                            $processedUrls[] = $url;
                            $this->logProduct($productData, $totalProducts);
                            $this->log("Successfully saved product: $url", self::COLOR_GREEN);
                        } catch (\Exception $e) {
                            $this->failedLinks++;
                            $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                            $this->log("Added to failed links due to database error: $url - {$e->getMessage()}", self::COLOR_RED);
                        }
                    } else {
                        $this->failedLinks++;
                        $reason = "Invalid product data: " . json_encode($productData, JSON_UNESCAPED_UNICODE);
                        $this->saveFailedLink($url, $reason);
                        $this->log("Added to failed links due to invalid product data: $url - $reason", self::COLOR_RED);
                    }

                    usleep(rand($this->config['request_delay_min'] ?? 1000, $this->config['request_delay_max'] ?? 3000) * 1000);
                }
            }
        } else {
            throw new \Exception("Invalid processing method specified: $method. Use 1, 2, or 3.");
        }

        $this->retryFailedLinks();

        $this->log("Batch processing completed. Processed: {$this->processedCount}, Failed: {$this->failedLinks}", self::COLOR_GREEN);

        return [
            'processed' => $this->processedCount,
            'failed' => $this->failedLinks,
            'pages_processed' => 0
        ];
    }

    private function saveFailedLink(string $url, string $errorMessage): void
    {
        try {
            FailedLink::updateOrCreate(
                ['url' => $url],
                [
                    'attempts' => DB::raw('attempts + 1'),
                    'error_message' => $errorMessage,
                ]
            );
            $this->log("Saved failed link: $url with error: $errorMessage", self::COLOR_YELLOW);
        } catch (\Exception $e) {
            $this->log("Failed to save link $url to database: {$e->getMessage()}", self::COLOR_RED);
        }
    }

    public function setOutputCallback(callable $callback): void
    {
        $this->outputCallback = $callback;
    }

    private function randomUserAgent(): string
    {
        $agents = [
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/37.0.2062.94 Chrome/37.0.2062.94 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/600.8.9 (KHTML, like Gecko) Version/8.0.8 Safari/600.8.9',
            'Mozilla/5.0 (iPad; CPU OS 8_4_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12H321 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/600.8.9 (KHTML, like Gecko) Version/7.1.8 Safari/537.85.17',
            'Mozilla/5.0 (iPad; CPU OS 8_4 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12H143 Safari/600.1.4',
            'Mozilla/5.0 (iPad; CPU OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12F69 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.1; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 5.1; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/600.6.3 (KHTML, like Gecko) Version/8.0.6 Safari/600.6.3',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/600.5.17 (KHTML, like Gecko) Version/8.0.5 Safari/600.5.17',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 8_4_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12H321 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (iPad; CPU OS 7_1_2 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D257 Safari/9537.53',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
            'Mozilla/5.0 (Windows NT 6.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (X11; CrOS x86_64 7077.134.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.156 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/7.1.7 Safari/537.85.16',
            'Mozilla/5.0 (Windows NT 6.0; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (iPad; CPU OS 8_1_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B466 Safari/600.1.4',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.3.18 (KHTML, like Gecko) Version/8.0.3 Safari/600.3.18',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 8_1_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B440 Safari/600.1.4',
            'Mozilla/5.0 (Linux; U; Android 4.0.3; en-us; KFTT Build/IML74K) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 8_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12D508 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
            'Mozilla/5.0 (iPad; CPU OS 7_1_1 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D201 Safari/9537.53',
            'Mozilla/5.0 (Linux; U; Android 4.4.3; en-us; KFTHWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/600.6.3 (KHTML, like Gecko) Version/7.1.6 Safari/537.85.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.4.10 (KHTML, like Gecko) Version/8.0.4 Safari/600.4.10',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',
            'Mozilla/5.0 (iPad; CPU OS 8_4_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) CriOS/45.0.2454.68 Mobile/12H321 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.3; Win64; x64; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B410 Safari/600.1.4',
            'Mozilla/5.0 (iPad; CPU OS 7_0_4 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11B554a Safari/9537.53',
            'Mozilla/5.0 (Windows NT 6.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; Win64; x64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; TNJB; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; ARM; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; MDDCJS; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 8_4 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12H143 Safari/600.1.4',
            'Mozilla/5.0 (Linux; U; Android 4.4.3; en-us; KFASWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 8_4_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) GSA/7.0.55539 Mobile/12H321 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12F70 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; MATBJS; rv:11.0) like Gecko',
            'Mozilla/5.0 (Linux; U; Android 4.0.4; en-us; KFJWI Build/IMM76D) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 7_1 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D167 Safari/9537.53',
            'Mozilla/5.0 (X11; CrOS armv7l 7077.134.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.156 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10) AppleWebKit/600.1.25 (KHTML, like Gecko) Version/8.0 Safari/600.1.25',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.2.5 (KHTML, like Gecko) Version/8.0.2 Safari/600.2.5',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.1.25 (KHTML, like Gecko) Version/8.0 Safari/600.1.25',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:39.0) Gecko/20100101 Firefox/39.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.56 (KHTML, like Gecko) Version/9.0 Safari/601.1.56',
            'Mozilla/5.0 (Linux; U; Android 4.4.3; en-us; KFSOWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 5_1_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B206 Safari/7534.48.3',
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 8_1_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B435 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240',
            'Mozilla/5.0 (Windows NT 6.3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; Touch; LCJB; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; MDDRJS; rv:11.0) like Gecko',
            'Mozilla/5.0 (Linux; U; Android 4.4.3; en-us; KFAPWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; Touch; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; LCJB; rv:11.0) like Gecko',
            'Mozilla/5.0 (Linux; U; Android 4.0.3; en-us; KFOT Build/IML74K) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 6_1_3 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10B329 Safari/8536.25',
            'Mozilla/5.0 (Linux; U; Android 4.4.3; en-us; KFARWI Build/KTU84M) AppleWebKit/537.36 (KHTML, like Gecko) Silk/3.68 like Chrome/39.0.2171.93 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; ASU2JS; rv:11.0) like Gecko',
            'Mozilla/5.0 (iPad; CPU OS 8_0_2 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12A405 Safari/600.1.4',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.77.4 (KHTML, like Gecko) Version/7.0.5 Safari/537.77.4',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; rv:38.0) Gecko/20100101 Firefox/38.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; yie11; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; MALNJS; rv:11.0) like Gecko',
            'Mozilla/5.0 (iPad; CPU OS 8_4_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) GSA/8.0.57838 Mobile/12H321 Safari/600.1.4',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
            'Mozilla/5.0 (Windows NT 10.0; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; MAGWJS; rv:11.0) like Gecko',
            'Mozilla/5.0 (X11; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/600.5.17 (KHTML, like Gecko) Version/7.1.5 Safari/537.85.14',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.152 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; Touch; TNJB; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; NP06; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.155 Safari/537.36 OPR/31.0.1889.174',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.4.8 (KHTML, like Gecko) Version/8.0.3 Safari/600.4.8',
            'Mozilla/5.0 (iPad; CPU OS 7_0_6 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11B651 Safari/9537.53',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            'Microsoft Office/16.0 (Windows NT 10.0; Microsoft Outlook 16.0.17928; Pro)',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 OPR/110.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2.1 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:126.0) Gecko/20100101 Firefox/126.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:124.0) Gecko/20100101 Firefox/124.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:127.0) Gecko/20100101 Firefox/127.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 Edg/119.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36 Edg/118.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36 Edg/117.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/118.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36 Edg/116.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36 Edg/115.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 Edg/114.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/120.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/112.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36 Edg/112.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36 Edg/110.0.1587.63',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36 Edg/111.0.1661.62',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/110.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/111.0',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36 Edg/109.0.1518.78',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36 OPR/94.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/119.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36 Edg/108.0.1462.54',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/109.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0;

 Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:119.0) Gecko/20100101 Firefox/119.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:118.0) Gecko/20100101 Firefox/118.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:117.0) Gecko/20100101 Firefox/117.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:116.0) Gecko/20100101 Firefox/116.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:114.0) Gecko/20100101 Firefox/114.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:113.0) Gecko/20100101 Firefox/113.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:112.0) Gecko/20100101 Firefox/112.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:111.0) Gecko/20100101 Firefox/111.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:110.0) Gecko/20100101 Firefox/110.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/109.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:106.0) Gecko/20100101 Firefox/106.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:105.0) Gecko/20100101 Firefox/105.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:104.0) Gecko/20100101 Firefox/104.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:103.0) Gecko/20100101 Firefox/103.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:101.0) Gecko/20100101 Firefox/101.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:98.0) Gecko/20100101 Firefox/98.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:96.0) Gecko/20100101 Firefox/96.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:95.0) Gecko/20100101 Firefox/95.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:94.0) Gecko/20100101 Firefox/94.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:93.0) Gecko/20100101 Firefox/93.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:90.0) Gecko/20100101 Firefox/90.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:88.0) Gecko/20100101 Firefox/88.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:87.0) Gecko/20100101 Firefox/87.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:85.0) Gecko/20100101 Firefox/85.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:81.0) Gecko/20100101 Firefox/81.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:79.0) Gecko/20100101 Firefox/79.0'
        ];
        return $agents[array_rand($agents)];
    }

    public function setRequestDelay(int $delay): void
    {
        $this->config['request_delay_min'] = $delay;
        $this->config['request_delay_max'] = $delay;
    }

    public function scrape(): array
    {
        $this->log("Starting scrape process...", self::COLOR_GREEN);

        $links = [];
        $pagesProcessed = 0;

        if ($this->config['method'] === 2) {
            $this->log("Using method 2 to collect product links...", self::COLOR_GREEN);
            $result = $this->fetchProductLinks();
            $links = $result['links'] ?? [];
            $pagesProcessed = $result['pages_processed'] ?? 0;
            $this->log("Collected " . count($links) . " unique product links from method 2.", self::COLOR_GREEN);
            $this->log("Links structure: " . json_encode($links, JSON_PRETTY_PRINT), self::COLOR_YELLOW); // لاگ جدید

            if (!empty($links)) {
                $this->log("Processing " . count($links) . " links using method 1 for extraction...", self::COLOR_GREEN);
                $batchResult = $this->processPagesInBatches($links, 1);
                $pagesProcessed += $batchResult['pages_processed'] ?? 0;
            } else {
                $this->log("No links collected by method 2.", self::COLOR_YELLOW);
            }
        } elseif ($this->config['method'] === 1) {
            $this->log("Using method 1 to collect and process links...", self::COLOR_GREEN);
            $result = $this->scrapeMethodOneForUrl();
            $links = $result['links'] ?? [];
            $this->log("Links structure: " . json_encode($links, JSON_PRETTY_PRINT), self::COLOR_YELLOW);
            $pagesProcessed = $result['pages_processed'] ?? 0;
            $this->log("Collected " . count($links) . " links from method 1.", self::COLOR_GREEN);

            if (!empty($links)) {
                $processingMethod = $this->config['processing_method'] ?? $this->config['method'];
                $this->log("Processing " . count($links) . " links using processing method: $processingMethod", self::COLOR_GREEN);
                $batchResult = $this->processPagesInBatches($links, $processingMethod);
                $pagesProcessed += $batchResult['pages_processed'] ?? 0;
            } else {
                $this->log("No links to process.", self::COLOR_YELLOW);
            }
        } elseif ($this->config['method'] === 3) {
            $this->log("Using method 3 to collect and process links...", self::COLOR_GREEN);
            $result = $this->scrapeMethodThree();
            $links = $result['links'] ?? [];
            $pagesProcessed = $result['pages_processed'] ?? 0;
            $this->log("Collected " . count($links) . " links from method 3.", self::COLOR_GREEN);

            if (!empty($links)) {
                $processingMethod = $this->config['processing_method'] ?? $this->config['method'];
                $this->log("Processing " . count($links) . " links using processing method: $processingMethod", self::COLOR_GREEN);
                $batchResult = $this->processPagesInBatches($links, $processingMethod);
                $pagesProcessed += $batchResult['pages_processed'] ?? 0;
            } else {
                $this->log("No links to process.", self::COLOR_YELLOW);
            }

            $this->log("Method 3 processing completed. Exiting scrape.", self::COLOR_GREEN);
        } else {
            throw new \Exception("Invalid method specified in config: {$this->config['method']}. Use 1, 2, or 3.");
        }

        $this->log("Scrape completed. Processed: {$this->processedCount}, Failed: {$this->failedLinks}, Pages: $pagesProcessed", self::COLOR_GREEN);

        return [
            'processed' => $this->processedCount,
            'failed' => $this->failedLinks,
            'pages_processed' => $pagesProcessed
        ];
    }

    public function scrapeMultiple(): array
    {
        $this->log("Starting scraper...", self::COLOR_GREEN);

        // تنظیم دیتابیس
        $this->setupDatabase();

        // بررسی تعداد products_urls
        $productsUrlsCount = count($this->config['products_urls']);
        $this->log("Found $productsUrlsCount products_urls to process", self::COLOR_PURPLE);

        // تنظیم اولیه
        $this->processedCount = 0;
        $this->failedLinks = 0;

        // جمع‌آوری لینک‌های محصولات از تمام products_urls
        $result = $this->fetchProductLinks();

        // پردازش لینک‌های جمع‌آوری‌شده
        $this->log("Processing all collected links...", self::COLOR_GREEN);
        $processingMethod = $this->config['processing_method'] ?? $this->config['method'];
        $processedResult = $this->processPagesInBatches($result['links'], $processingMethod);

        $this->log("Scraping completed!", self::COLOR_GREEN);
        $this->log("Processed: {$processedResult['processed']}, Failed: {$processedResult['failed']}, Pages processed: {$result['pages_processed']}", self::COLOR_GREEN);

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
            'total_products' => $processedResult['processed'],
            'failed_links' => $processedResult['failed'],
            'total_pages_count' => $result['pages_processed'],
            'products' => $products
        ];
    }

    private function fetchPageContent(string $url, bool $useDeep, bool $isProductPage = true): ?string
    {
        $maxRetries = $this->config['max_retries'] ?? 3;
        $attempt = 1;


        while ($attempt <= $maxRetries) {
            $userAgent = $this->randomUserAgent();
            $this->log("Attempt $attempt of $maxRetries to fetch URL: $url", self::COLOR_GREEN);
            try {
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
                        'Referer' => $this->config['base_urls'][0],
                        'Connection' => 'keep-alive',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $this->log("Received response with status code: $statusCode for $url", self::COLOR_GREEN);

                $finalUrl = $response->getHeaderLine('X-Guzzle-Redirect-History')
                    ? end($response->getHeader('X-Guzzle-Redirect-History'))
                    : $url;

                $normalizedUrl = $this->normalizeUrl($url);
                $normalizedFinalUrl = $this->normalizeUrl($finalUrl);

                $ignoreRedirects = $this->config['method_settings']['method_1']['pagination']['ignore_redirects'] ?? false;

                if (!$ignoreRedirects && $normalizedUrl !== $normalizedFinalUrl) {
                    $this->log("Redirect detected: Requested $url but got $finalUrl. Attempting to continue.", self::COLOR_YELLOW);
                    $url = $finalUrl; // ادامه با URL ریدایرکت‌شده
                    $attempt++;
                    continue;
                }

                $body = (string)$response->getBody();
                $this->log("Response body length: " . strlen($body) . " bytes for $url", self::COLOR_GREEN);

                if (empty($body)) {
                    $this->log("Empty response body received for $url. Retrying...", self::COLOR_YELLOW);
                    $attempt++;
                    continue;
                }

                return $body;
            } catch (RequestException $e) {
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
                $responseBody = $e->hasResponse() ? substr((string)$e->getResponse()->getBody(), 0, 500) : 'No response';
                $this->log("Request failed for $url on attempt $attempt: {$e->getMessage()}. Status code: $statusCode. Response: $responseBody", self::COLOR_RED);
                if ($attempt < $maxRetries) {
                    $delay = $this->exponentialBackoff($attempt);
                    $this->log("Retrying after $delay ms...", self::COLOR_YELLOW);
                    usleep($delay * 1000);
                }
                $attempt++;
            } catch (\Exception $e) {
                $this->log("Unexpected error fetching $url on attempt $attempt: {$e->getMessage()}", self::COLOR_RED);
                return null;
            }
        }

        $this->log("Failed to fetch $url after $maxRetries attempts. Skipping page.", self::COLOR_RED);
        return null;
    }

    private function exponentialBackoff(int $attempt): int
    {
        return (int)(100 * pow(2, $attempt - 1)); // تأخیر تصاعدی: 100ms, 200ms, 400ms
    }

    private function normalizeUrl(string $url): string
    {
        // تجزیه URL به اجزای آن
        $parsed = parse_url($url);
        if (!$parsed) {
            return $url; // در صورت نامعتبر بودن URL، همان را برگردان
        }

        // استخراج اجزا
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : 'https://';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        // نرمال‌سازی مسیر: حذف اسلش‌های اضافی و تبدیل به فرمت ثابت
        $path = rtrim($path, '/') . '/'; // همیشه یک اسلش در انتها داشته باشد
        $path = preg_replace('/\/+/', '/', $path); // حذف اسلش‌های اضافی

        // نرمال‌سازی query string: حذف اسلش قبل از query string
        $queryPart = $query ? '?' . $query : '';

        // بازسازی URL نرمال‌شده
        $normalizedUrl = $scheme . $host . $path . $queryPart;

        $this->log("Normalized URL: $url -> $normalizedUrl", self::COLOR_YELLOW);
        return $normalizedUrl;
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

        // جایگزینی قسمت‌های داینامیک مثل "صفحه-شماره"
        if (strpos($basePart, "صفحه-$pageNumber") !== false) {
            $basePart = str_replace("صفحه-$pageNumber", "صفحه-{page}", $basePart);
        }

        return $basePart;
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
            $baseUrl .= '/'; // اضافه کردن اسلش در هر دو حالت query و path
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

    private function isUnwantedDomain(string $url): bool
    {
        $unwantedDomains = [
            'telegram.me',
            't.me',
            'wa.me',
            'whatsapp.com',
            'aparat.com',
            'rubika.ir',
            'sapp.ir', // پیام‌رسان سروش
            'igap.net', // پیام‌رسان ایتا
            'bale.ai', // پیام‌رسان بله
        ];

        $parsedUrl = parse_url($url, PHP_URL_HOST);
        if (!$parsedUrl) {
            return true; // اگه URL معتبر نبود، ردش کن
        }

        foreach ($unwantedDomains as $domain) {
            if (stripos($parsedUrl, $domain) !== false) {
                $this->log("Skipping unwanted domain: $url", self::COLOR_YELLOW);
                return true;
            }
        }

        return false;
    }

    private function fetchProductLinks(): array
    {
        $method = $this->config['method'] ?? 1;
        $this->log("Fetching product links using method: $method", self::COLOR_GREEN);

        if (!isset($this->config['selectors']['main_page']['product_links'])) {
            throw new \Exception("Main page product_links selector is required.");
        }

        $allLinks = [];
        $totalPagesProcessed = 0;
        $processedUrls = [];

        // برای روش ۳، مستقیماً scrapeMethodThree را فراخوانی کنید و حلقه را حذف کنید
        if ($method === 3) {
            $this->log("Using scrapeMethodThree for method 3...", self::COLOR_GREEN);
            $result = $this->scrapeMethodThree();
            $allLinks = $result['links'] ?? [];
            $totalPagesProcessed = $result['pages_processed'] ?? 0;

            $this->log("Total links collected from method 3: " . count($allLinks), self::COLOR_GREEN);
        } else {
            // پردازش اولیه برای روش‌های ۱ و ۲
            foreach ($this->config['products_urls'] as $index => $productUrl) {
                $normalizedUrl = $this->normalizeUrl($productUrl);
                if (in_array($normalizedUrl, $processedUrls)) {
                    $this->log("Skipping duplicate products_url: $productUrl", self::COLOR_YELLOW);
                    continue;
                }
                $processedUrls[] = $normalizedUrl;

                $this->log("Processing products_url " . ($index + 1) . ": $productUrl", self::COLOR_PURPLE);

                $result = match ($method) {
                    1 => $this->scrapeMethodOneForUrl($productUrl),
                    2 => $this->scrapeWithPlaywright(2, $productUrl),
                    default => throw new \Exception("Invalid method specified in config: $method. Use 1 or 2."),
                };

                $rawLinks = $result['links'] ?? [];
                $pagesProcessed = $result['pages_processed'] ?? 0;
                $totalPagesProcessed += $pagesProcessed;

                $this->log("Found " . count($rawLinks) . " links from $productUrl", self::COLOR_GREEN);

                $filteredLinks = array_filter($rawLinks, function ($link) use (&$allLinks) {
                    $url = is_array($link) ? ($link['url'] ?? '') : $link;
                    $isValid = $url && !$this->isUnwantedDomain($url) && !in_array($url, array_column($allLinks, 'url'));
                    if (!$isValid) {
                        $this->log("Filtered out invalid, unwanted, or duplicate link: $url", self::COLOR_YELLOW);
                    }
                    return $isValid;
                });

                $allLinks = array_merge($allLinks, $filteredLinks);
            }
        }

        // مرحله تلاش مجدد برای لینک‌های شکست‌خورده (برای روش‌های ۱ و ۲)
        if ($method !== 3 && !empty($this->failedUrlsDueToInternalError)) {
            $this->log("Retrying " . count($this->failedUrlsDueToInternalError) . " URLs that failed due to internal errors...", self::COLOR_PURPLE);

            foreach ($this->failedUrlsDueToInternalError as $index => $failedUrl) {
                $normalizedUrl = $this->normalizeUrl($failedUrl);
                if (in_array($normalizedUrl, $processedUrls)) {
                    $this->log("Skipping already processed URL during retry: $failedUrl", self::COLOR_YELLOW);
                    continue;
                }
                $processedUrls[] = $normalizedUrl;

                $this->log("Retrying URL " . ($index + 1) . ": $failedUrl", self::COLOR_PURPLE);

                $result = match ($method) {
                    1 => $this->scrapeMethodOneForUrl($failedUrl),
                    2 => $this->scrapeWithPlaywright(2, $failedUrl),
                    default => throw new \Exception("Invalid method specified in config: $method. Use 1 or 2."),
                };

                $rawLinks = $result['links'] ?? [];
                $pagesProcessed = $result['pages_processed'] ?? 0;
                $totalPagesProcessed += $pagesProcessed;

                $this->log("Found " . count($rawLinks) . " links from $failedUrl during retry", self::COLOR_GREEN);

                $filteredLinks = array_filter($rawLinks, function ($link) use (&$allLinks) {
                    $url = is_array($link) ? ($link['url'] ?? '') : $link;
                    $isValid = $url && !$this->isUnwantedDomain($url) && !in_array($url, array_column($allLinks, 'url'));
                    if (!$isValid) {
                        $this->log("Filtered out invalid, unwanted, or duplicate link: $url", self::COLOR_YELLOW);
                    }
                    return $isValid;
                });

                $allLinks = array_merge($allLinks, $filteredLinks);
            }

            // پاک کردن آرایه بعد از تلاش مجدد
            $this->failedUrlsDueToInternalError = [];
        }

        // حذف لینک‌های تکراری
        $uniqueLinks = [];
        $urlSet = [];
        foreach ($allLinks as $link) {
            $url = is_array($link) ? $link['url'] : $link;
            if (!in_array($url, $urlSet)) {
                $urlSet[] = $url;
                $uniqueLinks[] = $link;
            }
        }

        $this->log("Total unique links collected: " . count($uniqueLinks), self::COLOR_GREEN);

        return [
            'links' => array_values($uniqueLinks),
            'pages_processed' => $totalPagesProcessed
        ];
    }

    private function retryFailedLinks(): void
    {
        $maxAttempts = $this->config['max_retry_attempts'] ?? 3;
        $failedLinks = FailedLink::where('attempts', '<', $maxAttempts)->get();

        if ($failedLinks->isEmpty()) {
            $this->log("No failed links to retry.", self::COLOR_GREEN);
            return;
        }

        $this->log("Retrying " . $failedLinks->count() . " failed links...", self::COLOR_PURPLE);

        $requests = function () use ($failedLinks) {
            foreach ($failedLinks as $link) {
                yield new Request('GET', $link->url);
            }
        };

        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency' => $this->config['concurrency'] ?? 5,
            'fulfilled' => function ($response, $index) use ($failedLinks) {
                $link = $failedLinks[$index];
                $url = $link->url;
                $productData = $this->extractProductData($url, (string)$response->getBody());
                if ($productData && $this->validateProductData($productData)) {
                    $this->saveProductToDatabase($productData);
                    $this->logProduct($productData);
                    $link->delete(); // حذف لینک از جدول بعد از موفقیت
                    $this->processedCount++;
                    $this->failedLinks--;
                } else {
                    $link->increment('attempts');
                    $this->log("Retry failed for $url: Invalid data extracted", self::COLOR_RED);
                }
            },
            'rejected' => function ($reason, $index) use ($failedLinks) {
                $link = $failedLinks[$index];
                $url = $link->url;
                $link->increment('attempts');
                $this->log("Retry failed for $url, saved to failed links", self::COLOR_YELLOW); // پیام ساده
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        // حذف لینک‌هایی که به حداکثر تلاش رسیدن
        $exhaustedLinks = FailedLink::where('attempts', '>=', $maxAttempts)->get();
        foreach ($exhaustedLinks as $link) {
            $this->log("Max attempts reached for $link->url. Giving up.", self::COLOR_RED);
            $link->delete();
        }
    }

    private function saveProductToDatabase(array $productData): void
    {
        $this->log("Raw availability before saving: {$productData['availability']}", self::COLOR_YELLOW);

        $validated = validator($productData, [
            'title' => 'required|string|max:500',
            'image' => 'nullable|string|max:1000',
        ])->validate();

        $productId = isset($productData['product_id']) && !empty($productData['product_id']) && $productData['product_id'] !== 'نامعلوم'
            ? $productData['product_id']
            : '';

        Product::updateOrCreate(
            [
                'page_url' => $productData['page_url'],
            ],
            [
                'title' => $validated['title'],
                'price' => $productData['price'],
                'product_id' => $productId,
                'availability' => (int)$productData['availability'],
                'image' => $validated['image'],
                'category' => $productData['category'],
                'off' => (int)$productData['off'],
                'guarantee' => $productData['guarantee'],
            ]
        );

        $totalProducts = count($this->config['products_urls'] ?? []);
        $this->logProduct($productData, $totalProducts);
    }

    private function validateProductData(array $productData): bool
    {
        if (empty($productData['title'])) {
            $this->log("Validation failed: title is empty for URL: {$productData['page_url']}", self::COLOR_RED);
            return false;
        }

        // لاگ کردن داده‌های محصول برای دیباگ
        $this->log("Validating product data: " . json_encode($productData, JSON_UNESCAPED_UNICODE), self::COLOR_YELLOW);

        // اگر محصول ناموجود است، نیازی به بررسی قیمت نیست
        if ($productData['availability'] == 0) {
            $this->log("Product is unavailable, skipping price validation for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        // بررسی وضعیت "قیمت‌گذاری نشده"
        if (isset($productData['price_status']) && $productData['price_status'] == 'unpriced') {
            $this->log("Product has no price but is marked as 'unpriced'. Accepting product for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        // بررسی متن قیمت برای کلمات کلیدی "قیمت‌گذاری نشده" یا "تماس بگیرید"
        if (empty($productData['price']) && isset($productData['price_text']) &&
            (strpos($productData['price_text'], 'قیمت‌گذاری نشده') !== false ||
                strpos($productData['price_text'], 'قیمت تعیین نشده') !== false ||
                strpos($productData['price_text'], 'تماس بگیرید') !== false)) {
            $this->log("Product has price text indicating 'unpriced'. Accepting product for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        // اگر قیمت خالی است، هشدار می‌دهیم اما محصول را می‌پذیریم
        if (empty($productData['price'])) {
            $this->log("Warning: price is empty for available product, but product will be saved for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        $this->log("Product data validated successfully for URL: {$productData['page_url']}", self::COLOR_GREEN);
        return true;
    }

    private function extractProductData(string $url, ?string $body = null, ?string $mainPageImage = null, ?string $mainPageProductId = null): ?array
    {
        $data = [
            'title' => '',
            'price' => $this->config['keep_price_format'] ?? false ? '' : '0',
            'product_id' => $mainPageProductId ?? '',
            'page_url' => $url,
            'availability' => 0,
            'image' => $mainPageImage ?? '',
            'category' => '',
            'off' => 0,
            'guarantee' => ''
        ];

        try {
            $usePython = ($this->config['method'] ?? 1) === 3;

            if ($body === null) {
                if ($usePython) {
                    $this->log("Fetching product page with Python for: $url", self::COLOR_GREEN);
                    $body = $this->fetchPageContent($url, true);
                    if ($body === null) {
                        throw new \Exception("Python script returned null or failed for $url");
                    }
                } else {
                    $this->log("Fetching product page with Guzzle for: $url", self::COLOR_YELLOW);
                    $body = $this->fetchPageContent($url, false);
                    if ($body === null) {
                        throw new \Exception("Failed to fetch $url with Guzzle");
                    }
                }
            }

            $crawler = new Crawler($body);
            $productSelectors = $this->config['selectors']['product_page'] ?? [];

            foreach ($productSelectors as $field => $selector) {
                if (!empty($selector['selector']) && array_key_exists($field, $data)) {
                    if ($field === 'guarantee') {
                        $data[$field] = $this->extractGuaranteeFromSelector($crawler, $selector, $data['title']);
                    } elseif ($field === 'category' && ($this->config['category_method'] ?? 'selector') === 'selector') {
                        $value = $this->extractData($crawler, $selector);
                        $data[$field] = $value;
                        $this->log("Extracted category from selector: {$data[$field]}", self::COLOR_GREEN);
                    } elseif ($field === 'image' && $this->config['image_method'] === 'product_page') {
                        $value = $this->extractData($crawler, $selector);
                        $data[$field] = $this->makeAbsoluteUrl($value);
                        $this->log("Extracted image from product_page: {$data[$field]}", self::COLOR_GREEN);
                    } elseif ($field === 'product_id' && $this->config['product_id_source'] === 'product_page') {
                        $value = $this->extractData($crawler, $selector);
                        $data[$field] = $value;
                        $this->log("Extracted product_id from product_page: {$data[$field]}", self::COLOR_GREEN);
                    } else {
                        $value = $this->extractData($crawler, $selector);
                        $this->log("Raw $field extracted: '$value'", self::COLOR_YELLOW);


                        if ($field === 'price') {
                            // منطق اختصاصی برای فیلد price
                            $priceSelectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];
                            $value = '';

                            // ابتدا سلکتور اول را امتحان می‌کنیم
                            if (isset($priceSelectors[0])) {
                                $this->log("Trying primary price selector: '{$priceSelectors[0]}'", self::COLOR_YELLOW);
                                $elements = $selector['type'] === 'css' ? $crawler->filter($priceSelectors[0]) : $crawler->filterXPath($priceSelectors[0]);
                                if ($elements->count() > 0) {
                                    $value = trim($elements->text());
                                    $this->log("Price extracted from primary selector: '$value'", self::COLOR_GREEN);
                                } else {
                                    $this->log("No price found with primary selector: '{$priceSelectors[0]}'", self::COLOR_YELLOW);
                                }
                            }

                            // اگر سلکتور اول خالی بود یا نتیجه‌ای نداشت، سلکتور دوم را امتحان می‌کنیم
                            if (empty($value) && isset($priceSelectors[1])) {
                                $this->log("Trying secondary price selector: '{$priceSelectors[1]}'", self::COLOR_YELLOW);
                                $elements = $selector['type'] === 'css' ? $crawler->filter($priceSelectors[1]) : $crawler->filterXPath($priceSelectors[1]);
                                if ($elements->count() > 0) {
                                    $value = trim($elements->text());
                                    $this->log("Price extracted from secondary selector: '$value'", self::COLOR_GREEN);
                                } else {
                                    $this->log("No price found with secondary selector: '{$priceSelectors[1]}'", self::COLOR_YELLOW);
                                }
                            }

                            // پردازش قیمت استخراج‌شده
                            $priceKeywords = $this->config['price_keywords']['unpriced'] ?? [];
                            $isUnpriced = false;
                            foreach ($priceKeywords as $keyword) {
                                if (!empty($value) && strpos($value, $keyword) !== false) {
                                    $isUnpriced = true;
                                    $data[$field] = trim($value);
                                    $this->log("Price is marked as unpriced text: '$value'", self::COLOR_YELLOW);
                                    break;
                                }
                            }

                            if (!$isUnpriced && !empty($value)) {
                                if ($this->config['keep_price_format'] ?? false) {
                                    $data[$field] = $this->cleanPriceWithFormat($value);
                                } else {
                                    $data[$field] = (string)$this->cleanPrice($value);
                                }
                            } else if (!$isUnpriced) {
                                $data[$field] = $this->config['keep_price_format'] ?? false ? '' : '0';
                                $this->log("No valid price found, setting default: '{$data[$field]}'", self::COLOR_YELLOW);
                            }
                        } elseif ($field === 'availability') {
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = (int)$this->$transform($value, $crawler);
                            } else {
                                $data[$field] = (string)$value;
                            }
                        } elseif ($field === 'off') {
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = (int)$this->$transform($value);
                            } else {
                                $data[$field] = (string)$value;
                            }
                        } else {
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = (string)$this->$transform($value);
                            } else {
                                $data[$field] = (string)$value;
                            }
                        }

                        $this->log("Extracted $field: \"{$data[$field]}\" for $url", self::COLOR_GREEN);
                    }
                }
            }

            // استخراج دسته‌بندی از عنوان اگر روش روی title تنظیم شده باشد
            if (($this->config['category_method'] ?? 'selector') === 'title' && !empty($data['title'])) {
                $wordCount = $this->config['category_word_count'] ?? 1;
                $data['category'] = $this->extractCategoryFromTitle($data['title'], $wordCount);
                $this->log("Extracted category from title: {$data['category']}", self::COLOR_GREEN);
            }

            // بقیه منطق متد بدون تغییر باقی می‌ماند
            if ($data['availability'] === '') {
                $addToCartSelector = $this->config['selectors']['product_page']['add_to_cart_button'] ?? null;
                $outOfStockSelector = $this->config['selectors']['product_page']['out_of_stock'] ?? null;

                if ($addToCartSelector && $crawler->filter($addToCartSelector)->count() > 0) {
                    $data['availability'] = 1;
                    $this->log("Product availability determined by add-to-cart button: Available", self::COLOR_GREEN);
                } elseif ($outOfStockSelector && $crawler->filter($outOfStockSelector)->count() > 0) {
                    $data['availability'] = 0;
                    $this->log("Product availability determined by out-of-stock indicator: Unavailable", self::COLOR_RED);
                } elseif (!empty($data['price']) && $data['price'] != '0') {
                    $data['availability'] = 1;
                    $this->log("Product with price considered available", self::COLOR_GREEN);
                } else {
                    $data['availability'] = 0;
                    $this->log("Product with no price and no availability indicators considered unavailable", self::COLOR_RED);
                }
            } else {
                $data['availability'] = (int)$data['availability'];
            }

            $data['off'] = (int)$data['off'];

            foreach ($data as $key => $value) {
                if ($key !== 'availability' && $key !== 'off' && is_numeric($value)) {
                    $data[$key] = (string)$value;
                }
            }

            if (!$this->validateProductData($data)) {
                $this->log("No valid data extracted for $url. Adding to failed links.", self::COLOR_RED);
                $this->saveFailedLink($url, "No valid data extracted");
                return null;
            }

            $this->logProduct($data);
            return $data;

        } catch (\Exception $e) {
            $this->log("Failed to process $url: {$e->getMessage()}. Adding to failed links.", self::COLOR_RED);
            $this->saveFailedLink($url, $e->getMessage());
            return null;
        }
    }

    private function extractGuaranteeFromSelector(Crawler $crawler, array $selector, ?string $title = null): string
    {
        $method = $this->config['guarantee_method'] ?? 'selector';
        $keywords = $this->config['guarantee_keywords'] ?? ['گارانتی', 'ضمانت'];

        if ($method === 'selector' && !empty($selector['selector'])) {
            $elements = $this->getElements($crawler, $selector);
            if ($elements->count() > 0) {
                $text = trim($elements->text());
                $this->log("Guarantee extracted from selector '{$selector['selector']}': '$text'", self::COLOR_GREEN);
                return $this->cleanGuarantee($text); // کل متن سلکتور را برمی‌گرداند
            }
            $this->log("No elements found for guarantee selector: '{$selector['selector']}'", self::COLOR_YELLOW);
            return '';
        } elseif ($method === 'title' && $title) {
            foreach ($keywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $this->log("Guarantee found in title: '$title'", self::COLOR_GREEN);
                    return $this->cleanGuarantee($title);
                }
            }
            $this->log("No guarantee found in title", self::COLOR_YELLOW);
            return '';
        }

        $this->log("No guarantee found", self::COLOR_YELLOW);
        return '';
    }

    private function extractData(Crawler $crawler, array $selector, ?string $field = null): string
    {
        $selectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];
        $value = '';

        foreach ($selectors as $index => $sel) {
            $this->log("Trying selector [$index]: '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_YELLOW);
            $elements = $selector['type'] === 'css' ? $crawler->filter($sel) : $crawler->filterXPath($sel);
            if ($elements->count() > 0) {
                $value = $selector['attribute'] ?? false
                    ? ($elements->attr($selector['attribute']) ?? '')
                    : trim($elements->text());
                if (!empty($value)) {
                    $this->log("Found value: '$value' with selector '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_GREEN);
                    break; // اگر مقدار معتبر پیدا شد، از حلقه خارج شو
                }
            }
            $this->log("No value found with selector '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_YELLOW);
        }

        if (empty($value)) {
            $this->log("No value found for selectors: " . json_encode($selectors) . " for field: " . ($field ?? 'unknown'), self::COLOR_RED);
        }
        return $value;
    }

    private function getElements(Crawler $crawler, array $selector): Crawler
    {
        $selectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];
        $crawlerResult = new Crawler();

        // اگر روش category روی title باشد و سلکتور خالی، نیازی به بررسی سلکتور نیست
        if (isset($this->config['category_method']) && $this->config['category_method'] === 'title' && empty($selector['selector'])) {
            $this->log("Category method is 'title', skipping selector check", self::COLOR_YELLOW);
            return $crawlerResult;
        }

        foreach ($selectors as $sel) {
            $this->log("Trying selector in getElements: '$sel'", self::COLOR_YELLOW);
            $elements = $selector['type'] === 'css' ? $crawler->filter($sel) : $crawler->filterXPath($sel);
            if ($elements->count() > 0) {
                $this->log("Found elements with selector '$sel'", self::COLOR_GREEN);
                return $elements;
            }
        }

        $this->log("No elements found for selectors: " . json_encode($selectors), self::COLOR_YELLOW);
        return $crawlerResult;
    }

    private function isInvalidLink(?string $href): bool
    {
        return empty($href) || $href === '#' || stripos($href, 'javascript:') === 0;
    }

    private function makeAbsoluteUrl(string $href): string
    {
        // چک کردن لینک‌های نامعتبر
        if (empty($href) || $href === '#' || stripos($href, 'javascript:') === 0) {
            return '';
        }

        // اگه لینک از قبل مطلقه، فقط کاراکترهای فرمت‌شده رو پاک کن
        if (stripos($href, 'http://') === 0 || stripos($href, 'https://') === 0) {
            return urldecode($href);
        }

        // استفاده از اولین base_url
        $baseUrl = $this->config['base_urls'][0] ?? '';
        if (empty($baseUrl)) {
            $this->log("No base_url defined, cannot create absolute URL for: $href", self::COLOR_RED);
            return $href;
        }

        $baseUrl = rtrim($baseUrl, '/');
        $href = ltrim($href, '/');

        // ساخت URL کامل
        $fullUrl = "$baseUrl/$href";
        return urldecode($fullUrl);
    }

    private function cleanPrice(string $price): int
    {
        $this->log("Raw price input to cleanPrice: '$price'", self::COLOR_YELLOW); // دیباگ
        $price = preg_replace('/[^\d,٫]/u', '', $price);
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $price = str_replace($persian, $latin, $price);
        $cleaned = (int)str_replace([',', '٫'], '', $price);
        $this->log("Cleaned price: '$cleaned'", self::COLOR_YELLOW); // دیباگ
        return $cleaned;
    }

    private function cleanPriceWithFormat(string $price): string
    {
        $this->log("Raw price input to cleanPriceWithFormat: '$price'", self::COLOR_YELLOW);
        $prices = explode('–', $price);
        $cleanedPrices = [];

        foreach ($prices as $pricePart) {
            $cleaned = trim(preg_replace('/[^\d, تومان]/u', '', $pricePart));
            if (!empty($cleaned)) {
                $cleanedPrices[] = $cleaned;
            }
        }

        $this->log("Cleaned prices: " . json_encode($cleanedPrices), self::COLOR_YELLOW);
        if (count($cleanedPrices) > 1) {
            return implode(' - ', $cleanedPrices);
        } elseif (count($cleanedPrices) === 1) {
            return $cleanedPrices[0];
        }
        return '';
    }

    private function parseAvailability(string $text, Crawler $crawler): int
    {
        $availabilityMode = $this->config['availability_mode'] ?? 'keyword';
        $stockSelector = $this->config['selectors']['product_page']['availability'] ?? null;
        $positiveKeywords = $this->config['availability_keywords']['positive'] ?? ["در انبار موجود است", "موجود", "افزودن به سبد خرید", "افزودن به سبد", "تماس بگیرید", "برای استعلام قیمت تماس بگیرید"];
        $negativeKeywords = $this->config['availability_keywords']['negative'] ?? ["ناموجود", "اتمام موجودی"];

        if ($availabilityMode === 'selector_presence') {
            // حالت selector_presence: بررسی وجود سلکتور و کلمه کلیدی
            if (!$stockSelector || empty($stockSelector['selector'])) {
                $this->log("No availability selector defined, assuming available", self::COLOR_GREEN);
                return 1; // اگر سلکتور تعریف نشده، فرض بر موجود بودن
            }

            $keyword = $stockSelector['keyword'] ?? 'ناموجود';
            $selectors = is_array($stockSelector['selector']) ? $stockSelector['selector'] : [$stockSelector['selector']];
            $stockText = '';

            foreach ($selectors as $sel) {
                $this->log("Checking stock button with selector: $sel", self::COLOR_YELLOW);
                $elements = $crawler->filter($sel);
                if ($elements->count() > 0) {
                    $stockText = trim($elements->text());
                    $this->log("Stock button text: '$stockText'", self::COLOR_YELLOW);
                    break;
                }
            }
            $this->log("Parsing availability: " . json_encode($availability), self::COLOR_YELLOW);

            if (is_numeric($availability) && in_array((int)$availability, [0, 1])) {
                return (int)$availability;
            }

            $availabilityText = (string)$availability;
            if (empty($availabilityText)) {
                return 0;
            }
            if (!empty($positiveKeywords) && array_some($positiveKeywords, fn($keyword) => str_contains($availabilityText, $keyword))) {
                return 1;
            }
            if (!empty($negativeKeywords) && array_some($negativeKeywords, fn($keyword) => str_contains($availabilityText, $keyword))) {
                return 0;
            }

            if (!empty($stockText) && stripos($stockText, $keyword) !== false) {
                $this->log("Product is out of stock based on keyword: $keyword", self::COLOR_RED);
                return 0; // سلکتور وجود دارد و کلمه کلیدی یافت شد -> ناموجود
            }

            $this->log("Stock button not found or no matching keyword, assuming available", self::COLOR_GREEN);
            return 1; // سلکتور وجود ندارد یا کلمه کلیدی یافت نشد -> موجود
        } else {
            // حالت keyword: بررسی بر اساس کلمات کلیدی مثبت/منفی
            if (!$stockSelector || empty($stockSelector['selector'])) {
                $this->log("No availability selector defined, assuming out of stock", self::COLOR_YELLOW);
                return 0; // اگر سلکتور تعریف نشده، فرض بر ناموجود بودن
            }

            $selectors = is_array($stockSelector['selector']) ? $stockSelector['selector'] : [$stockSelector['selector']];
            $stockText = '';

            foreach ($selectors as $sel) {
                $this->log("Checking stock button with selector: $sel", self::COLOR_YELLOW);
                $elements = $crawler->filter($sel);
                if ($elements->count() > 0) {
                    $stockText = trim($elements->text());
                    $this->log("Stock button text: '$stockText'", self::COLOR_YELLOW);
                    break;
                }
            }

            if (!empty($stockText)) {
                // بررسی کلمات کلیدی مثبت
                foreach ($positiveKeywords as $keyword) {
                    if (stripos($stockText, $keyword) !== false) {
                        $this->log("Product is available based on keyword: $keyword", self::COLOR_GREEN);
                        return 1;
                    }
                }

                // بررسی کلمات کلیدی منفی
                foreach ($negativeKeywords as $keyword) {
                    if (stripos($stockText, $keyword) !== false) {
                        $this->log("Product is out of stock based on keyword: $keyword", self::COLOR_RED);
                        return 0;
                    }
                }

                // اگر متن شامل "تماس بگیرید" باشد، فرض بر موجود بودن
                if (stripos($stockText, 'تماس بگیرید') !== false) {
                    $this->log("Stock button text indicates 'call for availability', assuming available", self::COLOR_GREEN);
                    return 1;
                }

                // اگر هیچ کلمه کلیدی منفی پیدا نشد و سلکتور وجود دارد، فرض بر موجود بودن
                $this->log("Stock button exists with no negative keywords, assuming available", self::COLOR_GREEN);
                return 1;
            }

            $this->log("Stock button not found, assuming out of stock", self::COLOR_YELLOW);
            return 0;
        }
    }

    private function cleanOff(string $text): int
    {
        $this->log("Raw off value: '$text'", self::COLOR_YELLOW);
        $text = trim($text);
        if (strpos($text, '%') !== false) {
            $value = (int)str_replace('%', '', $text);
            $this->log("Processed off (percentage): $value", self::COLOR_GREEN);
            return $value;
        }
        preg_match('/\d+/', $text, $matches);
        if (!empty($matches)) {
            $value = (int)$matches[0];
            $this->log("Processed off (numeric): $value", self::COLOR_GREEN);
            return $value;
        }
        $this->log("No valid number found in off value, returning 0", self::COLOR_RED);
        return 0;
    }

    private function cleanGuarantee(string $text): string
    {
        $text = trim($text);
        $this->log("Cleaned guarantee: '$text'", self::COLOR_GREEN);
        return $text; // کل متن رو بدون تغییر خاص برمی‌گردونیم
    }

    private function extractProductIdFromUrl(string $url, string $title, Crawler $crawler): string
    {
        if ($this->config['product_id_method'] === 'url') {
            // اصلاح انکودینگ URL
            $url = str_replace('\\/', '/', $url);
            $this->log("Original URL: '$url'", self::COLOR_YELLOW);

            // الگوی پیش‌فرض یا تعریف‌شده در کانفیگ برای استخراج product_id
            $pattern = $this->config['product_id_url_pattern'] ?? 'products/(\d+)';
            $this->log("Pattern: '$pattern'", self::COLOR_YELLOW);

            // تست الگوی منظم
            try {
                $this->log("Testing pattern: '$pattern' on URL: '$url'", self::COLOR_YELLOW);
                if (preg_match("#$pattern#", $url, $matches)) {
                    $this->log("Matches: " . json_encode($matches), self::COLOR_GREEN);
                    $productId = $matches[1];
                    $this->log("Extracted product_id from URL with pattern: $productId for $url", self::COLOR_GREEN);
                    return $productId;
                } else {
                    $this->log("No product_id found in URL with pattern: $pattern for $url", self::COLOR_YELLOW);
                }
            } catch (\Exception $e) {
                $this->log("Invalid regex pattern '$pattern' for URL $url: {$e->getMessage()}", self::COLOR_RED);
            }

            // روش جایگزین: تجزیه مسیر URL
            $path = parse_url($url, PHP_URL_PATH);
            $parts = explode('/', trim($path, '/'));
            $this->log("URL path: '$path'", self::COLOR_YELLOW);
            $this->log("URL parts: " . json_encode($parts), self::COLOR_YELLOW);
            $productIndex = array_search('products', $parts);
            if ($productIndex !== false && isset($parts[$productIndex + 1])) {
                $potentialId = $parts[$productIndex + 1];
                if (is_numeric($potentialId)) {
                    $this->log("Extracted product_id from URL structure: $potentialId for $url", self::COLOR_GREEN);
                    return $potentialId;
                } else {
                    $this->log("No numeric product_id found in URL part: $potentialId for $url", self::COLOR_YELLOW);
                }
            } else {
                $this->log("No product_id found in URL structure for $url", self::COLOR_YELLOW);
            }
        }

        // ادامه کد برای product_page و fallbackها...
        if ($this->config['product_id_source'] === 'product_page') {
            $selector = $this->config['selectors']['product_page']['product_id']['selector'] ?? '';
            if (!empty($selector)) {
                $value = $this->extractData($crawler, $this->config['selectors']['product_page']['product_id']);
                if (!empty($value)) {
                    $this->log("Extracted product_id from product_page selector: $value for $url", self::COLOR_GREEN);
                    return $value;
                } else {
                    $this->log("No product_id found with selector: $selector for $url", self::COLOR_YELLOW);
                }
            }

            // تلاش برای استخراج از اسکریپت‌ها
            $patterns = $this->config['product_id_fallback_script_patterns'] ?? [];
            if (!empty($patterns)) {
                $scripts = $crawler->filter('script')->each(function (Crawler $node) {
                    return $node->text();
                });
                foreach ($scripts as $script) {
                    foreach ($patterns as $pattern) {
                        if (preg_match("/$pattern/", $script, $matches)) {
                            $productId = $matches[1] ?? '';
                            $this->log("Extracted product_id from script: $productId for $url", self::COLOR_GREEN);
                            return $productId;
                        }
                    }
                }
                $this->log("No product_id found in scripts with patterns for $url", self::COLOR_YELLOW);
            }
        }

        $this->log("Failed to extract product_id, returning empty string for $url", self::COLOR_RED);
        return '';
    }

    private function generateAsciiTable(array $headers, array $rows): string
    {
        // محاسبه عرض هر ستون با در نظر گرفتن کاراکترهای یونیکد
        $widths = [];
        foreach ($headers as $header) {
            $widths[] = max(mb_strwidth($header, 'UTF-8'), 10); // حداقل عرض 10
        }
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $cellWidth = mb_strwidth((string)$cell, 'UTF-8');
                $widths[$i] = max($widths[$i], $cellWidth);
            }
        }

        // تنظیم عرض ستون Title برای عناوین طولانی
        $widths[1] = max($widths[1], 40); // عرض حداقل 40 برای Title

        // ساخت خط جداکننده
        $separator = '+';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }
        $separator .= "\n";

        // ساخت هدر
        $table = $separator;
        $table .= '|';
        foreach ($headers as $i => $header) {
            $table .= ' ' . str_pad($header, $widths[$i], ' ', STR_PAD_BOTH) . ' |';
        }
        $table .= "\n" . $separator;

        // ساخت ردیف‌ها
        foreach ($rows as $row) {
            $table .= '|';
            foreach ($row as $i => $cell) {
                // برای کاراکترهای فارسی، از mb_str_pad استفاده می‌کنیم
                $paddedCell = $this->mb_str_pad((string)$cell, $widths[$i], ' ', STR_PAD_BOTH);
                $table .= ' ' . $paddedCell . ' |';
            }
            $table .= "\n";
        }
        $table .= $separator;

        return $table;
    }

    private function mb_str_pad(string $input, int $pad_length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT): string
    {
        $input_length = mb_strwidth($input, 'UTF-8');
        if ($pad_length <= $input_length) {
            return $input;
        }

        $padding = str_repeat($pad_string, $pad_length - $input_length);
        switch ($pad_type) {
            case STR_PAD_LEFT:
                return $padding . $input;
            case STR_PAD_RIGHT:
                return $input . $padding;
            case STR_PAD_BOTH:
                $left_padding = str_repeat($pad_string, floor(($pad_length - $input_length) / 2));
                $right_padding = str_repeat($pad_string, ceil(($pad_length - $input_length) / 2));
                return $left_padding . $input . $right_padding;
            default:
                return $input;
        }
    }

    private function logProduct(array $product, int $totalProducts = 0): void
    {
        $availability = (int)($product['availability'] ?? 0) ? 'موجود' : 'ناموجود';
        $imageStatus = empty($product['image']) ? 'ناموجود' : 'موجود';
        $guaranteeStatus = empty($product['guarantee']) ? 'ندارد' : $product['guarantee'];
        $discount = (int)($product['off'] ?? 0) > 0 ? $product['off'] . '%' : '0%';
        $productId = $product['product_id'] ?? 'N/A';
        $price = $product['price'] ?? 'N/A';
        $title = $product['title'] ?? 'N/A';
        $category = $product['category'] ?? 'N/A';

        // جلوگیری از ترانکیشن عنوان
        // $title = mb_substr($title, 0, 25, 'UTF-8'); // این خط حذف شد تا عنوان کامل نمایش داده شود

        // دیباگ برای بررسی فراخوانی
        $this->log("DEBUG: logProduct called for $title with product_id $productId", self::COLOR_YELLOW);

        // آماده‌سازی داده‌ها برای جدول
        $headers = ['Product ID', 'Title', 'Price', 'Category', 'Availability', 'Discount', 'Image URL', 'Guarantee'];
        $rows = [[
            $productId,
            $title, // بدون ترانکیشن
            $price,
            $category,
            $availability,
            $discount,
            $imageStatus,
            $guaranteeStatus
        ]];

        // تولید جدول ASCII
        $table = $this->generateAsciiTable($headers, $rows);

        // لاگ جدول
        $this->log($table, null);
    }

    private function log(string $message, ?string $color = null): void
    {
        $colorGreen = "\033[1;32m";
        $colorPink = "\033[1;95m";
        $colorPurple = "\033[1;95m";
        $colorYellow = "\033[1;33m";
        $colorReset = "\033[0m";

        $formattedMessage = $color ? $color . $message . $colorReset : $message;

        // ذخیره لاگ در فایل
        $logFile = storage_path('logs/scraper_' . date('Ymd_His') . '.log');
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);

        // حذف کدهای رنگی برای بررسی شرط‌ها
        $cleanMessage = preg_replace("/\033\[[0-9;]*m/", "", $message);

        // نمایش لاگ‌های مهم
        if (
            str_starts_with($cleanMessage, 'page') && str_contains($cleanMessage, 'link find') ||
            str_starts_with($cleanMessage, '+') && str_contains($cleanMessage, '|') || // شرط برای جدول ASCII
            str_starts_with($cleanMessage, 'Fetching page') ||
            str_starts_with($cleanMessage, 'Completed processing page') ||
            str_starts_with($cleanMessage, 'Total links collected') ||
            str_starts_with($cleanMessage, 'DEBUG:') ||
            stripos($cleanMessage, 'Extracted product_id') !== false ||
            stripos($cleanMessage, 'No product_id found') !== false ||
            stripos($cleanMessage, 'Failed to fetch') !== false ||
            stripos($cleanMessage, 'Invalid link skipped') !== false ||
            stripos($cleanMessage, 'Unwanted domain skipped') !== false
        ) {
            if (str_starts_with($cleanMessage, 'Fetching page')) {
                $formattedMessage = $colorPink . $message . $colorReset;
            } elseif (
                stripos($cleanMessage, 'Extracted product_id') !== false ||
                stripos($cleanMessage, 'No product_id found') !== false
            ) {
                $formattedMessage = $colorPurple . $message . $colorReset;
            } elseif (str_starts_with($cleanMessage, 'page') && str_contains($cleanMessage, 'link find')) {
                $formattedMessage = $colorGreen . $message . $colorReset;
            } elseif (str_starts_with($cleanMessage, '+') && str_contains($cleanMessage, '|')) {
                $formattedMessage = $colorYellow . $message . $colorReset; // رنگ زرد برای جدول
            }

            if ($this->outputCallback) {
                call_user_func($this->outputCallback, $formattedMessage);
            } else {
                echo $formattedMessage . PHP_EOL;
            }
        }
    }

    private function validateConfig(): void
    {
        $requiredFields = [
            'base_urls' => 'Base URLs are required.',
            'products_urls' => 'Products URLs are required.',
            'method' => 'Scraping method is required (1, 2, or 3).',
            'selectors' => 'Selectors configuration is required.',
        ];

        foreach ($requiredFields as $field => $message) {
            if (empty($this->config[$field])) {
                throw new \Exception("Validation Error: $message");
            }
        }

        if (!is_array($this->config['base_urls']) || count($this->config['base_urls']) < 1) {
            throw new \Exception("Validation Error: At least one base_url is required.");
        }
        if (!is_array($this->config['products_urls']) || count($this->config['products_urls']) < 1) {
            throw new \Exception("Validation Error: At least one products_url is required.");
        }

        if ($this->config['method'] === 2) {
            if (!isset($this->config['method_settings']['method_2']['navigation']['pagination']['method'])) {
                throw new \Exception("Validation Error: 'method' is required in method_2.navigation.pagination.");
            }
            $paginationMethod = $this->config['method_settings']['method_2']['navigation']['pagination']['method'];
            if (!in_array($paginationMethod, ['url', 'next_button'])) {
                throw new \Exception("Validation Error: 'method' in method_2.navigation.pagination must be 'url' or 'next_button'.");
            }

            if ($paginationMethod === 'next_button') {
                if (empty($this->config['method_settings']['method_2']['navigation']['pagination']['next_button']['selector'])) {
                    throw new \Exception("Validation Error: 'next_button.selector' is required when pagination method is 'next_button'.");
                }
            } elseif ($paginationMethod === 'url') {
                $urlConfig = $this->config['method_settings']['method_2']['navigation']['pagination']['url'] ?? [];
                if ($urlConfig['use_sample_url'] && empty($urlConfig['sample_url'])) {
                    throw new \Exception("Validation Error: 'sample_url' is required when 'use_sample_url' is true in method_2.navigation.pagination.url.");
                }
            }
        }

        if (isset($this->config['processing_method']) && $this->config['processing_method'] === 3) {
            if (!$this->config['method_settings']['method_3']['enabled']) {
                throw new \Exception("Validation Error: Method 3 must be enabled when processing_method is set to 3.");
            }
            if (!$this->config['method_settings']['method_3']['navigation']['use_webdriver']) {
                throw new \Exception("Validation Error: Method 3 requires a WebDriver (use_webdriver must be true) when processing_method is set to 3.");
            }
        }

        if (!isset($this->config['selectors']['main_page']) || !isset($this->config['selectors']['product_page'])) {
            throw new \Exception("Validation Error: Both 'main_page' and 'product_page' selectors are required.");
        }


        if (!in_array($this->config['method'], [1, 2, 3])) {
            throw new \Exception('Validation Error: Invalid method value. Must be 1, 2, or 3.');
        }

        // اعتبارسنجی processing_method
        if (isset($this->config['processing_method']) && !in_array($this->config['processing_method'], [1, 2, 3])) {
            throw new \Exception('Validation Error: Invalid processing_method value. Must be 1, 2, or 3.');
        }

        // اعتبارسنجی category_method و category_word_count
        if (isset($this->config['category_method']) && $this->config['category_method'] === 'title') {
            if (!isset($this->config['category_word_count']) || !is_int($this->config['category_word_count']) || $this->config['category_word_count'] < 1) {
                throw new \Exception("Validation Error: 'category_word_count' must be a positive integer when 'category_method' is 'title'.");
            }
        }

        // اعتبارسنجی تنظیمات صفحه‌بندی برای method_1
        if ($this->config['method'] === 1) {
            if (!isset($this->config['method_settings']['method_1']['pagination']['ignore_redirects'])) {
                $this->log("Warning: 'ignore_redirects' not set in method_1.pagination. Defaulting to false.", self::COLOR_YELLOW);
                $this->config['method_settings']['method_1']['pagination']['ignore_redirects'] = false;
            }

            if ($this->config['method_settings']['method_1']['pagination']['use_sample_url'] && empty($this->config['method_settings']['method_1']['pagination']['sample_url'])) {
                throw new \Exception("Validation Error: 'sample_url' is required when 'use_sample_url' is true.");
            }
        }

        if (!empty($this->config['output_dir'])) {
            $this->log("Warning: output_dir is set but file saving is disabled.", self::COLOR_YELLOW);
            unset($this->config['output_dir']);
        }

        if (isset($this->config['selectors']['main_page']['product_links']['product_id'])) {
            $productIdAttr = $this->config['selectors']['main_page']['product_links']['product_id'];
            if (empty($productIdAttr)) {
                throw new \Exception("Validation Error: 'product_id' attribute in product_links cannot be empty.");
            }
        }

        $this->log('Configuration validated successfully.', self::COLOR_GREEN);
    }
}
