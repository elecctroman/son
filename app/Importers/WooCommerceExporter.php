<?php

namespace App\Importers;

use App\Helpers;
use App\Database;
use PDO;

class WooCommerceExporter
{
    /**
     * @param PDO $pdo
     * @return void
     */
    public static function stream(PDO $pdo)
    {
        $filename = 'woocommerce-products-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if (!$output) {
            exit;
        }

        // Add UTF-8 BOM for better spreadsheet compatibility
        fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headers = self::headers();
        fputcsv($output, $headers);

        $rows = self::rows($pdo);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * @return array<int,string>
     */
    private static function headers()
    {
        return array(
            'ID',
            'Type',
            'SKU',
            'Name',
            'Published',
            'Is featured?',
            'Visibility in catalog',
            'Short description',
            'Description',
            'Date sale price starts',
            'Date sale price ends',
            'Tax status',
            'Tax class',
            'In stock?',
            'Stock',
            'Low stock amount',
            'Backorders allowed?',
            'Sold individually?',
            'Weight (kg)',
            'Length (cm)',
            'Width (cm)',
            'Height (cm)',
            'Allow customer reviews?',
            'Purchase note',
            'Sale price',
            'Regular price',
            'Categories',
            'Tags',
            'Shipping class',
            'Images',
            'Download limit',
            'Download expiry days',
            'Parent',
            'Grouped products',
            'Upsells',
            'Cross-sells',
            'External URL',
            'Button text',
            'Position',
            'Meta: _wpcom_is_markdown'
        );
    }

    /**
     * @param PDO $pdo
     * @return array<int,array<int,string>>
     */
    private static function rows(PDO $pdo)
    {
        $categories = $pdo->query('SELECT id, parent_id, name FROM categories')->fetchAll(PDO::FETCH_ASSOC);
        $paths = self::buildCategoryPaths($categories);

        \ = Database::tableHasColumn('products', 'short_description');\n        \ = Database::tableHasColumn('products', 'image_url');\n\n        \ = array('id', 'category_id', 'name', 'sku', 'description', 'price', 'cost_price_try', 'status');\n        \[] = \ ? 'short_description' : 'NULL AS short_description';\n        \[] = \ ? 'image_url' : 'NULL AS image_url';\n\n        \ = \->query('SELECT ' . implode(', ', \) . ' FROM products ORDER BY id ASC');

        $rows = array();
        while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $description = isset($product['description']) ? (string)$product['description'] : '';
            if (trim($description) === '') {
                $description = Helpers::defaultProductDescription();
            }

            $description = self::normalizeText($description);
            $shortSource = isset($product['short_description']) ? (string)$product['short_description'] : '';
            if ($shortSource === '') {
                $shortSource = Helpers::truncate($description, 180);
            }
            $shortDescription = self::normalizeText($shortSource);
            $imageUrl = isset($product['image_url']) ? (string)$product['image_url'] : '';

            $status = isset($product['status']) ? (string)$product['status'] : 'inactive';
            $published = $status === 'active' ? '1' : '0';

            $categoryId = isset($product['category_id']) ? (int)$product['category_id'] : 0;
            $categoryPath = isset($paths[$categoryId]) ? $paths[$categoryId] : 'Uncategorized';

            if (isset($product['cost_price_try']) && $product['cost_price_try'] !== null) {
                $costTry = (float)$product['cost_price_try'];
            } else {
                $costTry = Helpers::costTryFromSalePrice(isset($product['price']) ? (float)$product['price'] : 0.0);
            }

            $priceFormatted = number_format($costTry, 2, '.', '');

            $rows[] = array(
                '', // ID
                'simple',
                isset($product['sku']) ? (string)$product['sku'] : '',
                isset($product['name']) ? (string)$product['name'] : '',
                $published,
                '0',
                'visible',
                $shortDescription,
                $description,
                $imageUrl,
                '',
                'taxable',
                '',
                '1',
                '',
                '',
                '0',
                '0',
                '',
                '',
                '',
                '',
                '1',
                '',
                '',
                $priceFormatted,
                $categoryPath,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            );
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $categories
     * @return array<int,string>
     */
    private static function buildCategoryPaths($categories)
    {
        $paths = array();
        $byId = array();

        foreach ($categories as $category) {
            $id = isset($category['id']) ? (int)$category['id'] : 0;
            if ($id > 0) {
                $byId[$id] = array(
                    'id' => $id,
                    'parent_id' => isset($category['parent_id']) ? (int)$category['parent_id'] : null,
                    'name' => isset($category['name']) ? (string)$category['name'] : ''
                );
            }
        }

        foreach ($byId as $id => $category) {
            $paths[$id] = self::resolveCategoryPath($id, $byId);
        }

        return $paths;
    }

    /**
     * @param int $id
     * @param array<int,array<string,mixed>> $categories
     * @return string
     */
    private static function resolveCategoryPath($id, $categories)
    {
        $names = array();
        $currentId = $id;
        $guard = 0;

        while ($currentId && isset($categories[$currentId]) && $guard < 50) {
            $category = $categories[$currentId];
            $name = isset($category['name']) ? (string)$category['name'] : '';
            if ($name !== '') {
                array_unshift($names, $name);
            }
            $currentId = isset($category['parent_id']) ? (int)$category['parent_id'] : 0;
            $guard++;
        }

        if (!$names) {
            return 'Uncategorized';
        }

        return implode(' > ', $names);
    }

    /**
     * @param string $value
     * @return string
     */
    private static function normalizeText($value)
    {
        $value = (string)$value;
        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            $value = '';
        }

        return trim($value);
    }
}

