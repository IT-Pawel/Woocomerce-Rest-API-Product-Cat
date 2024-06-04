<?php
require 'vendor/autoload.php';

use Automattic\WooCommerce\Client;


$woocommerce = new Client(
    'URL', 
    'consumerSecret',
    'consumerKey', 
    [
        'version' => 'wc/v3',
        'verify_ssl' => false
    ]
);

function utf8_fopen_read($fileName) {
    $handle = fopen($fileName, "r");
    return $handle;
}

$inputFile = 'output.csv';

if (($inputHandle = utf8_fopen_read($inputFile)) !== FALSE) {
    $headers = fgetcsv($inputHandle, 1000, ',');

    $termIdMap = [];

    while (($data = fgetcsv($inputHandle, 1000, ',')) !== FALSE) {
        $termId = $data[0];
        $termName = $data[2];
        $termSlug = $data[3];
        $parentId = !empty($data[5]) ? $data[5] : null;

        $termIdMap[$termId] = [
            'name' => $termName,
            'slug' => $termSlug,
            'parent_id' => $parentId,
            'wc_id' => null,
            'parent_wc_id' => null
        ];
    }
    
    $termIdMap = map_term_id_to_wc_id($woocommerce, $termIdMap);

    fclose($inputHandle);
} else {
    echo "Cannot open CSV file.\n";
}

function map_term_id_to_wc_id($woocommerce, $termIdMap) {
    foreach ($termIdMap as $termId => $termData) {
        $parent_wc_id = null;
        if ($termData['parent_id'] !== null) {
            $parent_wc_id = $termIdMap[$termData['parent_id']]['wc_id'];
        }

        $categoryData = [
            'name' => $termData['name'],
            'slug' => $termData['slug'],
            'parent' => $parent_wc_id
        ];

        try {
            $response = $woocommerce->post('products/categories', $categoryData);
            $termIdMap[$termId]['wc_id'] = $response->id;
            echo "Created category: {$termData['name']} with parent ID {$parent_wc_id}\n";
        } catch (Exception $e) {
            echo "Error creating category: ", $e->getMessage(), "\n";
        }
    }

    return $termIdMap;
}
?>
