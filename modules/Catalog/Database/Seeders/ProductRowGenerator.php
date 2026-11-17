<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Carbon\CarbonImmutable;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Builds realistic, varied product rows for search practice.
 *
 * Row n is always the same for the same n (seeded randomness), so a
 * product's data can be reproduced and seeding can resume safely.
 *
 * Text is built from word lists, so names and descriptions share
 * vocabulary across products: useful for relevance, fuzzy matching,
 * synonyms, facets (brand, category, color, tags) and range filters
 * (price, rating, stock, dates).
 */
final class ProductRowGenerator
{
    /**
     * category => [nouns, tags, min price, max price]
     *
     * @var array<string, array{0: non-empty-list<string>, 1: non-empty-list<string>, 2: int, 3: int}>
     */
    private const array CATEGORIES = [
        'Men Clothing' => [['T-Shirt', 'Polo Shirt', 'Panjabi', 'Jeans', 'Chino Pants', 'Hoodie', 'Jacket', 'Formal Shirt'], ['cotton', 'casual', 'formal', 'summer', 'winter', 'eid'], 350, 6500],
        'Women Clothing' => [['Saree', 'Kurti', 'Salwar Kameez', 'Dress', 'Tops', 'Abaya', 'Scarf', 'Leggings'], ['silk', 'cotton', 'party', 'casual', 'eid', 'wedding'], 400, 18000],
        'Footwear' => [['Sneakers', 'Running Shoes', 'Sandals', 'Loafers', 'Boots', 'Slippers', 'Formal Shoes'], ['leather', 'sports', 'comfort', 'outdoor', 'casual'], 450, 9500],
        'Mobile Phones' => [['Smartphone', 'Feature Phone', 'Foldable Phone', 'Gaming Phone'], ['5g', '4g', 'android', 'dual-sim', 'fast-charging'], 2500, 185000],
        'Mobile Accessories' => [['Phone Case', 'Screen Protector', 'Charger', 'Power Bank', 'USB-C Cable', 'Earbuds', 'Phone Holder'], ['fast-charging', 'wireless', 'type-c', 'bluetooth', 'portable'], 150, 7500],
        'Laptops' => [['Laptop', 'Gaming Laptop', 'Ultrabook', 'Chromebook', '2-in-1 Laptop'], ['ssd', 'intel', 'amd', 'gaming', 'office', 'lightweight'], 32000, 350000],
        'Computer Accessories' => [['Keyboard', 'Mouse', 'Monitor', 'Webcam', 'Headset', 'Laptop Bag', 'USB Hub'], ['wireless', 'rgb', 'gaming', 'ergonomic', 'office'], 250, 65000],
        'Home Appliances' => [['Refrigerator', 'Washing Machine', 'Microwave Oven', 'Air Conditioner', 'Ceiling Fan', 'Water Purifier', 'Room Heater'], ['inverter', 'energy-saving', 'smart', 'warranty'], 1800, 165000],
        'Kitchen' => [['Rice Cooker', 'Blender', 'Non-Stick Pan', 'Pressure Cooker', 'Electric Kettle', 'Knife Set', 'Dinner Set'], ['non-stick', 'stainless-steel', 'electric', 'gift', 'family'], 300, 25000],
        'Furniture' => [['Sofa', 'Dining Table', 'Office Chair', 'Bookshelf', 'Bed', 'Wardrobe', 'Study Table'], ['wooden', 'modern', 'home-office', 'space-saving'], 2500, 145000],
        'Home Decor' => [['Wall Clock', 'Table Lamp', 'Curtain', 'Bed Sheet', 'Cushion Cover', 'Photo Frame', 'Indoor Plant Pot'], ['handmade', 'modern', 'gift', 'cotton', 'nakshi'], 200, 12000],
        'Beauty' => [['Face Wash', 'Moisturizer', 'Sunscreen', 'Lipstick', 'Shampoo', 'Hair Oil', 'Perfume'], ['organic', 'herbal', 'skin-care', 'hair-care', 'halal'], 120, 9500],
        'Health' => [['Blood Pressure Monitor', 'Thermometer', 'Vitamin Tablets', 'Face Mask', 'Glucometer', 'Massager'], ['digital', 'family', 'daily-use', 'medical'], 150, 15000],
        'Groceries' => [['Basmati Rice', 'Mustard Oil', 'Red Lentils', 'Tea', 'Honey', 'Spice Mix', 'Ghee'], ['organic', 'family-pack', 'premium', 'local'], 60, 3500],
        'Baby & Kids' => [['Baby Diapers', 'Baby Lotion', 'Toy Car', 'Building Blocks', 'School Bag', 'Kids T-Shirt', 'Feeding Bottle'], ['kids', 'safe', 'educational', 'gift'], 100, 9000],
        'Sports & Outdoors' => [['Cricket Bat', 'Football', 'Badminton Racket', 'Yoga Mat', 'Dumbbell Set', 'Bicycle', 'Tent'], ['fitness', 'cricket', 'outdoor', 'training'], 250, 65000],
        'Books & Stationery' => [['Notebook', 'Novel', 'Fountain Pen', 'Story Book', 'Drawing Set', 'Calculator', 'Dictionary'], ['bangla', 'english', 'school', 'office', 'gift'], 40, 4500],
        'Watches' => [['Analog Watch', 'Smart Watch', 'Digital Watch', 'Fitness Band'], ['waterproof', 'leather', 'smart', 'sports', 'gift'], 600, 75000],
        'Bags & Luggage' => [['Backpack', 'Handbag', 'Trolley Bag', 'Wallet', 'Duffel Bag', 'Sling Bag'], ['leather', 'travel', 'waterproof', 'office'], 300, 28000],
        'Automotive' => [['Car Vacuum Cleaner', 'Bike Helmet', 'Car Seat Cover', 'Tyre Inflator', 'Dash Camera'], ['car', 'motorbike', 'safety', 'portable'], 350, 22000],
    ];

