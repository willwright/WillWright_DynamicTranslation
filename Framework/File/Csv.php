<?php

namespace Mecodeninja\DynamicTranslation\Framework\File;

class Csv extends \Magento\Framework\File\Csv
{
    /**
     * Retrieve CSV file data as pairs
     *
     * @param string $file
     * @return array
     * @throws \Exception
     */
    public function getDataPairsExtended($file)
    {
        $data = [];
        $csvData = $this->getData($file);
        foreach ($csvData as $rowData) {
            if (isset($rowData[0])) {
                $data[array_shift($rowData)] = $rowData;
            }
        }
        return $data;
    }
}
