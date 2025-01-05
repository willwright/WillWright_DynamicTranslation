<?php

namespace Mecodeninja\DynamicTranslation\Model\Js;

use Magento\Translation\Model\Js\DataProviderInterface;

/**
 * DataProvider for js translation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider implements DataProviderInterface
{
    private \Magento\Framework\TranslateInterface $translate;

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
     */
    public function getData($themePath)
    {
        $dictionary = [];

        $allTranslations = $this->translate->getData();
        foreach ($allTranslations as $key => $translation) {
            if (is_array($translation) && count($translation) > 1) {
                if (in_array('dynamic', $translation)) {
                    $dictionary[$key] = $translation[0];
                }
            }
        }
        ksort($dictionary);

        return $dictionary;
    }
}