    /** Fictional brands. */
    private const array BRANDS = [
        'Nordvik', 'Sonora', 'Padma', 'Meghna', 'Aarong Lane', 'Kazi', 'Zenith', 'Lumora', 'Bengal Craft', 'Orbit',
        'Vertex', 'Nakshi', 'Rupkotha', 'Shapla', 'Novatek', 'Ecoline', 'Urbanfit', 'Kirtonkhola', 'Jamuna Home', 'Pixelon',
        'Tarak', 'Sundarban', 'Halda', 'Brightway', 'Cozmo', 'Dhaka Loom', 'Karnaphuli', 'Silverleaf', 'Aurix', 'Bolaka',
        'Mayur', 'Neel', 'Gaangchil', 'Prothom', 'Swapno', 'Techvia', 'Kantha Co', 'Ranger', 'Polash', 'Sylhet Tea House',
    ];

    private const array ADJECTIVES = [
        'Premium', 'Classic', 'Modern', 'Lightweight', 'Durable', 'Smart', 'Compact', 'Deluxe', 'Eco', 'Pro',
        'Ultra', 'Elegant', 'Comfort', 'Essential', 'Handmade', 'Vintage', 'Slim', 'Heavy Duty', 'Portable', 'Signature',
    ];

    private const array MATERIALS = [
        'Cotton', 'Silk', 'Leather', 'Steel', 'Wooden', 'Bamboo', 'Ceramic', 'Aluminium', 'Linen', 'Jute', 'Plastic', 'Glass',
    ];

    private const array COLORS = [
        'Black', 'White', 'Red', 'Blue', 'Navy', 'Green', 'Olive', 'Maroon', 'Grey', 'Silver', 'Gold', 'Brown', 'Beige', 'Pink', 'Purple', 'Orange',
    ];

    private const array SENTENCES = [
        'Made from high quality {material} for everyday use.',
        'A {adjective} {noun} from {brand}, designed for {tag} lovers.',
        'Perfect for home, office and travel.',
        'Comes with a {warranty}-month official warranty.',
        'Available in {color}; easy to clean and maintain.',
        'Popular choice in Dhaka, Chattogram and Sylhet.',
        'Great value for money with fast delivery across Bangladesh.',
        'Ideal as a gift for Eid, weddings and birthdays.',
        'Rated highly by customers for comfort and finish.',
        'Limited stock: order today for cash on delivery.',
        'Tested for long life and consistent performance.',
        'Pairs well with other {category} items from {brand}.',
    ];

