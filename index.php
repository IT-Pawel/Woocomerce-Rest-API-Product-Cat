<?php
$inputFile = 'test.csv';
$outputFile = 'output.csv';


if (($inputHandle = fopen($inputFile, 'r')) !== FALSE) {

    if (($outputHandle = fopen($outputFile, 'w')) !== FALSE) {

        fprintf($outputHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($outputHandle, [
            'Term ID', 'Term Permalink', 'Term Name', 'Term Slug', 'Description', 'Parent ID',
            'Parent Name', 'Parent Slug', 'Count', 'Image URL', 'Image Title', 'Image Caption',
            'Image Description', 'Image Alt Text', 'Image Featured', 'Custom URI'
        ]);

        $headers = fgetcsv($inputHandle, 1000, ';');

        $categories = [];
        $termId = 10000;

        $termInfoId = [];

        print_r($termInfoId);


        while (($data = fgetcsv($inputHandle, 1000, ';')) !== FALSE) {
            $currentLevel = &$categories;
            $parentSlug = '';
            for ($i = 0; $i < count($data); $i++) {
                if (!empty($data[$i])) {
                    $termName = $data[$i];
                    $termSlug = slugify($termName);
                    if($termInfoId[$i]['name'] != $termName){

                        $termInfoId[$i] = [
                            'name' => $termName,
                            'termId' => $termId,
                        ];
                        $termId++;
                    }

                    if (!isset($currentLevel[$termSlug])) {
                        $currentLevel[$termSlug] = [
                            'id' => $termId, 
                            'name' => $termName,
                            'slug' => $termSlug,
                            'parent_slug' => $parentSlug,
                            'parent_id' => $i == 0 ? null : $termInfoId[$i-1]['termId']+1,
                            'children' => []
                        ];
                    }


                    $currentLevel = &$currentLevel[$termSlug]['children'];
                    $parentSlug = $termSlug;

                }
            }
        }

        function saveCategoryTreeToCsv($categories, $outputHandle, $parentId = 0)
        {
            foreach ($categories as $category) {
                $row = [
                    $category['id'], // Term ID
                    'gastroprodukt.staginglab.eu/' . $category['slug'], // Term Permalink
                    $category['name'], // Term Name
                    $category['slug'], // Term Slug
                    '', // Description
                    $category['parent_id'], // Parent ID
                    '', // Parent Name
                    '', // Parent Slug
                    '', // Count
                    '', // Image URL
                    '', // Image Title
                    '', // Image Caption
                    '', // Image Description
                    '', // Image Alt Text
                    '', // Image Featured
                    '' // Custom URI
                ];
                fputcsv($outputHandle, $row);

                if (!empty($category['children'])) {
                    saveCategoryTreeToCsv($category['children'], $outputHandle);
                }
            }
        }

        saveCategoryTreeToCsv($categories, $outputHandle);

        fclose($outputHandle);
    } else {
        echo "Can't open file.\n";
    }
    fclose($inputHandle);
} else {
    echo "Can't open file.\n";
}

function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}
