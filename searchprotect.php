<?php
/**
 * SearchProtect - PrestaShop Module
 * Protects the search endpoint from DoS/DDoS attacks via malformed or oversized queries.
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com - Arte e Informatica di Loris Modena e c. s.a.s.
 * @license   MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class SearchProtect extends Module
{
    private const DEFAULT_MAX_LENGTH = 100;
    private const DEFAULT_MAX_AMPS = 5;
    private const DEFAULT_MAX_PAGES = 3;
    private const DEFAULT_BLOCK_DURATION = 3600;
    private const DEFAULT_LOG_ENABLED = true;
    private const CACHE_KEY_PREFIX = 'SP_BLOCK_';

    public function __construct()
    {
        $this->name = 'searchprotect';
        $this->tab = 'others';
        $this->version = '1.0.3';
        $this->author = 'Tecnoacquisti.com';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Search Protect');
        $this->description = $this->l(
            'Blocks malformed or oversized search queries to prevent DoS attacks on MariaDB.'
        );
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    // -------------------------------------------------------------------------
    // INSTALL / UNINSTALL
    // -------------------------------------------------------------------------

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionFrontControllerInitBefore')
            && $this->installConfig()
            && $this->installDb();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallConfig()
            && $this->uninstallDb();
    }

    private function installConfig()
    {
        Configuration::updateValue('SP_MAX_LENGTH', self::DEFAULT_MAX_LENGTH);
        Configuration::updateValue('SP_MAX_AMPS', self::DEFAULT_MAX_AMPS);
        Configuration::updateValue('SP_MAX_PAGES', self::DEFAULT_MAX_PAGES);
        Configuration::updateValue('SP_BLOCK_DURATION', self::DEFAULT_BLOCK_DURATION);
        Configuration::updateValue('SP_LOG_ENABLED', self::DEFAULT_LOG_ENABLED);

        return true;
    }

    private function uninstallConfig()
    {
        $keys = ['SP_MAX_LENGTH', 'SP_MAX_AMPS', 'SP_MAX_PAGES', 'SP_BLOCK_DURATION', 'SP_LOG_ENABLED'];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    private function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'searchprotect_log` (
            `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip`         VARCHAR(45) NOT NULL,
            `query`      TEXT NOT NULL,
            `reason`     VARCHAR(100) NOT NULL,
            `blocked_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_ip` (`ip`),
            KEY `idx_blocked_at` (`blocked_at`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'searchprotect_log`'
        );
    }

    // -------------------------------------------------------------------------
    // HOOK: actionFrontControllerInitBefore
    // Fires on every front-office request before the controller is dispatched.
    // -------------------------------------------------------------------------

    public function hookActionFrontControllerInitBefore(array $params)
    {
        $controller = Tools::getValue('controller');
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        $thirdPartyPaths = [
            '/module/iqitsearch/',
            '/module/searchbar/',
            '/module/elasticsearch/',
            '/module/nrtSearch/',
            '/module/sph_search/',
        ];

        $isThirdParty = false;

        foreach ($thirdPartyPaths as $path) {
            if (strpos($uri, $path) !== false) {
                $isThirdParty = true;
                break;
            }
        }

        $isSearch = ($controller === 'search')
            || $isThirdParty
            || (strpos($uri, '/busqueda') !== false)
            || (strpos($uri, '/recherche') !== false)
            || (strpos($uri, '/search') !== false)
            || (strpos($uri, '/ricerca') !== false);

        if (!$isSearch) {
            return;
        }

        $ip = $this->getClientIp();
        $query = Tools::getValue('s', Tools::getValue('search_query', Tools::getValue('q', '')));

        if ($this->isBlocked($ip)) {
            $this->denyRequest('ip_blocked');
        }

        $reason = $this->validateQuery($query);

        if ($reason !== null) {
            $this->blockIp($ip);
            $this->logAttempt($ip, $query, $reason);
            $this->denyRequest($reason);
        }
    }

    // -------------------------------------------------------------------------
    // VALIDATION RULES
    // -------------------------------------------------------------------------

    /**
     * Returns a reason string when the query is malicious, null when it is clean.
     *
     * @param string $query Raw value of the search parameter
     *
     * @return string|null
     */
    private function validateQuery($query)
    {
        $maxLen = (int) Configuration::get('SP_MAX_LENGTH');
        $maxAmps = (int) Configuration::get('SP_MAX_AMPS');
        $maxPages = (int) Configuration::get('SP_MAX_PAGES');

        // Rule 1: raw length guard
        if (mb_strlen($query) > $maxLen) {
            return 'query_too_long';
        }

        // Rule 2: encoded &amp; chain flood (%26amp%3B repeated)
        $decoded = urldecode(urldecode($query));
        $ampCount = substr_count($decoded, '&amp;') + substr_count($decoded, '&amp');

        if ($ampCount > $maxAmps) {
            return 'amp_flood';
        }

        // Rule 3: repeated ?page= injections inside the query value
        $pageCount = preg_match_all('/[?&]page=/i', $decoded, $matches);

        if ($pageCount > $maxPages) {
            return 'page_injection';
        }

        // Rule 4: full query string length guard
        $rawQs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        if (strlen($rawQs) > 2000) {
            return 'querystring_too_long';
        }

        // Rule 5: nested percent-encoding abuse (%25 chains)
        if (preg_match('/(%25){5,}/i', $rawQs)) {
            return 'encoding_abuse';
        }

        // Rule 6: IQITSearch pattern — amp-flooded s= combined with a separate &page= param
        $pageParam = (int) Tools::getValue('page', 0);

        if ($pageParam > 0) {
            $decodedOnce = urldecode($query);

            if (substr_count($decodedOnce, '&amp') > 2) {
                return 'iqit_amp_page_combo';
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // IP BLOCKING
    // PS CacheCore::store() accepts only 2 params; we store the expiry timestamp
    // as the value and compare it to time() on retrieval.
    // File-based JSON serves as fallback for environments without PS cache.
    // -------------------------------------------------------------------------

    private function getCacheKey($ip)
    {
        return self::CACHE_KEY_PREFIX . md5($ip);
    }

    private function isBlocked($ip)
    {
        $key = $this->getCacheKey($ip);

        if (Cache::isStored($key)) {
            $expires = (int) Cache::retrieve($key);

            if ($expires > time()) {
                return true;
            }
        }

        return $this->isBlockedInFile($ip);
    }

    private function blockIp($ip)
    {
        $duration = (int) Configuration::get('SP_BLOCK_DURATION');
        Cache::store($this->getCacheKey($ip), time() + $duration);
        $this->blockInFile($ip, $duration);
    }

    private function blockFilePath()
    {
        return _PS_ROOT_DIR_ . '/var/logs/searchprotect_blocks.json';
    }

    private function isBlockedInFile($ip)
    {
        $file = $this->blockFilePath();

        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(file_get_contents($file), true);

        return is_array($data) && isset($data[$ip]) && $data[$ip] > time();
    }

    private function blockInFile($ip, $duration)
    {
        $file = $this->blockFilePath();
        $data = [];

        if (file_exists($file)) {
            $raw = json_decode(file_get_contents($file), true);
            $data = is_array($raw) ? $raw : [];
        }

        $now = time();

        $data = array_filter($data, function ($exp) use ($now) {
            return $exp > $now;
        });

        $data[$ip] = $now + $duration;
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    // -------------------------------------------------------------------------
    // DENY & LOG
    // -------------------------------------------------------------------------

    private function denyRequest($reason)
    {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: ' . (int) Configuration::get('SP_BLOCK_DURATION'));
        header('Content-Type: text/plain; charset=utf-8');
        echo '429 - Request blocked by SearchProtect (' . htmlspecialchars($reason) . ')';
        exit;
    }

    private function logAttempt($ip, $query, $reason)
    {
        if (!(bool) Configuration::get('SP_LOG_ENABLED')) {
            return;
        }

        Db::getInstance()->insert('searchprotect_log', [
            'ip' => pSQL($ip),
            'query' => pSQL(mb_substr($query, 0, 500)),
            'reason' => pSQL($reason),
            'blocked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // -------------------------------------------------------------------------
    // UTILITIES
    // -------------------------------------------------------------------------

    private function getClientIp()
    {
        $ip = Tools::getRemoteAddr();

        return $ip ?: (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0');
    }

    // -------------------------------------------------------------------------
    // BACK-OFFICE CONFIGURATION
    // -------------------------------------------------------------------------

    public function getContent()
    {
        $output = '';

        $useSsl = (bool) Configuration::get('PS_SSL_ENABLED_EVERYWHERE') || (bool) Configuration::get('PS_SSL_ENABLED');
        $this->context->smarty->assign('shop_base_url', $this->context->link->getBaseLink((int) $this->context->shop->id, $useSsl));

        if (Tools::isSubmit('submit_searchprotect')) {
            $output .= $this->postProcess();
        }

        $output .= $this->renderConfigForm();
        $output .= $this->renderLogTable();
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/copyright.tpl');

        return $output;
    }

    private function postProcess()
    {
        $errors = [];
        $maxLen = (int) Tools::getValue('SP_MAX_LENGTH');
        $maxAmps = (int) Tools::getValue('SP_MAX_AMPS');
        $maxPages = (int) Tools::getValue('SP_MAX_PAGES');
        $blockDur = (int) Tools::getValue('SP_BLOCK_DURATION');
        $logOn = (bool) Tools::getValue('SP_LOG_ENABLED');

        if ($maxLen < 10 || $maxLen > 2000) {
            $errors[] = $this->l('Max query length must be between 10 and 2000.');
        }

        if ($maxAmps < 1 || $maxAmps > 100) {
            $errors[] = $this->l('Max &amp; count must be between 1 and 100.');
        }

        if ($maxPages < 1 || $maxPages > 50) {
            $errors[] = $this->l('Max ?page= count must be between 1 and 50.');
        }

        if ($blockDur < 60 || $blockDur > 86400) {
            $errors[] = $this->l('Block duration must be between 60 and 86400 seconds.');
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        Configuration::updateValue('SP_MAX_LENGTH', $maxLen);
        Configuration::updateValue('SP_MAX_AMPS', $maxAmps);
        Configuration::updateValue('SP_MAX_PAGES', $maxPages);
        Configuration::updateValue('SP_BLOCK_DURATION', $blockDur);
        Configuration::updateValue('SP_LOG_ENABLED', $logOn);

        return $this->displayConfirmation($this->l('Settings saved.'));
    }

    private function renderConfigForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Protection Rules'),
                    'icon' => 'icon-shield',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Max query length (chars)'),
                        'name' => 'SP_MAX_LENGTH',
                        'required' => true,
                        'desc' => $this->l('Queries longer than this will be blocked. Recommended: 100.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Max &amp; occurrences'),
                        'name' => 'SP_MAX_AMPS',
                        'required' => true,
                        'desc' => $this->l('Blocks queries with repeated &amp; encoding (DoS pattern). Recommended: 5.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Max ?page= occurrences'),
                        'name' => 'SP_MAX_PAGES',
                        'required' => true,
                        'desc' => $this->l('Blocks repeated ?page= injection. Recommended: 3.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('IP block duration (seconds)'),
                        'name' => 'SP_BLOCK_DURATION',
                        'required' => true,
                        'desc' => $this->l('How long to block an offending IP. Default: 3600 (1 hour).'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable logging'),
                        'name' => 'SP_LOG_ENABLED',
                        'values' => [
                            ['id' => 'log_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'log_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submit_searchprotect';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->fields_value['SP_MAX_LENGTH'] = Configuration::get('SP_MAX_LENGTH');
        $helper->fields_value['SP_MAX_AMPS'] = Configuration::get('SP_MAX_AMPS');
        $helper->fields_value['SP_MAX_PAGES'] = Configuration::get('SP_MAX_PAGES');
        $helper->fields_value['SP_BLOCK_DURATION'] = Configuration::get('SP_BLOCK_DURATION');
        $helper->fields_value['SP_LOG_ENABLED'] = Configuration::get('SP_LOG_ENABLED');

        return $helper->generateForm([$fieldsForm]);
    }

    private function renderLogTable()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'searchprotect_log`
             ORDER BY blocked_at DESC
             LIMIT 100'
        );

        $this->context->smarty->assign([
            'sp_rows' => $rows,
            'sp_label_title' => $this->l('Last 100 blocked requests'),
            'sp_label_empty' => $this->l('No attacks logged yet.'),
            'sp_label_date' => $this->l('Date'),
            'sp_label_ip' => $this->l('IP'),
            'sp_label_reason' => $this->l('Reason'),
            'sp_label_query' => $this->l('Query (truncated)'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/logs.tpl');
    }
}
