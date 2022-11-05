<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillWright\Translation\Framework;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Translate library
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Translate extends \Magento\Framework\Translate implements \Magento\Framework\TranslateInterface
{
    const CONFIG_CUSTOM_MODULE_KEY = 'willwright';

    /**
     * @param \Magento\Framework\View\DesignInterface $viewDesign
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     * @param \Magento\Framework\View\FileSystem $viewFileSystem
     * @param \Magento\Framework\Module\ModuleList $moduleList
     * @param \Magento\Framework\Module\Dir\Reader $modulesReader
     * @param \Magento\Framework\App\ScopeResolverInterface $scopeResolver
     * @param \Magento\Framework\Translate\ResourceInterface $translate
     * @param \Magento\Framework\Locale\ResolverInterface $locale
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\File\Csv $csvParser
     * @param \Magento\Framework\App\Language\Dictionary $packDictionary
     * @param DriverInterface|null $fileDriver
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\DesignInterface $viewDesign,
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\Module\Dir\Reader $modulesReader,
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\Translate\ResourceInterface $translate,
        \Magento\Framework\Locale\ResolverInterface $locale,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\App\Language\Dictionary $packDictionary,
        DriverInterface $fileDriver = null
    ) {
        $this->_viewDesign = $viewDesign;
        $this->_cache = $cache;
        $this->_viewFileSystem = $viewFileSystem;
        $this->_moduleList = $moduleList;
        $this->_modulesReader = $modulesReader;
        $this->_scopeResolver = $scopeResolver;
        $this->_translateResource = $translate;
        $this->_locale = $locale;
        $this->_appState = $appState;
        $this->request = $request;
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->_csvParser = $csvParser;
        $this->packDictionary = $packDictionary;
        $this->fileDriver = $fileDriver
            ?? ObjectManager::getInstance()->get(File::class);

        $this->_config = [
            self::CONFIG_AREA_KEY => null,
            self::CONFIG_LOCALE_KEY => null,
            self::CONFIG_SCOPE_KEY => null,
            self::CONFIG_THEME_KEY => null,
            self::CONFIG_MODULE_KEY => null,
        ];
    }

    /**
     * Initialize translation data
     *
     * @param string|null $area
     * @param bool $forceReload
     * @return $this
     */
    public function loadData($area = null, $forceReload = false)
    {
        $this->_data = [];
        if ($area === null) {
            $area = $this->_appState->getAreaCode();
        }
        $this->setConfig(
            [
                self::CONFIG_AREA_KEY => $area,
            ]
        );

        if (!$forceReload) {
            $data = $this->_loadCache();
            if (false !== $data) {
                $this->_data = $data;
                return $this;
            }
        }

        $this->_loadModuleTranslation();
        $this->_loadPackTranslation();
        $this->_loadThemeTranslation();

        if (!$forceReload) {
            $this->_saveCache();
        }

        return $this;
    }

    /**
     * Adding translation data
     *
     * @param array $data
     * @return $this
     */
    protected function _addData($data)
    {
        foreach ($data as $key => $value) {
            if ($key === $value) {
                if (isset($this->_data[$key])) {
                    unset($this->_data[$key]);
                }
                continue;
            }

            $key = is_array($key) ? $key : (string) $key;
            $value = is_array($value) ? $value : (string) $value;
            $key = str_replace('""', '"', $key);
            $value = str_replace('""', '"', $value);

            $this->_data[$key] = $value;
        }
        return $this;
    }

    /**
     * Retrieve data from file
     *
     * @param string $file
     * @return array
     */
    protected function _getFileData($file)
    {
        $data = [];
        if ($this->fileDriver->isExists($file)) {
            $this->_csvParser->setDelimiter(',');
            $data = $this->_csvParser->getData($file);
        }
        return $data;
    }

    /**
     * Loading data cache
     *
     * @return array|bool
     */
    protected function _loadCache()
    {
        $data = $this->_cache->load($this->getCacheId());
        if ($data) {
            $data = $this->getSerializer()->unserialize($data);
        }
        return $data;
    }

    /**
     * Load data from module translation files
     *
     * @return $this
     */
    protected function _loadModuleTranslation()
    {
        $currentModule = $this->getControllerModuleName();
        $allModulesExceptCurrent = array_diff($this->_moduleList->getNames(), [$currentModule]);

        $this->loadModuleTranslationByModulesList($allModulesExceptCurrent);
        $this->loadModuleTranslationByModulesList([$currentModule]);
        return $this;
    }

    /**
     * Load translation dictionary from language packages
     *
     * @return void
     */
    protected function _loadPackTranslation()
    {
        $data = $this->packDictionary->getDictionary($this->getLocale());
        $this->_addData($data);
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return \Magento\Framework\TranslateInterface
     */

    protected function _loadThemeTranslation()
    {
        $themeFiles = $this->getThemeTranslationFilesList($this->getLocale());

        /** @var string $file */
        foreach ($themeFiles as $file) {
            if ($file) {
                $this->_addData($this->_getFileData($file));
            }
        }

        return $this;
    }

    /**
     * Saving data cache
     *
     * @return $this
     */
    protected function _saveCache()
    {
        $this->_cache->save($this->getSerializer()->serialize($this->getData()), $this->getCacheId(), [], false);
        return $this;
    }

    /**
     * Retrieve cache identifier
     *
     * @return string
     */
    protected function getCacheId()
    {
        $_cacheId = \Magento\Framework\App\Cache\Type\Translate::TYPE_IDENTIFIER;
        $_cacheId .= '_' . $this->_config[self::CONFIG_LOCALE_KEY];
        $_cacheId .= '_' . $this->_config[self::CONFIG_AREA_KEY];
        $_cacheId .= '_' . $this->_config[self::CONFIG_SCOPE_KEY];
        $_cacheId .= '_' . $this->_config[self::CONFIG_THEME_KEY];
        $_cacheId .= '_' . $this->_config[self::CONFIG_MODULE_KEY];
        $_cacheId .= '_' . $this->_config[self::CONFIG_CUSTOM_MODULE_KEY];

        $this->_cacheId = $_cacheId;
        return $_cacheId;
    }

    /**
     * Get serializer
     *
     * @return \Magento\Framework\Serialize\SerializerInterface
     * @deprecated 101.0.0
     */
    private function getSerializer()
    {
        if ($this->serializer === null) {
            $this->serializer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(Serialize\SerializerInterface::class);
        }
        return $this->serializer;
    }

    /**
     * Load data from module translation files by list of modules
     *
     * @param array $modules
     * @return $this
     */
    protected function loadModuleTranslationByModulesList(array $modules)
    {
        foreach ($modules as $module) {
            $moduleFilePath = $this->_getModuleTranslationFile($module, $this->getLocale());
            $this->_addData($this->_getFileData($moduleFilePath));
        }
        return $this;
    }

    /**
     * Get parent themes for the current theme in fallback order
     *
     * @return array
     */
    private function getParentThemesList(): array
    {
        $themes = [];

        $parentTheme = $this->_viewDesign->getDesignTheme()->getParentTheme();
        while ($parentTheme) {
            $themes[] = $parentTheme;
            $parentTheme = $parentTheme->getParentTheme();
        }
        $themes = array_reverse($themes);

        return $themes;
    }

    /**
     * Get theme translation locale file name
     *
     * @param string|null $locale
     * @param array $config
     * @return string|null
     */
    private function getThemeTranslationFileName(?string $locale, array $config): ?string
    {
        $fileName = $this->_viewFileSystem->getLocaleFileName(
            'i18n' . '/' . $locale . '.csv',
            $config
        );

        return $fileName ? $fileName : null;
    }

    /**
     * Retrieve translation files for themes according to fallback
     *
     * @param string $locale
     *
     * @return array
     */
    private function getThemeTranslationFilesList($locale): array
    {
        $translationFiles = [];

        /** @var \Magento\Framework\View\Design\ThemeInterface $theme */
        foreach ($this->getParentThemesList() as $theme) {
            $config = $this->_config;
            $config['theme'] = $theme->getCode();
            $translationFiles[] = $this->getThemeTranslationFileName($locale, $config);
        }

        $translationFiles[] = $this->getThemeTranslationFileName($locale, $this->_config);

        return $translationFiles;
    }
}
