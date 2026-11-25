ALTER TABLE `products` 
ADD COLUMN `child_category_id` bigint(20) UNSIGNED NULL DEFAULT NULL AFTER `subcategory_id`;

ALTER TABLE `products`
ADD CONSTRAINT `products_child_category_id_foreign` 
FOREIGN KEY (`child_category_id`) REFERENCES `child_categories` (`id`) ON DELETE SET NULL;