    /** Products are spread over three years before this moment (Unix time, UTC). */
    private readonly int $latest;

    public function __construct(?CarbonImmutable $latest = null)
    {
        $this->latest = ($latest ?? CarbonImmutable::parse('2026-11-01 00:00:00', 'UTC'))->getTimestamp();
    }

    /**
     * One products row, ready for a bulk INSERT.
     *
     * @param  int  $n  1-based product number
     * @param  int  $total  number of products in the run (spreads created_at)
     * @return array<string, int|string|bool>
     */
    public function row(int $n, int $total): array
    {
        $random = new Randomizer(new Mt19937($n));

        $category = $this->pick($random, array_keys(self::CATEGORIES));
        [$nouns, $categoryTags, $minPrice, $maxPrice] = self::CATEGORIES[$category];

        $brand = $this->pick($random, self::BRANDS);
        $noun = $this->pick($random, $nouns);
        $adjective = $this->pick($random, self::ADJECTIVES);
        $material = $this->pick($random, self::MATERIALS);
        $color = $this->pick($random, self::COLORS);
        $model = strtoupper(substr($brand, 0, 2)).'-'.$random->getInt(100, 9999);

        $tags = $this->tags($random, $categoryTags, $color, $material);

        $replacements = [
            '{material}' => strtolower($material),
            '{adjective}' => strtolower($adjective),
            '{noun}' => strtolower($noun),
            '{brand}' => $brand,
            '{tag}' => $tags[0],
            '{warranty}' => (string) $this->pick($random, [3, 6, 12, 24]),
            '{color}' => strtolower($color),
            '{category}' => strtolower($category),
        ];

        $sentences = [];
        foreach ($random->pickArrayKeys(self::SENTENCES, 3) as $key) {
            $sentences[] = strtr(self::SENTENCES[$key], $replacements);
        }

        // Older products have lower ids: created_at grows with n (keeps inserts in index order).
        // Plain integers and gmdate(): Carbon per row would add minutes over 10M rows.
        $daysAgo = (int) floor((1 - $n / max($total, 1)) * 1095);
        $createdAt = $this->latest - $daysAgo * 86_400 - $random->getInt(0, 86_399);
        $updatedAt = $createdAt + $random->getInt(0, 30) * 86_400;

        // Prices end in 0 or 9, like real shops.
        $price = $random->getInt($minPrice, $maxPrice);
        $price = $price - ($price % 10) + $this->pick($random, [0, 9]);

        $rating = $random->getInt(10, 50) / 10;

        return [
            'sku' => 'SKU-'.str_pad((string) $n, 10, '0', STR_PAD_LEFT),
            'name' => "{$brand} {$adjective} {$material} {$noun} {$model}",
            'description' => implode(' ', $sentences),
            'brand' => $brand,
            'category' => $category,
            'color' => $color,
            'tags' => (string) json_encode($tags),
            'price' => number_format($price, 2, '.', ''),
            'currency' => 'BDT',
            'stock' => $random->getInt(0, 20) === 0 ? 0 : $random->getInt(1, 500),
            'rating' => number_format($rating, 1, '.', ''),
            'reviews_count' => $random->getInt(0, 2500),
            'image' => "https://picsum.photos/seed/urban-nest-{$n}/640/640",
            'is_active' => $random->getInt(1, 100) > 3,
            'created_at' => gmdate('Y-m-d H:i:s', $createdAt),
            'updated_at' => gmdate('Y-m-d H:i:s', $updatedAt),
        ];
    }

    /**
     * @param  non-empty-list<string>  $categoryTags
     * @return list<string>
     */
    private function tags(Randomizer $random, array $categoryTags, string $color, string $material): array
    {
        $tags = [];
        foreach ($random->pickArrayKeys($categoryTags, min(3, count($categoryTags))) as $key) {
            $tags[] = $categoryTags[$key];
        }

        $tags[] = strtolower($color);
        $tags[] = strtolower($material);

        return array_values(array_unique($tags));
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    private function pick(Randomizer $random, array $items): mixed
    {
        return $items[$random->getInt(0, count($items) - 1)];
    }
}
