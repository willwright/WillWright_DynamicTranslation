<?php

namespace WillWright\Translation\Model\Js;

use Magento\Framework\Exception\LocalizedException;
use Magento\Translation\Model\Js\Config;
use Magento\Translation\Model\Js\DataProviderInterface;

/**
 * DataProvider for js translation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider implements DataProviderInterface
{
    private $translate;

    public function __construct(
        \Magento\Framework\TranslateInterface $translate,
    ) {
        $this->translate = $translate;
    }

    /**
     * Get translation data
     *
     * @param string $themePath
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getData($themePath)
    {
        $dictionary = [];

        $allTranslations = $this->translate->getData();
        foreach ($allTranslations as $translation) {
            if (count($translation) > 2) {
                if (in_array('dynamic', $translation)) {
                    $dictionary[$translation[0]] = $translation[1];
                }
            }
        }

        ksort($dictionary);

        return $dictionary;
    }
}
