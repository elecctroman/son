ALTER TABLE products
    ADD COLUMN slug VARCHAR(191) NOT NULL AFTER name,
    ADD UNIQUE KEY idx_products_slug (slug);

UPDATE products
SET slug = LOWER(
        REGEXP_REPLACE(
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                REPLACE(REPLACE(REPLACE(name,
                    'Ç', 'c'), 'ç', 'c'),
                    'Ğ', 'g'), 'ğ', 'g'),
                    'İ', 'i'), 'I', 'i'), 'ı', 'i'),
                    'Ö', 'o'), 'ö', 'o'),
                    'Ş', 's'), 'ş', 's'),
                    'Ü', 'u'), 'ü', 'u'
            ),
            '[^a-z0-9]+',
            '-'
        )
    )
WHERE slug IS NULL OR slug = '';

UPDATE products p
JOIN (
    SELECT slug, id,
           ROW_NUMBER() OVER (PARTITION BY slug ORDER BY id) AS duplicate_rank
    FROM products
) dup ON dup.id = p.id
SET p.slug = CONCAT(p.slug, '-', p.id)
WHERE dup.duplicate_rank > 1;
