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
use Symfony\Component\DomCrawler\Crawler;

class StartController
{
    public array $config;
    private Client $httpClient;
    private $chromedriverPid = null;
    private array $failedUrlsDueToInternalError = [];
    private $outputCallback = null;
    private int $processedCount = 0;
    protected array $failedLinks = [];
    private array $sharedProductIds = [];
    private const COLOR_GREEN = "\033[1;92m";
    private const COLOR_RED = "\033[1;91m";
    private const COLOR_PURPLE = "\033[1;95m";
    private const COLOR_YELLOW = "\033[1;93m";
    private const COLOR_BLUE = "\033[1;94m";

    private const COLOR_GRAY = "\033[1;90m";
    private const COLOR_CYAN = "\033[1;36m";
    private const COLOR_RESET = "\033[0m";
    private const COLOR_BOLD = "\033[1m";

    public function __construct(array $config)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->config = $config;

        // ✅ بررسی حالت تست محصول قبل از اعتبارسنجی معمولی
        $isProductTestMode = $this->config['product_test'] ?? false;

        if ($isProductTestMode) {
            $this->log("🧪 Product Test Mode - Skipping standard config validation", self::COLOR_PURPLE);
            // فقط اعتبارسنجی مخصوص حالت تست
            $this->validateProductTestConfig();
        } else {
            // اعتبارسنجی و تصحیح کانفیگ معمولی
            $this->validateAndFixConfig();
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
            // در حالت تست، از اولین URL محصول برای تنظیم Referer استفاده می‌کنیم
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

            // اسکریپت Playwright
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
        bypassCSP: true // برای دور زدن محدودیت‌های CSP
    });

    console.log('Creating new page...');
    page = await context.newPage();

    // لاگ‌گیری در صورت بسته شدن غیرمنتظره
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

            // اصلاح قسمت استخراج product_id
            console.log('Extracting product_id...');

            // اول چک کنیم که product_id_method چیه
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
        // تلاش برای بازسازی مرورگر
        await closeBrowser();
        await initializeBrowser();
    }
};

    try {
        await initializeBrowser();

        // Step 1: جمع‌آوری لینک‌های محصولات
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

        // Step 2: پردازش هر لینک محصول در همان تب
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
                    'PRODUCT_ID_METHOD',
                    'PRODUCT_ID_SOURCE',
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
                    $productIdMethod,
                    $productIdSource,
                    $baseurl,
                    $positiveKeywords,
                    $negativeKeywords,
                    $paginationMethod,
                    $paginationConfigJson,
                    $scrool
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

            // پردازش محصولات و ذخیره در دیتابیس
            $links = [];
            $successfulProducts = 0;
            $failedProducts = 0;

            foreach ($result['products'] as $productData) {
                $this->log("Processing product: {$productData['url']}", self::COLOR_YELLOW);

                // اگر خطا داریم، فقط failed link اضافه کن و ادامه بده
                if (!empty($productData['error'])) {
                    $this->log("Error processing product {$productData['url']}: {$productData['error']}", self::COLOR_RED);
                    $this->saveFailedLink($productData['url'], $productData['error']);
                    $failedProducts++;
                    continue;
                }

                // بررسی اینکه آیا داده‌های اساسی موجود هستند
                if (empty($productData['title']) && empty($productData['price'])) {
                    $this->log("Product has no title or price: {$productData['url']}", self::COLOR_RED);
                    $this->saveFailedLink($productData['url'], "Missing essential data (title and price)");
                    $failedProducts++;
                    continue;
                }

                // پردازش داده‌های محصول
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

                // validation و ذخیره محصول
                if ($this->validateProductData($processedData)) {
                    try {
                        $this->saveProductToDatabase($processedData);
                        $this->logProduct($processedData);
                        $links[] = [
                            'url' => $processedData['page_url'],
                            'image' => $processedData['image'],
                            'product_id' => $processedData['product_id']
                        ];

                        $successfulProducts++;
                        $this->log("✅ Product saved successfully: {$processedData['page_url']}", self::COLOR_GREEN);

                        // 🔥 مهم: اگر محصول موفقیت‌آمیز ذخیره شد، از failed_links حذفش کن
                        $this->removeFromFailedLinks($processedData['page_url']);

                    } catch (\Exception $e) {
                        $this->log("Failed to save product {$processedData['page_url']}: {$e->getMessage()}", self::COLOR_RED);
                        $this->saveFailedLink($processedData['page_url'], "Database error: {$e->getMessage()}");
                        $failedProducts++;
                    }
                } else {
                    $this->log("Invalid product data for {$processedData['page_url']}", self::COLOR_RED);
                    $this->saveFailedLink($processedData['page_url'], "Invalid product data");
                    $failedProducts++;
                }
            }

            $this->log("Products processing summary - Successful: $successfulProducts, Failed: $failedProducts", self::COLOR_PURPLE);
            $allLinks = array_merge($allLinks, $links);
            $totalPagesProcessed += $result['pages_processed'] ?? 0;

            unlink($tempFile);
        }

        // اطمینان از اتمام پردازش
        $this->log("All products_urls processed. Total links: " . count($allLinks) . ", Total pages: $totalPagesProcessed", self::COLOR_GREEN);

        return [
            'links' => array_unique($allLinks, SORT_REGULAR),
            'pages_processed' => $totalPagesProcessed
        ];
    }

    private function removeFromFailedLinks(string $url): void
    {
        try {
            $deletedCount = FailedLink::where('url', $url)->delete();

            if ($deletedCount > 0) {
                $this->log("🧹 لینک از failed_links حذف شد: $url", self::COLOR_GREEN);
            }

        } catch (\Exception $e) {
            $this->log("💥 خطا در حذف failed_link $url: {$e->getMessage()}", self::COLOR_RED);
        }
    }

    private function scrapeWithPlaywright(int $method, string $productUrl): array
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

        // اسکریپت Playwright
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

        console.log(`Navigating to URL: PRODUCTS_URL`);
        await page.goto("PRODUCTS_URL", { waitUntil: 'domcontentloaded', timeout: 120000 });

        while (hasMorePages && currentPage <= maxPages) {
            let pageUrl = paginationMethod === 'url' ? buildPaginationUrl("PRODUCTS_URL", currentPage) : "PRODUCTS_URL";
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
                            const parent = node.closest(container);
                            const imageElement = parent ? parent.querySelector(imageSel) : null;
                            image = imageElement ? imageElement.getAttribute(imageAttr) : '';
                        }
                        let productId = '';
                        if (productIdSource === 'product_links' && productIdFromLink) {
                            productId = node.getAttribute(productIdFromLink) || 'unknown';
                        } else if (productIdSource === 'main_page' && productIdSel) {
                            const parent = node.closest(container);
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
                        await page.waitForTimeout(10000);
                    } else {
                        console.log('Next button not found. Stopping pagination.');
                        hasMorePages = false;
                    }
                } catch (error) {
                    console.log(`Failed to click next button: ${error.message}`);
                    hasMorePages = false;
                }
            } else if (paginationMethod === 'url') {
                await page.waitForTimeout(7000);
            }
        }

        links = [...new Set(links.map(link => JSON.stringify(link)))].map(str => JSON.parse(str));
        await browser.close();
        console.log(JSON.stringify({ links, pagesProcessed, consoleLogs }));
    } catch (error) {
        console.error('Error in Playwright script:', error.message);
        console.log(JSON.stringify({ links: [], pagesProcessed, consoleLogs, error: error.message }));
    }
})();
JAVASCRIPT;

        // جایگذاری مقادیر
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
                $paginationMethod,
                $nextButtonSelector
            ],
            $playwrightScript
        );

        // ایجاد فایل موقت
        $tempFile = tempnam(sys_get_temp_dir(), 'playwright_') . '.cjs';
        file_put_contents($tempFile, $playwrightScript);
        chmod($tempFile, 0755);
        chown($tempFile, 'www-data');
        $this->log("Temporary script file created at: $tempFile", self::COLOR_GREEN);

        // تنظیم مسیر Node.js
        $nodePath = '/usr/bin/node'; // مسیر درست Node.js
        $nodeModulesPath = '/var/www/html/products-shops/node_modules';
        $browserPath = '/var/www/.cache/ms-playwright';

        // اجرای اسکریپت
        $command = "PLAYWRIGHT_BROWSERS_PATH={$browserPath} NODE_PATH={$nodeModulesPath} {$nodePath} {$tempFile} 2>&1";
        $this->log("Executing Playwright script: {$command}", self::COLOR_GREEN);

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
                if (!preg_match('/^\s*\{.*"links":.*\}/', $line)) {
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
        proc_close($process);

        unlink($tempFile);
        $this->log("Playwright script execution completed.", self::COLOR_GREEN);

        // بررسی خطاها
        if (!empty($errorOutput)) {
            $this->log("Playwright errors detected: {$errorOutput}", self::COLOR_RED);
            $this->saveFailedLink($productUrl, $errorOutput);
            return ['links' => [], 'pages_processed' => 0];
        }

        // تجزیه خروجی
        preg_match('/\{.*\}/s', $output, $matches);
        if (!isset($matches[0])) {
            $this->log("Failed to parse Playwright output for {$productUrl}. Raw output: {$output}", self::COLOR_RED);
            $this->saveFailedLink($productUrl, 'Failed to parse output');
            return ['links' => [], 'pages_processed' => 0];
        }

        $result = json_decode($matches[0], true);
        if (!$result || !isset($result['links'])) {
            $this->log("Invalid Playwright output format for {$productUrl}.", self::COLOR_RED);
            $this->saveFailedLink($productUrl, 'Invalid output format');
            return ['links' => [], 'pages_processed' => 0];
        }

        // لاگ کردن console logs
        if (isset($result['console_logs']) && is_array($result['console_logs'])) {
            foreach ($result['console_logs'] as $log) {
                $this->log("Playwright console log: {$log}", self::COLOR_YELLOW);
            }
        }

        // پردازش لینک‌ها
        $links = array_map(function ($link) use ($productUrl) {
            $url = $this->makeAbsoluteUrl($link['url'], $productUrl);
            $productId = $link['product_id'] ?? '';

            if ($productId === '' && ($this->config['product_id_method'] ?? 'selector') === 'url') {
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

        // ذخیره لینک‌ها در دیتابیس
        $this->saveProductLinksToDatabase($links);

        return [
            'links' => array_unique($links, SORT_REGULAR),
            'pages_processed' => $result['pages_processed'] ?? 0
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

    private function extractCategoryFromTitle(string $title, $wordCount = 1): string
    {
        $this->log("Extracting category from title: '$title' with word count: " . (is_array($wordCount) ? json_encode($wordCount) : $wordCount), self::COLOR_YELLOW);

        // پاک کردن کاراکترهای ناخواسته از عنوان
        $cleanTitle = $this->cleanCategoryText($title);

        // تقسیم عنوان به کلمات
        $words = preg_split('/\s+/', trim($cleanTitle), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            $this->log("No words found in title", self::COLOR_RED);
            return '';
        }

        $categories = [];

        // اگر wordCount یک آرایه باشد، برای هر عنصر یک دسته‌بندی بساز
        if (is_array($wordCount)) {
            foreach ($wordCount as $count) {
                if (is_numeric($count) && $count > 0 && $count <= count($words)) {
                    $categoryWords = array_slice($words, 0, $count);
                    $category = implode(' ', $categoryWords);
                    if (!empty($category)) {
                        $categories[] = $category;
                    }
                } else {
                    $this->log("Invalid word count: $count (should be positive integer <= " . count($words) . ")", self::COLOR_YELLOW);
                }
            }
        } else {
            // روش قدیمی: فقط یک دسته‌بندی
            if (is_numeric($wordCount) && $wordCount > 0) {
                $categoryWords = array_slice($words, 0, min($wordCount, count($words)));
                $category = implode(' ', $categoryWords);
                if (!empty($category)) {
                    $categories[] = $category;
                }
            } else {
                $this->log("Invalid word count: $wordCount (should be positive integer)", self::COLOR_YELLOW);
            }
        }

        // حذف دسته‌بندی‌های تکراری و خالی
        $categories = array_filter(array_unique($categories), function ($cat) {
            return !empty(trim($cat));
        });

        // حذف محدودیت تعداد دسته‌بندی - همه دسته‌بندی‌های پیدا شده حفظ می‌شوند

        // ترکیب دسته‌بندی‌ها با کاما
        $finalCategory = implode(', ', $categories);

        $this->log("Extracted category: '$finalCategory' (Total: " . count($categories) . ")", self::COLOR_GREEN);
        return $finalCategory;
    }

    private function cleanCategoryText(string $text): string
    {
        // حذف کاراکترهای HTML
        $text = strip_tags($text);

        // حذف کاراکترهای ویژه (اختیاری - بر اساس نیاز سایت)
        $text = preg_replace('/[^\p{L}\p{N}\s\-_,]/u', '', $text);

        // تبدیل چند فاصله به یک فاصله
        $text = preg_replace('/\s+/', ' ', $text);

        // حذف فاصله‌های ابتدا و انتها
        return trim($text);
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
        $databaseMode = $this->config['database'] ?? 'clear';
        $this->log("Database mode: $databaseMode", self::COLOR_GREEN);

        // اتصال به دیتابیس پیش‌فرض برای مدیریت دیتابیس‌ها
        $defaultConnection = config('database.connections.mysql.database');

        // بررسی وجود دیتابیس
        $exists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
        $databaseExists = !empty($exists);

        if ($databaseMode === 'clear') {
            // حالت clear: حذف و ایجاد مجدد دیتابیس
            if ($databaseExists) {
                $this->log("Database $dbName exists, dropping it...", self::COLOR_YELLOW);
                DB::statement("DROP DATABASE `$dbName`");
            }

            $this->log("Creating database $dbName...", self::COLOR_GREEN);
            DB::statement("CREATE DATABASE `$dbName`");
        } elseif ($databaseMode === 'continue') {
            // حالت continue: استفاده از دیتابیس موجود یا ایجاد اگه وجود نداره
            if (!$databaseExists) {
                $this->log("Database $dbName does not exist, creating it...", self::COLOR_YELLOW);
                DB::statement("CREATE DATABASE `$dbName`");
            } else {
                $this->log("Using existing database $dbName", self::COLOR_GREEN);
            }
        } else {
            throw new \Exception("Invalid database mode specified: $databaseMode. Use 'clear' or 'continue'.");
        }

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

        // اجرای مهاجرت‌ها فقط اگه دیتابیس تازه ایجاد شده یا در حالت clear هستیم
        if ($databaseMode === 'clear' || !$databaseExists) {
            $this->log("Running specific migrations for database $dbName...", self::COLOR_GREEN);

            // لیست فایل‌های مهاجرت مورد نیاز
            $migrationFiles = [
                database_path('migrations/2025_04_08_162133_create_products_table.php'),
                database_path('migrations/2025_04_13_073528_create_failed_links_table.php'),
                database_path('migrations/2025_05_19_162835_create_links_table.php'),
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
        } else {
            $this->log("Skipping migrations for database $dbName in continue mode", self::COLOR_GREEN);
        }
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
        $this->failedLinksCount = 0; // Changed from array to counter
        $processedUrls = [];

        // لاگ لینک‌های ورودی برای دیباگ
        $this->log("Input links: " . json_encode(array_slice($links, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "...", self::COLOR_YELLOW);

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
                        $productData = $this->extractProductData($url, (string)$response->getBody(), $image, $productId);

                        if ($productData && $this->validateProductData($productData)) {
                            if (is_array($product) && isset($product['off'])) {
                                $productData['off'] = $product['off'];
                            }

                            DB::beginTransaction();
                            try {
                                $this->saveProductToDatabase($productData);
                                $this->updateLinkProcessedStatus($url);
                                DB::commit();

                                $processedUrls[] = $url;
                                $this->logProduct($productData);
                                $this->log("Successfully processed: $url", self::COLOR_GREEN);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                                $this->failedLinksCount++;
                                $this->log("Failed to save product: $url - {$e->getMessage()}", self::COLOR_RED);
                            }
                        } else {
                            $this->saveFailedLink($url, "Invalid or missing product data");
                            $this->failedLinksCount++;
                            $this->log("Failed to extract valid data: $url", self::COLOR_RED);
                        }
                    } catch (\Exception $e) {
                        $this->saveFailedLink($url, "Processing error: " . $e->getMessage());
                        $this->failedLinksCount++;
                        $this->log("Processing error: $url - {$e->getMessage()}", self::COLOR_RED);
                    }
                },
                'rejected' => function ($reason, $index) use ($filteredProducts) {
                    $url = is_array($filteredProducts[$index]) ? $filteredProducts[$index]['url'] : $filteredProducts[$index];
                    $this->saveFailedLink($url, "Failed to fetch: " . $reason->getMessage());
                    $this->failedLinksCount++;
                    $this->log("Fetch failed: $url - {$reason->getMessage()}", self::COLOR_YELLOW);
                },
            ]);

            $promise = $pool->promise();
            $promise->wait();
        } // Method 2: Sequential processing with Playwright (for JavaScript-rendered pages)
        elseif ($method === 2) {
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
                        $productData = $this->processProductPageWithPlaywright($url);

                        if (isset($productData['error'])) {
                            $this->saveFailedLink($url, $productData['error']);
                            $this->failedLinksCount++;
                            $this->log("Failed: $url - {$productData['error']}", self::COLOR_RED);
                            continue;
                        }

                        $productData['page_url'] = $url;
                        $productData['image'] = $image ?? ($productData['image'] ?? '');
                        $productData['product_id'] = $productId !== '' ? $productId : ($productData['product_id'] ?? '');
                        $productData['availability'] = isset($productData['availability']) ? (int)$productData['availability'] : 0;
                        $productData['off'] = isset($productData['off']) ? (int)$productData['off'] : 0;
                        $productData['category'] = $productData['category'] ?? '';
                        $productData['guarantee'] = $productData['guarantee'] ?? '';

                        if ($this->validateProductData($productData)) {
                            DB::beginTransaction();
                            try {
                                $this->saveProductToDatabase($productData);
                                $this->updateLinkProcessedStatus($url);
                                DB::commit();

                                $processedUrls[] = $url;
                                $this->logProduct($productData);
                                $this->log("Successfully processed: $url", self::COLOR_GREEN);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                                $this->failedLinksCount++;
                                $this->log("Failed to save product: $url - {$e->getMessage()}", self::COLOR_RED);
                            }
                        } else {
                            $this->saveFailedLink($url, "Invalid product data: " . json_encode($productData, JSON_UNESCAPED_UNICODE));
                            $this->failedLinksCount++;
                            $this->log("Invalid product data: $url", self::COLOR_RED);
                        }
                    } catch (\Exception $e) {
                        $this->saveFailedLink($url, "Processing error: " . $e->getMessage());
                        $this->failedLinksCount++;
                        $this->log("Processing error: $url - {$e->getMessage()}", self::COLOR_RED);
                    }

                    // Add delay between requests
                    usleep(rand($this->config['request_delay_min'] ?? 1000, $this->config['request_delay_max'] ?? 3000) * 1000);
                }
            }
        } // Method 3: Sequential processing with custom extraction
        elseif ($method === 3) {
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
                        $productData = $this->extractProductData($url, null, $image, $productId);

                        if ($productData === null) {
                            $this->saveFailedLink($url, "Failed to extract product data");
                            $this->failedLinksCount++;
                            $this->log("Failed to extract data: $url", self::COLOR_RED);
                            continue;
                        }

                        $productData['page_url'] = $url;
                        $productData['image'] = $image ?? ($productData['image'] ?? '');
                        $productData['product_id'] = $productId !== '' ? $productId : ($productData['product_id'] ?? '');
                        $productData['availability'] = isset($productData['availability']) ? (int)$productData['availability'] : 0;
                        $productData['off'] = isset($productData['off']) ? (int)$productData['off'] : 0;
                        $productData['category'] = $productData['category'] ?? '';
                        $productData['guarantee'] = $productData['guarantee'] ?? '';

                        if ($this->validateProductData($productData)) {
                            DB::beginTransaction();
                            try {
                                $this->saveProductToDatabase($productData);
                                $this->updateLinkProcessedStatus($url);
                                DB::commit();

                                $processedUrls[] = $url;
                                $this->logProduct($productData);
                                $this->log("Successfully processed: $url", self::COLOR_GREEN);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $this->saveFailedLink($url, "Database error: " . $e->getMessage());
                                $this->failedLinksCount++;
                                $this->log("Failed to save product: $url - {$e->getMessage()}", self::COLOR_RED);
                            }
                        } else {
                            $this->saveFailedLink($url, "Invalid product data: " . json_encode($productData, JSON_UNESCAPED_UNICODE));
                            $this->failedLinksCount++;
                            $this->log("Invalid product data: $url", self::COLOR_RED);
                        }
                    } catch (\Exception $e) {
                        $this->saveFailedLink($url, "Processing error: " . $e->getMessage());
                        $this->failedLinksCount++;
                        $this->log("Processing error: $url - {$e->getMessage()}", self::COLOR_RED);
                    }

                    // Add delay between requests
                    usleep(rand($this->config['request_delay_min'] ?? 1000, $this->config['request_delay_max'] ?? 3000) * 1000);
                }
            }
        } else {
            throw new \Exception("Invalid processing method: $method. Use 1, 2, or 3.");
        }

        // اطلاعات لینک‌های شکست‌خورده از دیتابیس
        $failedLinksCount = FailedLink::count();

        $this->log("Batch processing completed. Processed: {$this->processedCount}, Failed: {$failedLinksCount}", self::COLOR_GREEN);

        return [
            'processed' => $this->processedCount,
            'failed' => $failedLinksCount,
            'pages_processed' => count($filteredProducts)
        ];
    }

    private function saveFailedLink(string $url, string $errorMessage): void
    {
        try {
            $existingFailedLink = FailedLink::where('url', $url)->first();

            if ($existingFailedLink) {
                // آپدیت لینک ناموفق موجود
                $oldAttempts = $existingFailedLink->attempts;
                $existingFailedLink->update([
                    'attempts' => $oldAttempts + 1,
                    'error_message' => $errorMessage,
                    'updated_at' => now()
                ]);

                $this->log("🔄 لینک ناموفق آپدیت شد (تلاش #{$existingFailedLink->attempts}): $url", self::COLOR_YELLOW);
                $this->log("  └─ خطا: $errorMessage", self::COLOR_RED);

            } else {
                // ایجاد لینک ناموفق جدید
                FailedLink::create([
                    'url' => $url,
                    'attempts' => 1,
                    'error_message' => $errorMessage,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $this->log("❌ لینک جدید به failed_links اضافه شد: $url", self::COLOR_RED);
                $this->log("  └─ خطا: $errorMessage", self::COLOR_RED);
            }

        } catch (\Exception $e) {
            $this->log("💥 خطا در ذخیره failed_link $url: {$e->getMessage()}", self::COLOR_RED);
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

    public function scrapeMultiple(?int $start_id = null): array
    {
        $this->log("Inside scrapeMultiple method", self::COLOR_PURPLE);
        $this->log("Starting scraper with start_id: " . ($start_id ?? 'not set'), self::COLOR_GREEN);

        // ✅ بررسی حالت تست محصول
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

        // لاگ محتوای کانفیگ برای دیباگ
        $this->log("Config contents: " . json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), self::COLOR_YELLOW);

        // اعتبارسنجی کانفیگ
        $this->validateConfig();

        // تنظیم دیتابیس
        $this->setupDatabase();

        // اگر در حالت update هستیم، ابتدا reset انجام میدهیم
        // IMPORTANT: Reset باید بعد از setupDatabase انجام شود
        if ($isUpdateMode) {
            $this->resetProductsAndLinks();
        }

        // تنظیم اولیه
        $this->processedCount = 0;
        $this->failedLinksCount = 0;

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

            // در حالت update، ابتدا بررسی می‌کنیم آیا لینکی در دیتابیس وجود دارد یا نه
            if ($isUpdateMode) {
                $totalLinksInDb = Link::count();
                $this->log("Total links in database: $totalLinksInDb", self::COLOR_BLUE);

                if ($totalLinksInDb == 0) {
                    $this->log("No links found in database for update mode. Need to fetch from web first.", self::COLOR_YELLOW);

                    // اگر لینکی وجود ندارد، از web بگیریم
                    $this->log("Fetching product links from web for update mode...", self::COLOR_GREEN);
                    $result = $this->fetchProductLinks();
                    $links = $result['links'] ?? [];
                    $pagesProcessed = $result['pages_processed'] ?? 0;

                    $this->log("Got " . count($links) . " unique product links from web", self::COLOR_GREEN);

                    if (!empty($links)) {
                        $this->saveProductLinksToDatabase($links);
                        // حالا از دیتابیس بگیریم
                        $result = $this->getProductLinksFromDatabase($start_id);
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
                    // لینک‌ها در دیتابیس موجود است، از آنها استفاده کنیم
                    $result = $this->getProductLinksFromDatabase($start_id);
                    $links = $result['links'] ?? [];
                    $pagesProcessed = $result['pages_processed'] ?? 0;
                }
            } else {
                // حالت عادی continue
                $result = $this->getProductLinksFromDatabase($start_id);
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
            $result = $this->fetchProductLinks();
            $links = $result['links'] ?? [];
            $pagesProcessed = $result['pages_processed'] ?? 0;

            $this->log("Got " . count($links) . " unique product links from web", self::COLOR_GREEN);
            $this->log("Links structure: " . json_encode(array_slice($links, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "...", self::COLOR_YELLOW);

            if (!empty($links)) {
                $this->saveProductLinksToDatabase($links);
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
        $processedResult = $this->processPagesInBatches($uniqueLinks, $processingMethod);

        // Get failed links count from database
        $failedLinksCount = FailedLink::count();
        $this->failedLinksCount = $failedLinksCount;

        // تلاش مجدد برای لینک‌های شکست‌خورده
        if ($failedLinksCount > 0) {
            $this->log("Found $failedLinksCount failed links in database. Attempting to retry...", self::COLOR_PURPLE);

            // Track the number of processed before retrying
            $processedBefore = $this->processedCount;

            // Retry failed links
            $this->retryFailedLinks();

            // Calculate how many were successfully processed during retry
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

    private function fetchPageContent(string $url, bool $useDeep, bool $isProductPage = true): ?string
    {
        $this->log("🌐 FETCHING: $url", self::COLOR_PURPLE);

        $maxRetries = $this->config['max_retries'] ?? 3;
        $attempt = 1;

        while ($attempt <= $maxRetries) {
            $userAgent = $this->randomUserAgent();
            $this->log("🔄 Attempt $attempt/$maxRetries - UserAgent: " . substr($userAgent, 0, 50) . "...", self::COLOR_GREEN);

            try {
                // تست DNS resolution
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

                // چک کردن response headers
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

                // چک کردن محتوا برای anti-bot patterns
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

                // مشخص کردن نوع خطا
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
        $this->log("🔄 STARTING fetchProductLinks - Method: $method", self::COLOR_GREEN);

        // چک کردن کانفیگ اولیه
        $this->log("📄 Config check - products_urls count: " . count($this->config['products_urls'] ?? []), self::COLOR_PURPLE);
        $this->log("📄 Config check - base_urls: " . json_encode($this->config['base_urls'] ?? []), self::COLOR_PURPLE);

        if (!isset($this->config['selectors']['main_page']['product_links'])) {
            throw new \Exception("Main page product_links selector is required.");
        }

        // رفع مشکل Array to string conversion
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
                // تست اتصال اولیه
                $this->log("🔗 Testing connection to: $productUrl", self::COLOR_PURPLE);
                $testContent = $this->fetchPageContent($productUrl, false, false);

                if ($testContent === null) {
                    $this->log("❌ CRITICAL: Cannot fetch content from $productUrl", self::COLOR_RED);
                    continue;
                }

                $this->log("✅ Connection successful - Content length: " . strlen($testContent), self::COLOR_GREEN);

                // بررسی محتوا برای debugging
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

                // نمونه لینک‌ها برای debugging
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

    private function retryFailedLinks(): void
    {
        $maxAttempts = $this->config['max_retry_attempts'] ?? 3;
        $failedLinks = FailedLink::where('attempts', '<', $maxAttempts)->get();

        if ($failedLinks->isEmpty()) {
            $this->log("✅ هیچ لینک ناموفقی برای تلاش مجدد وجود ندارد", self::COLOR_GREEN);
            return;
        }

        $this->log("🔄 شروع تلاش مجدد برای " . $failedLinks->count() . " لینک ناموفق...", self::COLOR_PURPLE);
        $this->log("═══════════════════════════════════════════════════════════════", self::COLOR_PURPLE);

        $proxies = $this->config['proxies'] ?? [];
        if (empty($proxies)) {
            $this->log("⚠️  هشدار: پروکسی تعریف نشده - استفاده از اتصال مستقیم", self::COLOR_YELLOW);
            $proxies = [['ip' => '', 'port' => '', 'username' => '', 'password' => '']];
        }

        $successCount = 0;
        $stillFailedCount = 0;

        foreach ($failedLinks as $index => $link) {
            $url = $link->url;
            $attemptNumber = $link->attempts + 1;

            $this->log("🔍 تلاش مجدد [" . ($index + 1) . "/" . $failedLinks->count() . "] - تلاش #{$attemptNumber}: $url", self::COLOR_BLUE);

            try {
                $content = $this->fetchWithProxyAndRandomUA($url, $proxies, 30, $maxAttempts);

                if (!$content) {
                    throw new \Exception("عدم دریافت محتوا پس از چندین تلاش با پروکسی‌های مختلف");
                }

                $productData = $this->extractProductData($url, $content);

                if ($productData && $this->validateProductData($productData)) {
                    DB::beginTransaction();
                    try {
                        $this->saveProductToDatabase($productData);
                        $this->updateLinkProcessedStatus($url, true);

                        // حذف از failed_links
                        $link->delete();

                        DB::commit();

                        // لاگ موفقیت بازیابی
                        $extraInfo = [
                            'تلاش‌های قبلی' => $link->attempts,
                            'زمان بازیابی' => now()->format('H:i:s')
                        ];
                        $this->logProduct($productData, 'RETRY_SUCCESS', $extraInfo);

                        $this->processedCount++;
                        $successCount++;

                        $this->log("🎉 موفقیت در بازیابی لینک: $url", self::COLOR_GREEN);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->handleRetryFailure($link, "خطای دیتابیس: " . $e->getMessage());
                        $stillFailedCount++;
                    }
                } else {
                    $this->handleRetryFailure($link, "داده محصول نامعتبر");
                    $stillFailedCount++;
                }
            } catch (\Exception $e) {
                $this->handleRetryFailure($link, "خطا در تلاش مجدد: " . $e->getMessage());
                $stillFailedCount++;
            }

            // فاصله بین لینک‌ها
            $this->log("───────────────────────────────────────────────────────────────", self::COLOR_GRAY);
        }

        // پاکسازی لینک‌های منقضی
        $this->cleanupExhaustedLinks($maxAttempts);

        // گزارش نهایی
        $this->log("═══════════════════════════════════════════════════════════════", self::COLOR_PURPLE);
        $this->log("📊 گزارش تلاش مجدد تکمیل شد:", self::COLOR_PURPLE);
        $this->log("  ✅ موفق: $successCount", self::COLOR_GREEN);
        $this->log("  ❌ ناموفق: $stillFailedCount", self::COLOR_RED);
        $this->log("═══════════════════════════════════════════════════════════════", self::COLOR_PURPLE);
    }

    private function fetchWithProxyAndRandomUA(string $url, array $proxies, int $timeout = 30, int $maxRetries = 3): ?string
    {
        // بررسی وجود پروکسی
        if (empty($proxies)) {
            $this->log("No proxies provided for fetchWithProxyAndRandomUA", self::COLOR_YELLOW);
            return null;
        }

        // ثبت لاگ
        $this->log("Attempting to fetch failed URL with proxy and random UA: $url", self::COLOR_BLUE);

        // تعداد تلاش‌ها
        $attempt = 0;
        $maxAttempts = count($proxies) * 2; // هر پروکسی حداکثر دو بار تلاش می‌شود
        $maxAttempts = min($maxAttempts, $maxRetries * 2); // با توجه به محدودیت maxRetries

        // لیست خطاها برای گزارش
        $errors = [];

        while ($attempt < $maxAttempts) {
            // انتخاب یک پروکسی رندوم
            $proxyIndex = array_rand($proxies);
            $proxy = $proxies[$proxyIndex];

            // انتخاب یک User-Agent رندوم
            $userAgent = $this->randomUserAgent();

            // تأخیر متغیر بین درخواست‌ها (بین 1 تا 3 ثانیه)
            $delay = rand(1000, 3000);
            usleep($delay * 1000); // تبدیل به میکروثانیه

            // ایجاد یک session cURL جدید
            $ch = curl_init();

            // تنظیم URL
            curl_setopt($ch, CURLOPT_URL, $url);

            // تنظیم User-Agent
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

            // تنظیم پروکسی
            curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);

// تنظیم نوع پروکسی اگر مشخص شده باشد
            if (!empty($proxy['type'])) {
                $proxyType = CURLPROXY_HTTP; // مقدار پیش‌فرض

                if (strtolower($proxy['type']) === 'socks4') {
                    $proxyType = CURLPROXY_SOCKS4;
                } elseif (strtolower($proxy['type']) === 'socks5') {
                    $proxyType = CURLPROXY_SOCKS5;
                }

                curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType);
            }

            // اگر پروکسی نیاز به احراز هویت دارد
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
            }

            // تنظیمات امنیتی
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // غیرفعال کردن بررسی SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // غیرفعال کردن بررسی هاست SSL

            // تنظیمات دیگر
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

            // تنظیم هدرهای اضافی برای شبیه‌سازی بهتر مرورگر
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Referer: ' . parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/'
            ]);

            // فعال کردن اطلاعات خطا
            curl_setopt($ch, CURLOPT_FAILONERROR, true);

            // اجرای درخواست
            $content = curl_exec($ch);

            // بررسی خطا
            if ($content === false) {
                $errorCode = curl_errno($ch);
                $errorMessage = curl_error($ch);
                $errors[] = "cURL error ($errorCode): $errorMessage with proxy " . $proxy['ip'] . ":" . $proxy['port'];

                $this->log("Attempt " . ($attempt + 1) . " failed: cURL error ($errorCode): $errorMessage", self::COLOR_YELLOW);
            } else {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // اگر کد HTTP مناسب است (2xx یا 3xx)
                if ($httpCode >= 200 && $httpCode < 400) {
                    curl_close($ch);
                    $this->log("Successfully fetched content with proxy " . $proxy['ip'] . ":" . $proxy['port'] . " and UA: " . substr($userAgent, 0, 30) . "...", self::COLOR_GREEN);
                    return $content;
                } else {
                    $errors[] = "HTTP error: $httpCode with proxy " . $proxy['ip'] . ":" . $proxy['port'];
                    $this->log("Attempt " . ($attempt + 1) . " failed: HTTP error $httpCode", self::COLOR_YELLOW);
                }
            }

            // بستن session cURL
            curl_close($ch);

            // افزایش شمارنده تلاش
            $attempt++;

            // محاسبه تأخیر با استفاده از استراتژی exponential backoff
            $backoffDelay = $this->exponentialBackoff($attempt);
            usleep($backoffDelay * 1000); // تبدیل به میکروثانیه
        }

        // لاگ خطاهای نهایی
        $this->log("All attempts failed for URL: $url", self::COLOR_RED);
        foreach ($errors as $index => $error) {
            $this->log("Error " . ($index + 1) . ": $error", self::COLOR_RED);
        }

        return null;
    }

    private function saveProductToDatabase(array $productData): void
    {
        try {
            // آماده‌سازی داده‌ها
            $data = [
                'product_id' => $productData['product_id'] ?? null,
                'title' => $productData['title'] ?? '',
                'price' => $productData['price'] ?? 0,
                'page_url' => $productData['page_url'] ?? '',
                'availability' => $productData['availability'] ?? 0,
                'off' => $productData['off'] ?? 0,
                'image' => $productData['image'] ?? '',
                'guarantee' => $productData['guarantee'] ?? '',
                'category' => $productData['category'] ?? '',
                'updated_at' => now(),
            ];

            // چک کردن وجود محصول
            $existingProduct = Product::where('page_url', $data['page_url'])->first();

            if ($existingProduct) {
                // بررسی تغییرات
                $changes = $this->detectProductChanges($existingProduct, $data);

                if (!empty($changes)) {
                    // آپدیت محصول موجود
                    $existingProduct->update($data);

                    // لاگ آپدیت با جزئیات تغییرات
                    $this->logProduct($productData, 'UPDATED', $changes);

                    $this->log("📝 محصول آپدیت شد - تعداد تغییرات: " . count($changes), self::COLOR_BLUE);
                } else {
                    // هیچ تغییری نداشته
                    $this->log("⚡ محصول بدون تغییر: {$data['title']}", self::COLOR_GRAY);
                }
            } else {
                // ایجاد محصول جدید
                $data['created_at'] = now();
                Product::create($data);

                // لاگ محصول جدید
                $this->logProduct($productData, 'NEW');

                $this->log("🎉 محصول جدید ایجاد شد: {$data['title']}", self::COLOR_GREEN);
            }

        } catch (\Exception $e) {
            $this->log("💥 خطا در ذخیره محصول {$productData['title']}: {$e->getMessage()}", self::COLOR_RED);
            throw $e;
        }
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

        // ✅ بررسی فلگ price_status که در extractProductData ست می‌شه
        if (isset($productData['price_status']) && $productData['price_status'] == 'unpriced') {
            $this->log("Product has no price but is marked as 'unpriced'. Accepting product for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        // ✅ بررسی مستقیم متن قیمت با کلمات کلیدی (نه price_text که وجود نداره!)
        if (!empty($productData['price']) && !is_numeric($productData['price'])) {
            $priceKeywords = $this->config['price_keywords']['unpriced'] ?? [];
            foreach ($priceKeywords as $keyword) {
                if (mb_strpos($productData['price'], $keyword) !== false) {
                    $this->log("Product price contains unpriced keyword: '{$keyword}'. Accepting product for URL: {$productData['page_url']}", self::COLOR_YELLOW);
                    return true;
                }
            }
        }

        // اگر قیمت خالی است، هشدار می‌دهیم اما محصول را می‌پذیریم
        if (empty($productData['price']) || $productData['price'] === '0') {
            $this->log("Warning: price is empty for available product, but product will be saved for URL: {$productData['page_url']}", self::COLOR_YELLOW);
            return true;
        }

        $this->log("Product data validated successfully for URL: {$productData['page_url']}", self::COLOR_GREEN);
        return true;
    }

    public function extractProductData(string $url, ?string $body = null, ?string $mainPageImage = null, ?string $mainPageProductId = null): ?array
    {
        $data = [
            'title' => '',
            'price' => $this->config['keep_price_format'] ?? false ? '' : '0',
            'product_id' => $mainPageProductId ?? '',
            'page_url' => $url,
            'availability' => null,
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

            // اگر set_category در کانفیگ وجود داشت، آن را استفاده کن
            if (isset($this->config['set_category']) && !empty($this->config['set_category'])) {
                $data['category'] = $this->config['set_category'];
                $this->log("Using preset category from config: {$data['category']}", self::COLOR_GREEN);
            }

            foreach ($productSelectors as $field => $selector) {
                if (!empty($selector['selector']) && array_key_exists($field, $data)) {
                    if ($field === 'guarantee') {
                        $data[$field] = $this->extractGuaranteeFromSelector($crawler, $selector, $data['title']);
                    } elseif ($field === 'category' && ($this->config['category_method'] ?? 'selector') === 'selector' && !isset($this->config['set_category'])) {
                        // استخراج دسته‌بندی با دو سلکتور
                        $data[$field] = $this->extractCategoriesFromSelectors($crawler, $selector);
                        $this->log("Extracted categories from selectors: {$data[$field]}", self::COLOR_GREEN);
                    } elseif ($field === 'image' && $this->config['image_method'] === 'product_page') {
                        $value = $this->extractData($crawler, $selector);
                        $data[$field] = $this->makeAbsoluteUrl($value);
                        $this->log("Extracted image from product_page: {$data[$field]}", self::COLOR_GREEN);
                    } // بخش مربوط به product_id در تابع extractProductData را با این کد جایگزین کنید:

                    elseif ($field === 'product_id' && $this->config['product_id_source'] === 'product_page') {
                        // استفاده از متد بهبود یافته extractProductIdFromUrl که حالا فرمت جدید را پشتیبانی می‌کند
                        $extractedId = $this->extractProductIdFromUrl($url, $data['title'] ?? '', $crawler);
                        if (!empty($extractedId)) {
                            $data[$field] = $extractedId;
                            $this->log("Extracted product_id from updated method: {$data[$field]}", self::COLOR_GREEN);
                        } else {
                            $this->log("No product_id extracted, keeping mainPageProductId or empty", self::COLOR_YELLOW);
                        }
                    } elseif ($field === 'price') {
                        // ✅ پردازش price با متد جدید یا قدیم
                        if (method_exists($this, 'extractPriceWithPriority')) {
                            $value = $this->extractPriceWithPriority($crawler, $selector);
                            $this->log("Raw price extracted with priority method: '$value'", self::COLOR_YELLOW);
                        } else {
                            // fallback به روش قدیم
                            $priceSelectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];
                            $value = '';

                            if (isset($priceSelectors[0])) {
                                $this->log("Trying primary price selector: '{$priceSelectors[0]}'", self::COLOR_YELLOW);
                                $elements = $selector['type'] === 'css' ? $crawler->filter($priceSelectors[0]) : $crawler->filterXPath($priceSelectors[0]);
                                if ($elements->count() > 0) {
                                    $value = trim($elements->text());
                                    $this->log("Price extracted from primary selector: '$value'", self::COLOR_GREEN);
                                }
                            }

                            if (empty($value) && isset($priceSelectors[1])) {
                                $this->log("Trying secondary price selector: '{$priceSelectors[1]}'", self::COLOR_YELLOW);
                                $elements = $selector['type'] === 'css' ? $crawler->filter($priceSelectors[1]) : $crawler->filterXPath($priceSelectors[1]);
                                if ($elements->count() > 0) {
                                    $value = trim($elements->text());
                                    $this->log("Price extracted from secondary selector: '$value'", self::COLOR_GREEN);
                                }
                            }
                        }

                        // بررسی کلمات کلیدی "قیمت‌گذاری نشده"
                        $priceKeywords = $this->config['price_keywords']['unpriced'] ?? [];
                        $isUnpriced = false;

                        foreach ($priceKeywords as $keyword) {
                            if (!empty($value) && mb_strpos($value, $keyword) !== false) {
                                $isUnpriced = true;
                                $data[$field] = trim($value); // متن اصلی
                                $data['price_status'] = 'unpriced'; // فلگ اضافه کردن
                                $this->log("Price is marked as unpriced text: '$value'", self::COLOR_YELLOW);
                                break;
                            }
                        }

                        // ✅ فقط در صورتی که unpriced نباشد، قیمت رو پردازش کن
                        if (!$isUnpriced) {
                            if (!empty($value)) {
                                if ($this->config['keep_price_format'] ?? false) {
                                    $data[$field] = $this->cleanPriceWithFormat($value);
                                } else {
                                    $data[$field] = (string)$this->cleanPrice($value);
                                }
                            } else {
                                $data[$field] = $this->config['keep_price_format'] ?? false ? '' : '0';
                                $this->log("No valid price found, setting default: '{$data[$field]}'", self::COLOR_YELLOW);
                            }
                        }
                    } else {
                        // سایر فیلدها
                        $value = $this->extractData($crawler, $selector);
                        $this->log("Raw $field extracted: '$value'", self::COLOR_YELLOW);

                        if ($field === 'title') {
                            $data[$field] = $value;
                            $data[$field] = $this->applyTitlePrefix($data[$field], $url);
                            $this->log("Title after applying prefix: {$data[$field]}", self::COLOR_GREEN);
                        } elseif ($field === 'availability') {
                            // پردازش availability با parseAvailability
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = (int)$this->$transform($value, $crawler);
                                $this->log("Availability processed by $transform: {$data[$field]}", self::COLOR_CYAN);
                            } else {
                                // fallback: تبدیل مستقیم
                                $data[$field] = !empty($value) ? 1 : 0;
                                $this->log("Availability fallback processing: {$data[$field]}", self::COLOR_YELLOW);
                            }
                        } elseif ($field === 'off') {
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = $this->$transform($value); // حذف (int) cast
                                $this->log("Off processed by $transform: {$data[$field]}", self::COLOR_CYAN);
                            } else {
                                // fallback: تلاش برای استخراج عدد از متن
                                preg_match('/\d+/', $value, $matches);
                                $data[$field] = !empty($matches) ? (int)$matches[0] : 0;
                                $this->log("Off fallback processing: {$data[$field]}", self::COLOR_YELLOW);
                            }
                        } else {
                            $transform = $this->config['data_transformers'][$field] ?? null;
                            if ($transform && method_exists($this, $transform)) {
                                $data[$field] = (string)$this->$transform($value);
                            } else {
                                $data[$field] = (string)$value;
                            }
                        }
                    }

                    $this->log("Extracted $field: \"{$data[$field]}\" for $url", self::COLOR_GREEN);
                }
            }

            // اگر set_category وجود نداشت و category_method برابر title بود، دسته‌بندی را از عنوان استخراج کن
            if (!isset($this->config['set_category']) && ($this->config['category_method'] ?? 'selector') === 'title' && !empty($data['title'])) {
                $wordCount = $this->config['category_word_count'] ?? 1;
                $data['category'] = $this->extractCategoryFromTitle($data['title'], $wordCount);
                $this->log("Extracted category from title: {$data['category']}", self::COLOR_GREEN);
            }

            // فقط اگر availability پردازش نشده باشد، fallback استفاده کن
            if ($data['availability'] === null) {
                $this->log("Availability not processed, using fallback logic", self::COLOR_YELLOW);

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
            }

            // اطمینان از نوع داده‌ها
            $data['availability'] = (int)$data['availability'];

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

    private function extractPriceWithPriority(Crawler $crawler, array $selector): string
    {
        $selectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];
        $foundSelectors = [];
        $value = '';

        $this->log("Starting price extraction with priority from " . count($selectors) . " selectors", self::COLOR_YELLOW);

        // بررسی وجود هر سلکتور و ذخیره آنها
        foreach ($selectors as $index => $sel) {
            $this->log("Checking selector [$index]: '$sel'", self::COLOR_YELLOW);
            $elements = $selector['type'] === 'css' ? $crawler->filter($sel) : $crawler->filterXPath($sel);

            if ($elements->count() > 0) {
                $extractedValue = $selector['attribute'] ?? false
                    ? ($elements->attr($selector['attribute']) ?? '')
                    : trim($elements->text());

                if (!empty($extractedValue)) {
                    $foundSelectors[$index] = [
                        'selector' => $sel,
                        'value' => $extractedValue
                    ];
                    $this->log("Found value with selector [$index] '$sel': '$extractedValue'", self::COLOR_GREEN);
                }
            }
        }

        // اگر هیچ سلکتوری پیدا نشد
        if (empty($foundSelectors)) {
            $this->log("No valid selectors found for price extraction", self::COLOR_RED);
            return '';
        }

        // انتخاب آخرین سلکتور موجود (بالاترین اولویت)
        $selectedIndex = max(array_keys($foundSelectors));
        $selectedData = $foundSelectors[$selectedIndex];

        $this->log("Selected price from selector [$selectedIndex] '{$selectedData['selector']}': '{$selectedData['value']}'", self::COLOR_GREEN);

        // لاگ سلکتورهای نادیده گرفته شده
        foreach ($foundSelectors as $index => $data) {
            if ($index !== $selectedIndex) {
                $this->log("Ignored selector [$index] '{$data['selector']}' with value: '{$data['value']}'", self::COLOR_YELLOW);
            }
        }

        return $selectedData['value'];
    }

    private function extractCategoriesFromSelectors(Crawler $crawler, array $selector): string
    {
        $categories = [];

        // چک کردن اینکه آیا سلکتور آرایه‌ای از سلکتورها است یا یک سلکتور ساده
        $selectors = is_array($selector['selector']) ? $selector['selector'] : [$selector['selector']];

        $this->log("Starting category extraction with " . count($selectors) . " selectors", self::COLOR_YELLOW);

        foreach ($selectors as $index => $selectorString) {
            if (empty($selectorString)) {
                $this->log("Skipping empty selector at index $index", self::COLOR_YELLOW);
                continue;
            }

            $this->log("Trying category selector [$index]: '$selectorString'", self::COLOR_YELLOW);

            try {
                // انتخاب المان‌ها بر اساس نوع سلکتور (CSS یا XPath)
                $elements = $selector['type'] === 'css'
                    ? $crawler->filter($selectorString)
                    : $crawler->filterXPath($selectorString);

                if ($elements->count() > 0) {
                    // اگر چندین المان وجود داشت، همه را پردازش کن
                    $elements->each(function (Crawler $element) use (&$categories, $selector, $index) {
                        // استخراج متن از attribute مشخص شده یا text
                        $categoryText = $selector['attribute'] ?? false
                            ? ($element->attr($selector['attribute']) ?? '')
                            : trim($element->text());

                        if (!empty($categoryText)) {
                            // پاک کردن فاصله‌های اضافی و کاراکترهای ناخواسته
                            $categoryText = $this->cleanCategoryText($categoryText);

                            if (!empty($categoryText)) {
                                $categories[] = $categoryText;
                                $this->log("Found category with selector [$index]: '$categoryText'", self::COLOR_GREEN);
                            }
                        }
                    });
                } else {
                    $this->log("No elements found with selector [$index]: '$selectorString'", self::COLOR_YELLOW);
                }
            } catch (\Exception $e) {
                $this->log("Error with selector [$index] '$selectorString': {$e->getMessage()}", self::COLOR_RED);
            }
        }

        // حذف دسته‌بندی‌های تکراری و خالی
        $categories = array_filter(array_unique($categories), function ($cat) {
            return !empty(trim($cat));
        });

        // حذف محدودیت تعداد دسته‌بندی - همه دسته‌بندی‌های پیدا شده حفظ می‌شوند

        // ترکیب دسته‌بندی‌ها با کاما
        $finalCategory = implode(', ', $categories);

        if (!empty($finalCategory)) {
            $this->log("Final combined categories: '$finalCategory' (Total: " . count($categories) . ")", self::COLOR_GREEN);
        } else {
            $this->log("No categories found from any selectors", self::COLOR_RED);
        }

        return $finalCategory;
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
        $attributes = isset($selector['attribute'])
            ? (is_array($selector['attribute']) ? $selector['attribute'] : [$selector['attribute']])
            : [null];

        $value = '';

        foreach ($selectors as $index => $sel) {
            $this->log("Trying selector [$index]: '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_YELLOW);

            $elements = $selector['type'] === 'css' ? $crawler->filter($sel) : $crawler->filterXPath($sel);

            if ($elements->count() > 0) {
                // تعیین attribute مربوط به این سلکتور
                $currentAttribute = $attributes[$index] ?? $attributes[0] ?? null;

                if ($currentAttribute) {
                    $value = $elements->attr($currentAttribute) ?? '';
                    $this->log("Extracting attribute '$currentAttribute' from selector '$sel'", self::COLOR_CYAN);
                } else {
                    $value = trim($elements->text());
                    $this->log("Extracting text content from selector '$sel'", self::COLOR_CYAN);
                }

                if (!empty($value)) {
                    $this->log("Found value: '$value' with selector '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_GREEN);
                    break; // اگر مقدار معتبر پیدا شد، از حلقه خارج شو
                } else {
                    $this->log("Empty value from selector '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_YELLOW);
                }
            } else {
                $this->log("No elements found with selector '$sel' for field: " . ($field ?? 'unknown'), self::COLOR_YELLOW);
            }
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

    private function parseAvailability(string $value, Crawler $crawler): int
    {
        $this->validateConfig($this->config, [
            'availability_mode',
            'selectors',
            'availability_keywords',
            'out_of_stock_button',
            'out_of_stock_selector'
        ]);

        $outOfStockButton = $this->config['out_of_stock_button'] ?? false;
        $outOfStockSelector = $this->config['selectors']['product_page']['out_of_stock'] ?? null;
        $availabilitySelector = $this->config['selectors']['product_page']['availability'] ?? null;
        $positiveKeywords = $this->config['availability_keywords']['positive'] ?? ['موجود', 'افزودن به سبد خرید'];
        $negativeKeywords = $this->config['availability_keywords']['negative'] ?? ['ناموجود', 'اتمام موجودی'];

        // ✅ اضافه کردن کلمات کلیدی قیمت
        $unpricedKeywords = $this->config['price_keywords']['unpriced'] ?? [];

        $this->log("Starting availability detection with value: '$value'", self::COLOR_CYAN);

        // ✅ Priority 0: Check if value contains unpriced keywords (highest priority)
        if (!empty($value)) {
            foreach ($unpricedKeywords as $keyword) {
                if (stripos($value, $keyword) !== false) {
                    $this->log("✅ Product available due to unpriced keyword: '$keyword' in availability text", self::COLOR_GREEN);
                    return 1;
                }
            }
        }

        // Priority 1: Check out-of-stock selector if out_of_stock_button is true
        if ($outOfStockButton) {
            $outOfStockResult = $this->checkOutOfStockWithPriority($crawler, $outOfStockSelector);
            if ($outOfStockResult === 0) {
                $this->log("Final decision: Product unavailable due to out-of-stock selector", self::COLOR_RED);
                return 0;
            }
        }

        // Priority 2: Check availability selectors
        $availabilityStatus = $this->checkMultipleAvailabilitySelectors($crawler, $availabilitySelector, $positiveKeywords, $negativeKeywords, $unpricedKeywords);
        if ($availabilityStatus !== null) {
            $this->log("Final decision: Product availability set to " . ($availabilityStatus ? 'Available' : 'Unavailable'), $availabilityStatus ? self::COLOR_GREEN : self::COLOR_RED);
            return $availabilityStatus;
        }

        // Fallback: Assume unavailable if no clear indicators
        $fallback = $this->config['default_availability'] ?? 0;
        $this->log("No clear availability indicators found, using fallback: " . ($fallback ? 'Available' : 'Unavailable'), $fallback ? self::COLOR_GREEN : self::COLOR_RED);
        return $fallback;
    }

    private function checkMultipleAvailabilitySelectors(Crawler $crawler, ?array $stockSelector, array $positiveKeywords, array $negativeKeywords, array $unpricedKeywords = []): ?int
    {
        if (!$stockSelector || empty($stockSelector['selector'])) {
            $this->log("Availability selector not defined", self::COLOR_YELLOW);
            return null;
        }

        $selectors = is_array($stockSelector['selector']) ? $stockSelector['selector'] : [$stockSelector['selector']];

        foreach ($selectors as $index => $selector) {
            $this->log("📦 Checking availability selector [$index]: $selector", self::COLOR_YELLOW);
            $elements = $this->getElements($crawler, ['selector' => $selector, 'type' => $stockSelector['type'] ?? 'css']);

            if ($elements->count() > 0) {
                $stockText = $this->extractData($crawler, ['selector' => $selector, 'type' => $stockSelector['type'] ?? 'css'], 'availability');

                if (!empty($stockText)) {
                    $this->log("📄 Availability text found: '$stockText'", self::COLOR_YELLOW);

                    // ✅ Check unpriced keywords first (highest priority)
                    foreach ($unpricedKeywords as $keyword) {
                        if (stripos($stockText, $keyword) !== false) {
                            $this->log("💰 Product available due to unpriced keyword: '$keyword'", self::COLOR_GREEN);
                            return 1;
                        }
                    }

                    // Check negative keywords second
                    foreach ($negativeKeywords as $keyword) {
                        if (stripos($stockText, $keyword) !== false) {
                            $this->log("🚫 Product unavailable due to negative keyword: $keyword", self::COLOR_RED);
                            return 0;
                        }
                    }

                    // Check positive keywords last
                    foreach ($positiveKeywords as $keyword) {
                        if (stripos($stockText, $keyword) !== false) {
                            $this->log("✅ Product available due to positive keyword: $keyword", self::COLOR_GREEN);
                            return 1;
                        }
                    }

                    $this->log("⚠️ Availability text found but no matching keywords: '$stockText'", self::COLOR_YELLOW);
                    return null; // Text found but no keyword match
                } else {
                    $this->log("⚠️ Availability selector found but no content extracted", self::COLOR_YELLOW);
                    return null;
                }
            }
        }

        $this->log("❌ No availability selectors found", self::COLOR_YELLOW);
        return null;
    }

    private function checkOutOfStockWithPriority(Crawler $crawler, ?array $outOfStockSelector): ?int
    {
        if (!$outOfStockSelector || empty($outOfStockSelector['selector'])) {
            $this->log("Out-of-stock selector not defined", self::COLOR_YELLOW);
            return null;
        }

        $selectors = is_array($outOfStockSelector['selector']) ? $outOfStockSelector['selector'] : [$outOfStockSelector['selector']];

        foreach ($selectors as $index => $selector) {
            $this->log("🔍 Checking out-of-stock selector [$index]: $selector", self::COLOR_YELLOW);
            $elements = $this->getElements($crawler, ['selector' => $selector, 'type' => $outOfStockSelector['type'] ?? 'css']);

            if ($elements->count() > 0) {
                $this->log("🚨 Out-of-stock selector found, product is unavailable", self::COLOR_RED);
                return 0; // Selector exists, product is unavailable
            }
        }

        $this->log("✅ No out-of-stock selectors found, proceeding with availability check", self::COLOR_GREEN);
        return null; // No out-of-stock selector found, continue with other checks
    }

    private function cleanOff(string $text): int
    {
        $this->log("Raw off value: '$text'", self::COLOR_YELLOW);
        $text = trim($text);

        if (empty($text)) {
            $this->log("Off value is empty, returning 0", self::COLOR_YELLOW);
            return 0;
        }

        // بررسی وجود علامت درصد
        if (strpos($text, '%') !== false) {
            // استخراج عدد قبل از علامت درصد
            preg_match('/(\d+)%/', $text, $matches);
            if (!empty($matches[1])) {
                $value = (int)$matches[1];
                $this->log("Processed off (percentage with regex): $value", self::COLOR_GREEN);
                return $value;
            }

            // fallback: حذف علامت درصد و تبدیل به عدد
            $value = (int)str_replace('%', '', $text);
            $this->log("Processed off (percentage with str_replace): $value", self::COLOR_GREEN);
            return $value;
        }

        // استخراج اولین عدد موجود در متن
        preg_match('/\d+/', $text, $matches);
        if (!empty($matches)) {
            $value = (int)$matches[0];
            $this->log("Processed off (numeric): $value", self::COLOR_GREEN);
            return $value;
        }

        $this->log("No valid number found in off value '$text', returning 0", self::COLOR_RED);
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

        // پردازش product_id از product_page با ساختار جدید
        if ($this->config['product_id_source'] === 'product_page') {
            $productIdConfig = $this->config['selectors']['product_page']['product_id'] ?? [];

            if (!empty($productIdConfig)) {
                // بررسی فرمت جدید (آرایه از اشیاء) یا قدیم
                if ($this->isNewProductIdFormat($productIdConfig)) {
                    $this->log("Processing product_id with new array format", self::COLOR_CYAN);
                    return $this->extractProductIdWithNewFormat($crawler, $productIdConfig, $url);
                } else {
                    // فرمت قدیمی - استفاده از extractData
                    $this->log("Processing product_id with legacy format", self::COLOR_CYAN);
                    $value = $this->extractData($crawler, $productIdConfig, 'product_id');
                    if (!empty($value)) {
                        $this->log("Extracted product_id from legacy format: $value for $url", self::COLOR_GREEN);
                        return $value;
                    } else {
                        $this->log("No product_id found with legacy format for $url", self::COLOR_YELLOW);
                    }
                }
            }

            // تلاش برای استخراج از اسکریپت‌ها (fallback)
            $patterns = $this->config['product_id_fallback_script_patterns'] ?? [];
            if (!empty($patterns)) {
                $this->log("Trying fallback script patterns for product_id extraction", self::COLOR_CYAN);
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

    /**
     * بررسی اینکه آیا کانفیگ product_id در فرمت جدید است یا نه
     */
    private function isNewProductIdFormat(array $config): bool
    {
        // اگر کلید selector وجود نداشته باشد، احتمالاً فرمت جدید است
        if (!isset($config['selector'])) {
            return true;
        }

        // اگر selector یک آرایه است و عنصر اول آن یک آرایه است، فرمت جدید است
        if (is_array($config['selector']) && isset($config['selector'][0]) && is_array($config['selector'][0])) {
            return true;
        }

        // اگر مستقیماً آرایه‌ای از اشیاء باشد (بدون کلید selector)
        if (isset($config[0]) && is_array($config[0]) && isset($config[0]['type'])) {
            return true;
        }

        return false;
    }

    /**
     * استخراج product_id با فرمت جدید
     */
    private function extractProductIdWithNewFormat(Crawler $crawler, array $config, string $url): string
    {
        // تعیین آرایه سلکتورها
        $selectors = [];

        if (isset($config[0]) && is_array($config[0])) {
            // فرمت: [{"type": "css", "selector": "...", "attribute": "..."}, {...}]
            $selectors = $config;
        } elseif (isset($config['selector']) && is_array($config['selector']) && is_array($config['selector'][0])) {
            // فرمت: {"selector": [{"type": "css", "selector": "...", "attribute": "..."}, {...}]}
            $selectors = $config['selector'];
        }

        foreach ($selectors as $index => $selectorConfig) {
            $this->log("Trying product_id selector [$index]: '{$selectorConfig['selector']}'", self::COLOR_YELLOW);

            $elements = $selectorConfig['type'] === 'css'
                ? $crawler->filter($selectorConfig['selector'])
                : $crawler->filterXPath($selectorConfig['selector']);

            if ($elements->count() > 0) {
                $value = isset($selectorConfig['attribute'])
                    ? ($elements->attr($selectorConfig['attribute']) ?? '')
                    : trim($elements->text());

                if (!empty($value)) {
                    $this->log("Extracted product_id from selector [$index] '{$selectorConfig['selector']}': $value for $url", self::COLOR_GREEN);
                    return $value;
                } else {
                    $this->log("Empty value from selector [$index] '{$selectorConfig['selector']}' for $url", self::COLOR_YELLOW);
                }
            } else {
                $this->log("No elements found with selector [$index] '{$selectorConfig['selector']}' for $url", self::COLOR_YELLOW);
            }
        }

        $this->log("No product_id found with any new format selectors for $url", self::COLOR_RED);
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

    private function getProductLinksFromDatabase(?int $start_id = null): array
    {
        $this->log("Fetching product links from database" . ($start_id ? " starting from ID $start_id" : ""), self::COLOR_GREEN);

        try {
            $query = DB::table('links')
                ->where('is_processed', 0)
                ->select('id', 'url', 'source_url', 'product_id') // اضافه کردن id برای بهتر tracking
                ->orderBy('id'); // مرتب‌سازی برای consistency

            if ($start_id !== null) {
                $query->where('id', '>=', $start_id);
            }

            $links = $query->get()->map(function ($link) {
                return [
                    'id' => $link->id, // اضافه کردن id
                    'url' => $link->url,
                    'sourceUrl' => $link->source_url,
                    'product_id' => $link->product_id
                ];
            })->toArray();

            $this->log("Retrieved " . count($links) . " unprocessed links from database" . ($start_id ? " with ID >= $start_id" : ""), self::COLOR_GREEN);

            // لاگ بازه IDها برای دیباگ
            if (!empty($links)) {
                $ids = array_column($links, 'id');
                $this->log("Link ID range: " . min($ids) . " to " . max($ids), self::COLOR_YELLOW);
            }

            // محاسبه صفحات پردازش شده بر اساس source_url های منحصر به فرد
            $pagesProcessed = DB::table('links')
                ->distinct()
                ->count('source_url');

            return [
                'links' => $links,
                'pages_processed' => $pagesProcessed
            ];

        } catch (\Exception $e) {
            $this->log("Failed to fetch links from database: {$e->getMessage()}", self::COLOR_RED);
            return [
                'links' => [],
                'pages_processed' => 0
            ];
        }
    }

    private function saveProductLinksToDatabase(array $links): void
    {
        if (empty($links)) {
            $this->log("No links to save to database", self::COLOR_YELLOW);
            return;
        }

        $this->log("Saving " . count($links) . " product links to database...", self::COLOR_GREEN);

        try {
            $insertData = [];
            $duplicateCount = 0;
            $batchSize = 1000; // پردازش batch برای performance بهتر

            // آماده‌سازی داده‌ها برای insert
            foreach ($links as $link) {
                $url = is_array($link) ? $link['url'] : $link;
                $sourceUrl = is_array($link) && isset($link['sourceUrl']) ? $link['sourceUrl'] : null;
                $productId = is_array($link) && isset($link['product_id']) ? $link['product_id'] : null;

                // بررسی معتبر بودن URL
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->log("Invalid URL skipped: " . ($url ?? 'empty'), self::COLOR_YELLOW);
                    continue;
                }

                $insertData[] = [
                    'url' => $url,
                    'source_url' => $sourceUrl,
                    'is_processed' => false,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            if (empty($insertData)) {
                $this->log("No valid links to insert", self::COLOR_YELLOW);
                return;
            }

            // استفاده از upsert برای جلوگیری از duplicate entries
            $chunks = array_chunk($insertData, $batchSize);
            $totalInserted = 0;

            foreach ($chunks as $chunk) {
                try {
                    // استفاده از insertOrIgnore برای جلوگیری از خطای duplicate
                    $inserted = DB::table('links')->insertOrIgnore($chunk);
                    $totalInserted += $inserted;

                } catch (\Exception $e) {
                    $this->log("Error inserting batch: " . $e->getMessage(), self::COLOR_RED);

                    // اگر batch insert شکست خورد، یکی یکی امتحان کنیم
                    foreach ($chunk as $item) {
                        try {
                            $existingLink = DB::table('links')->where('url', $item['url'])->exists();
                            if (!$existingLink) {
                                DB::table('links')->insert($item);
                                $totalInserted++;
                            } else {
                                $duplicateCount++;
                            }
                        } catch (\Exception $individualError) {
                            $this->log("Failed to insert link {$item['url']}: " . $individualError->getMessage(), self::COLOR_RED);
                        }
                    }
                }
            }

            $this->log("Successfully saved $totalInserted new links to database", self::COLOR_GREEN);

            if ($duplicateCount > 0) {
                $this->log("Skipped $duplicateCount duplicate links", self::COLOR_YELLOW);
            }

        } catch (\Exception $e) {
            $this->log("Critical error saving links to database: " . $e->getMessage(), self::COLOR_RED);
            throw $e;
        }
    }

    private function updateLinkProcessedStatus(string $url, bool $status = true): void
    {
        try {
            // بررسی معتبر بودن URL
            if (empty($url)) {
                $this->log("Cannot update status: empty URL provided", self::COLOR_RED);
                return;
            }

            $affected = DB::table('links')
                ->where('url', $url)
                ->update([
                    'is_processed' => $status,
                    'updated_at' => now()
                ]);

            if ($affected === 0) {
                $this->log("Link not found in database for status update: $url", self::COLOR_YELLOW);
            } else {
                $statusText = $status ? 'processed' : 'unprocessed';
                // فقط برای debug mode لاگ کنیم تا spam نشود
                if ($this->config['debug'] ?? false) {
                    $this->log("Marked $affected link(s) as $statusText: $url", self::COLOR_BLUE);
                }
            }

        } catch (\Exception $e) {
            $this->log("Error updating link status for $url: " . $e->getMessage(), self::COLOR_RED);
        }
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

    private function logProduct(array $product, string $action = 'PROCESSED', array $extraInfo = []): void
    {
        $availability = (int)($product['availability'] ?? 0) ? 'موجود' : 'ناموجود';
        $imageStatus = empty($product['image']) ? 'ناموجود' : 'موجود';
        $guaranteeStatus = empty($product['guarantee']) ? 'ندارد' : $product['guarantee'];
        $discount = (int)($product['off'] ?? 0) > 0 ? $product['off'] . '%' : '0%';
        $productId = $product['product_id'] ?? 'N/A';
        $price = $product['price'] ?? 'N/A';
        $title = $product['title'] ?? 'N/A';
        $category = $product['category'] ?? 'N/A';

        // انتخاب آیکون و رنگ بر اساس نوع عملیات
        $actionConfig = $this->getActionConfig($action);

        // لاگ عملیات با جزئیات
        $this->log($actionConfig['message'] . " $title (ID: $productId)", $actionConfig['color']);

        // اطلاعات اضافی برای هر نوع عملیات
        if (!empty($extraInfo)) {
            foreach ($extraInfo as $key => $value) {
                $this->log("  └─ $key: $value", self::COLOR_GRAY);
            }
        }

        // تولید جدول با هدر مخصوص هر عملیات
        $headers = ['Product ID', 'Title', 'Price', 'Category', 'Availability', 'Discount', 'Image', 'Guarantee'];
        $rows = [[
            $productId,
            $title,
            $price,
            $category,
            $availability,
            $discount,
            $imageStatus,
            $guaranteeStatus
        ]];

        // جدول با رنگ مخصوص عملیات
        $table = $this->generateAsciiTableWithColor($headers, $rows, $actionConfig['tableColor']);
        $this->log($table, null);

        // فاصله بین محصولات
        $this->log("", null);
    }

    private function getActionConfig(string $action): array
    {
        $configs = [
            'NEW' => [
                'message' => '🆕 محصول جدید اضافه شد:',
                'color' => self::COLOR_GREEN,
                'tableColor' => self::COLOR_GREEN
            ],
            'UPDATED' => [
                'message' => '🔄 محصول آپدیت شد:',
                'color' => self::COLOR_BLUE,
                'tableColor' => self::COLOR_BLUE
            ],
            'RETRY_SUCCESS' => [
                'message' => '✅ محصول از failed_links بازیابی شد:',
                'color' => self::COLOR_PURPLE,
                'tableColor' => self::COLOR_PURPLE
            ],
            'FAILED' => [
                'message' => '❌ محصول ناموفق:',
                'color' => self::COLOR_RED,
                'tableColor' => self::COLOR_RED
            ],
            'PROCESSED' => [
                'message' => '📦 محصول پردازش شد:',
                'color' => self::COLOR_YELLOW,
                'tableColor' => self::COLOR_YELLOW
            ]
        ];

        return $configs[$action] ?? $configs['PROCESSED'];
    }

    private function detectProductChanges($existingProduct, array $newData): array
    {
        $changes = [];
        $fieldsToCheck = ['title', 'price', 'availability', 'off', 'image', 'guarantee', 'category'];

        foreach ($fieldsToCheck as $field) {
            $oldValue = $existingProduct->$field;
            $newValue = $newData[$field] ?? null;

            if ($oldValue != $newValue) {
                $changes["$field تغییر"] = "$oldValue → $newValue";
            }
        }

        return $changes;
    }

    private function handleRetryFailure(FailedLink $link, string $errorMessage): void
    {
        $this->log("❌ شکست در تلاش مجدد: {$link->url}", self::COLOR_RED);
        $this->log("  └─ خطا: $errorMessage", self::COLOR_RED);

        $link->attempts = $link->attempts + 1;
        $link->error_message = $errorMessage;
        $link->save();
    }

    private function cleanupExhaustedLinks(int $maxAttempts): void
    {
        $exhaustedLinks = FailedLink::where('attempts', '>=', $maxAttempts)->get();

        if ($exhaustedLinks->count() > 0) {
            $this->log("🗑️  حذف " . $exhaustedLinks->count() . " لینک منقضی از صف تلاش مجدد...", self::COLOR_YELLOW);

            foreach ($exhaustedLinks as $link) {
                $this->log("💀 حداکثر تلاش رسیده - حذف شد: {$link->url}", self::COLOR_RED);
                $this->log("  └─ آخرین خطا: {$link->error_message}", self::COLOR_RED);
            }

            FailedLink::where('attempts', '>=', $maxAttempts)->delete();
            $this->log("✅ لینک‌های منقضی حذف شدند", self::COLOR_GREEN);
        }
    }

    private function shouldDisplayLog(string $cleanMessage): bool
    {
        $displayConditions = [
            // محصولات و عملیات
            str_contains($cleanMessage, '🆕') || str_contains($cleanMessage, '🔄') ||
            str_contains($cleanMessage, '✅') || str_contains($cleanMessage, '❌'),

            // جداول ASCII
            str_starts_with($cleanMessage, '+') && str_contains($cleanMessage, '|'),

            // عملیات مهم
            str_starts_with($cleanMessage, 'Fetching page') ||
            str_starts_with($cleanMessage, 'Completed processing page') ||
            str_contains($cleanMessage, 'Extracted product_id') ||
            str_contains($cleanMessage, 'failed_links') ||

            // خطاها
            str_contains($cleanMessage, 'Failed to fetch') ||
            str_contains($cleanMessage, 'Invalid link') ||

            // گزارش‌ها
            str_contains($cleanMessage, '═══') || str_contains($cleanMessage, '───') ||

            // لاگ‌های Playwright
            str_contains($cleanMessage, 'Playwright') || // اضافه کردن لاگ‌های Playwright
            str_contains($cleanMessage, 'Starting Playwright') ||
            str_contains($cleanMessage, 'Temporary script file') ||
            str_contains($cleanMessage, 'Playwright console log')
        ];

        return array_reduce($displayConditions, function ($carry, $condition) {
            return $carry || $condition;
        }, false);
    }

    private function generateAsciiTableWithColor(array $headers, array $rows, string $color): string
    {
        $table = $this->generateAsciiTable($headers, $rows);
        return $color . $table . "\033[0m"; // اضافه کردن رنگ و ریست
    }

    private function log(string $message, ?string $color = null): void
    {
        // اضافه کردن رنگ خاکستری
        if (!defined('self::COLOR_GRAY')) {
            $this->COLOR_GRAY;
        }

        $colorReset = "\033[0m";
        $formattedMessage = $color ? $color . $message . $colorReset : $message;

        // ذخیره در فایل لاگ
        $logFile = storage_path('logs/scraper_' . date('Ymd') . '.log');
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);

        // حذف کدهای رنگی برای بررسی
        $cleanMessage = preg_replace("/\033\[[0-9;]*m/", "", $message);

        // شرایط نمایش لاگ‌های مهم (بهبود یافته)
        $shouldDisplay = $this->shouldDisplayLog($cleanMessage);

        if ($shouldDisplay) {
            if ($this->outputCallback) {
                call_user_func($this->outputCallback, $formattedMessage);
            } else {
                echo $formattedMessage . PHP_EOL;
            }
        }
    }

    private function applyTitlePrefix(string $title, string $url): string
    {
        $title = trim($title);
        $prefixRules = $this->config['title_prefix_rules'] ?? [];
        $productsUrls = $this->config['products_urls'] ?? [];

        // بررسی اینکه آیا URL محصول از یکی از products_urls استخراج شده است
        foreach ($productsUrls as $productUrl) {
            if (isset($prefixRules[$productUrl])) {
                $prefix = $prefixRules[$productUrl]['prefix'] ?? '';

                if (empty($prefix)) {
                    $this->log("No prefix defined for rule on URL: $productUrl", self::COLOR_YELLOW);
                    return $title;
                }

                // بررسی اینکه آیا عنوان با پیشوند شروع می‌شود
                if (!str_starts_with($title, $prefix)) {
                    $newTitle = $prefix . ' ' . $title;
                    $this->log("Added prefix '$prefix' to title: '$newTitle' for URL: $url", self::COLOR_GREEN);
                    return $newTitle;
                } else {
                    $this->log("Title already starts with prefix '$prefix': '$title' for URL: $url", self::COLOR_YELLOW);
                    return $title;
                }
            }
        }

        $this->log("No title prefix rule matched for URL: $url", self::COLOR_YELLOW);
        return $title;
    }

    private function validateAndFixConfig(): void
    {
        $isProductTestMode = $this->config['product_test'] ?? false;
        if ($isProductTestMode) {
            $this->log("🧪 Product Test Mode - Using test-specific validation", self::COLOR_PURPLE);
            $this->validateProductTestConfig();
            return; // در حالت تست، نیاز به اعتبارسنجی معمولی نیست
        }
        // بررسی صحت کلیدهای مهم کانفیگ
        $this->log("Validating configuration...", self::COLOR_GREEN);

        // بررسی و اطمینان از وجود run_method
        if (!isset($this->config['run_method'])) {
            $this->config['run_method'] = 'new';
            $this->log("run_method was not set in config. Defaulting to 'new'", self::COLOR_YELLOW);
        }

        // تبدیل خودکار run_method به فرمت صحیح string
        $this->config['run_method'] = (string)$this->config['run_method'];

        // بررسی صحت مقدار run_method
        if (!in_array($this->config['run_method'], ['new', 'continue'])) {
            $this->log("WARNING: Invalid run_method '{$this->config['run_method']}' in config. Must be 'new' or 'continue'. Defaulting to 'new'", self::COLOR_RED);
            $this->config['run_method'] = 'new';
        }

        // بررسی set_category
        if (isset($this->config['set_category']) && !empty($this->config['set_category'])) {
            $this->log("Found set_category in config: '{$this->config['set_category']}'. Will use this value for all products.", self::COLOR_GREEN);
        }

        // لاگ کردن کانفیگ برای دیباگ
        $this->log("Config validated. Using run_method: {$this->config['run_method']}", self::COLOR_GREEN);

        // بررسی وجود کلیدهای مهم دیگر
        if (!isset($this->config['method'])) {
            $this->log("WARNING: 'method' is not set in config. Defaulting to 1", self::COLOR_YELLOW);
            $this->config['method'] = 1;
        }

        if (!isset($this->config['processing_method']) && $this->config['run_method'] === 'continue') {
            $this->log("WARNING: 'processing_method' is not set for continue mode. Using method {$this->config['method']} instead", self::COLOR_YELLOW);
            $this->config['processing_method'] = $this->config['method'];
        }
    }

    private function validateConfig(): void
    {

        $isProductTestMode = $this->config['product_test'] ?? false;
        if ($isProductTestMode) {
            $this->log("🧪 Product Test Mode - Using test-specific validation", self::COLOR_PURPLE);
            $this->validateProductTestConfig();
            return; // در حالت تست، نیاز به اعتبارسنجی معمولی نیست
        }
        $requiredFields = [
            'base_urls' => 'Base URLs are required.',
            'products_urls' => 'Products URLs are required.',
            'method' => 'Scraping method is required (1, 2, or 3).',
            'selectors' => 'Selectors configuration is required.',
            'out_of_stock_button' => 'Out of stock button configuration is required.',
        ];

        foreach ($requiredFields as $field => $message) {
            // برای out_of_stock_button از isset استفاده کن نه empty چون false مقدار معتبری است
            if ($field === 'out_of_stock_button') {
                if (!isset($this->config[$field])) {
                    throw new \Exception("Validation Error: $message");
                }
            } else {
                if (empty($this->config[$field])) {
                    throw new \Exception("Validation Error: $message");
                }
            }
        }

        if (!is_array($this->config['base_urls']) || count($this->config['base_urls']) < 1) {
            throw new \Exception("Validation Error: At least one base_url is required.");
        }
        if (!is_array($this->config['products_urls']) || count($this->config['products_urls']) < 1) {
            throw new \Exception("Validation Error: At least one products_url is required.");
        }

        // بررسی set_category اگر وجود داشت
        if (isset($this->config['set_category'])) {
            if (!is_string($this->config['set_category']) || empty(trim($this->config['set_category']))) {
                throw new \Exception("Validation Error: set_category must be a non-empty string.");
            }
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

        if (isset($this->config['processing_method']) && !in_array($this->config['processing_method'], [1, 2, 3])) {
            throw new \Exception('Validation Error: Invalid processing_method value. Must be 1, 2, or 3.');
        }

        // اگر set_category تنظیم نشده و category_method روی title است، اطمینان حاصل کن که category_word_count وجود دارد
        if (!isset($this->config['set_category']) && isset($this->config['category_method']) && $this->config['category_method'] === 'title') {
            if (!isset($this->config['category_word_count']) || !is_int($this->config['category_word_count']) || $this->config['category_word_count'] < 1) {
                throw new \Exception("Validation Error: 'category_word_count' must be a positive integer when 'category_method' is 'title' and 'set_category' is not used.");
            }
        }

        // اعتبارسنجی run_method
        if (isset($this->config['run_method']) && !in_array($this->config['run_method'], ['new', 'continue'])) {
            throw new \Exception("Invalid run_method. Use 'new' or 'continue'.");
        }

        if ($this->config['method'] === 1) {
            if (!isset($this->config['method_settings']['method_1']['pagination']['ignore_redirects'])) {
                $this->log("Warning: 'ignore_redirects' not set in method_1.pagination. Defaulting to false.", self::COLOR_YELLOW);
                $this->config['method_settings']['method_1']['pagination']['ignore_redirects'] = false;
            }

            if ($this->config['method_settings']['method_1']['pagination']['use_sample_url'] && empty($this->config['method_settings']['method_1']['pagination']['sample_url'])) {
                throw new \Exception("Validation Error: 'sample_url' is required when 'use_sample_url' is true.");
            }
        }

        if (isset($this->config['selectors']['main_page']['product_links']['product_id'])) {
            $productIdAttr = $this->config['selectors']['main_page']['product_links']['product_id'];
            if (empty($productIdAttr)) {
                throw new \Exception("Validation Error: 'product_id' attribute in product_links cannot be empty.");
            }
        }

        // اعتبارسنجی title_prefix_rules
        if (isset($this->config['title_prefix_rules'])) {
            if (!is_array($this->config['title_prefix_rules'])) {
                throw new \Exception("Validation Error: 'title_prefix_rules' must be an array.");
            }
            $productsUrls = $this->config['products_urls'] ?? [];
            foreach ($this->config['title_prefix_rules'] as $url => $rule) {
                if (!is_string($url) || empty($url)) {
                    throw new \Exception("Validation Error: Each key in 'title_prefix_rules' must be a valid non-empty URL string.");
                }
                if (!in_array($url, $productsUrls)) {
                    throw new \Exception("Validation Error: URL '$url' in 'title_prefix_rules' must match one of the 'products_urls'.");
                }
                if (!isset($rule['prefix']) || !is_string($rule['prefix']) || empty($rule['prefix'])) {
                    throw new \Exception("Validation Error: 'prefix' in 'title_prefix_rules' for URL '$url' is required and must be a non-empty string.");
                }
            }
        }

        // بررسی نوع داده out_of_stock_button - حالا که isset شده، بررسی می‌کنیم نوعش boolean باشد
        if (!is_bool($this->config['out_of_stock_button'])) {
            throw new \Exception("Validation Error: 'out_of_stock_button' must be a boolean value (true or false).");
        }

        // اگر out_of_stock_button فعال است، بررسی کن که selector های مربوطه تعریف شده باشند
        if ($this->config['out_of_stock_button'] === true) {
            if (!isset($this->config['selectors']['product_page']['out_of_stock']) ||
                empty($this->config['selectors']['product_page']['out_of_stock']['selector'])) {
                throw new \Exception("Validation Error: 'selectors.product_page.out_of_stock' must be defined with a non-empty 'selector' when 'out_of_stock_button' is true.");
            }
            $selector = $this->config['selectors']['product_page']['out_of_stock']['selector'];
            if (!is_string($selector) && !is_array($selector)) {
                throw new \Exception("Validation Error: 'selectors.product_page.out_of_stock.selector' must be a string or array.");
            }
            if (is_array($selector) && empty($selector)) {
                throw new \Exception("Validation Error: 'selectors.product_page.out_of_stock.selector' array cannot be empty.");
            }
            // بررسی نوع سلکتور (css یا xpath)
            if (!isset($this->config['selectors']['product_page']['out_of_stock']['type']) ||
                !in_array($this->config['selectors']['product_page']['out_of_stock']['type'], ['css', 'xpath'])) {
                throw new \Exception("Validation Error: 'selectors.product_page.out_of_stock.type' must be 'css' or 'xpath'.");
            }
        }

        $this->log('Configuration validated successfully.', self::COLOR_GREEN);
    }

    private function resetProductsAndLinks(): void
    {
        $this->log("Reset mode activated - clearing products and marking all links as unprocessed...", self::COLOR_YELLOW);

        // اطمینان از پاکسازی transaction‌های قبلی در صورت وجود
        while (DB::transactionLevel() > 0) {
            try {
                DB::rollBack();
            } catch (\Exception $e) {
                // ادامه می‌دهیم حتی اگر rollback ناموفق باشد
                break;
            }
        }

        try {
            // شروع transaction تازه
            DB::beginTransaction();

            // ریست کردن جدول products (حذف تمام محصولات)
            $productsCount = Product::count();
            if ($productsCount > 0) {
                Product::truncate();
                $this->log("Cleared $productsCount products from database", self::COLOR_GREEN);
            } else {
                $this->log("No products found to clear", self::COLOR_YELLOW);
            }

            // ریست کردن وضعیت is_processed در جدول links
            $linksUpdated = Link::where('is_processed', 1)->update(['is_processed' => 0]);
            $this->log("Reset $linksUpdated links to unprocessed state", self::COLOR_GREEN);

            // پاک کردن جدول failed_links برای شروع تازه
            $failedLinksCount = FailedLink::count();
            if ($failedLinksCount > 0) {
                FailedLink::truncate();
                $this->log("Cleared $failedLinksCount failed links from database", self::COLOR_GREEN);
            } else {
                $this->log("No failed links found to clear", self::COLOR_YELLOW);
            }

            // commit کردن transaction
            DB::commit();
            $this->log("Database reset completed successfully", self::COLOR_GREEN);

        } catch (\Exception $e) {
            // rollback در صورت بروز خطا
            try {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
            } catch (\Exception $rollbackException) {
                $this->log("Failed to rollback transaction: " . $rollbackException->getMessage(), self::COLOR_RED);
            }

            $this->log("Failed to reset database: " . $e->getMessage(), self::COLOR_RED);
            throw $e;
        }
    }

    private function validateProductTestConfig(): void
    {
        $this->log("Validating Product Test Mode configuration...", self::COLOR_BLUE);

        // بررسی وجود product_urls
        if (!isset($this->config['product_urls']) || empty($this->config['product_urls'])) {
            throw new \InvalidArgumentException("product_urls is required for Product Test Mode");
        }

        if (!is_array($this->config['product_urls'])) {
            throw new \InvalidArgumentException("product_urls must be an array");
        }

        // بررسی معتبر بودن URLs
        foreach ($this->config['product_urls'] as $index => $url) {
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException("Invalid URL at index $index: $url");
            }
        }

        // بررسی وجود selectors برای product_page
        if (!isset($this->config['selectors']['product_page'])) {
            throw new \InvalidArgumentException("product_page selectors are required for Product Test Mode");
        }

        // بررسی حداقل selectors ضروری
        $requiredSelectors = ['title', 'price'];
        $productPageSelectors = $this->config['selectors']['product_page'];

        foreach ($requiredSelectors as $selector) {
            if (!isset($productPageSelectors[$selector]) || empty($productPageSelectors[$selector]['selector'])) {
                throw new \InvalidArgumentException("Required selector '$selector' is missing or empty in product_page");
            }
        }

        // اعتبارسنجی timeout
        $timeout = $this->config['timeout'] ?? 60;
        if (!is_numeric($timeout) || $timeout <= 0) {
            $this->log("Invalid timeout value, using default: 60", self::COLOR_YELLOW);
            $this->config['timeout'] = 60;
        }

        // اعتبارسنجی delays
        $delayMin = $this->config['request_delay_min'] ?? 1000;
        $delayMax = $this->config['request_delay_max'] ?? 1000;

        if (!is_numeric($delayMin) || $delayMin < 0) {
            $this->log("Invalid request_delay_min, using default: 1000", self::COLOR_YELLOW);
            $this->config['request_delay_min'] = 1000;
        }

        if (!is_numeric($delayMax) || $delayMax < $delayMin) {
            $this->log("Invalid request_delay_max, using default: 1000", self::COLOR_YELLOW);
            $this->config['request_delay_max'] = 1000;
        }

        $this->log("✅ Product Test Mode configuration is valid", self::COLOR_GREEN);
        $this->log("📝 Testing " . count($this->config['product_urls']) . " product URLs", self::COLOR_BLUE);
    }

    private function runProductTestMode(): array
    {
        $this->log("🚀 Starting Product Test Mode", self::COLOR_GREEN);

        // بررسی وجود product_urls
        $productUrls = $this->config['product_urls'] ?? [];
        if (empty($productUrls)) {
            $this->log("❌ No product_urls found in config for test mode", self::COLOR_RED);
            return [
                'status' => 'error',
                'message' => 'No product_urls provided for test mode',
                'total_products' => 0,
                'failed_links' => 0,
                'products' => []
            ];
        }

        $this->log("📝 Found " . count($productUrls) . " product URLs to test", self::COLOR_GREEN);

        $successfulProducts = [];
        $failedProducts = [];

        foreach ($productUrls as $index => $url) {
            $this->log("🔍 Testing product " . ($index + 1) . "/" . count($productUrls) . ": $url", self::COLOR_BLUE);

            try {
                // استخراج داده‌های محصول with detailed logging
                $this->log("📡 Attempting to extract product data...", self::COLOR_YELLOW);
                $productData = $this->extractProductData($url);

                if ($productData !== null) {
                    $successfulProducts[] = $productData;
                    $this->log("✅ Product data extracted successfully!", self::COLOR_GREEN);

                    // نمایش خلاصه اطلاعات (بدون جدول کامل)
                    $this->log("📦 Product: {$productData['title']}", self::COLOR_BLUE);
                    $this->log("💰 Price: {$productData['price']}", self::COLOR_BLUE);
                    $this->log("📊 Available: " . ($productData['availability'] ? 'Yes' : 'No'), self::COLOR_BLUE);

                } else {
                    $failedProducts[] = $url;
                    $this->log("❌ Failed to extract data - productData is null", self::COLOR_RED);
                }

            } catch (\Exception $e) {
                $failedProducts[] = $url;
                $this->log("💥 Exception occurred: " . $e->getMessage(), self::COLOR_RED);
                $this->log("📍 File: " . $e->getFile() . " Line: " . $e->getLine(), self::COLOR_YELLOW);
            }

            // مختصر کردن تاخیر برای تست
            $this->log("⏱️ Applying delay...", self::COLOR_YELLOW);
            $delay = mt_rand(500, 1000); // کاهش تاخیر برای تست سریع‌تر
            usleep($delay * 1000);
            $this->log("✅ Delay completed, continuing...", self::COLOR_GREEN);
        }

        // خلاصه نتایج
        $this->log("", null); // فاصله
        $this->log("📊 Test Results Summary:", self::COLOR_PURPLE);
        $successCount = count($successfulProducts);
        $failCount = count($failedProducts);
        $totalCount = count($productUrls);

        $this->log("  ✅ Successful: $successCount", self::COLOR_GREEN);
        $this->log("  ❌ Failed: $failCount", self::COLOR_RED);

        if ($totalCount > 0) {
            $successRate = round(($successCount / $totalCount) * 100, 2);
            $this->log("  📈 Success Rate: {$successRate}%", self::COLOR_BLUE);
        }

        // نمایش URL های شکست خورده
        if (!empty($failedProducts)) {
            $this->log("", null);
            $this->log("💀 Failed URLs:", self::COLOR_RED);
            foreach ($failedProducts as $failedUrl) {
                $this->log("  - $failedUrl", self::COLOR_YELLOW);
            }
        }

        $this->log("🏁 Product Test Mode completed!", self::COLOR_GREEN);

        return [
            'status' => 'success',
            'test_mode' => true,
            'total_tested' => $totalCount,
            'total_products' => $successCount,
            'failed_links' => $failCount,
            'success_rate' => $totalCount > 0 ? round(($successCount / $totalCount) * 100, 2) : 0,
            'products' => $successfulProducts,
            'failed_urls' => $failedProducts
        ];
    }
}
