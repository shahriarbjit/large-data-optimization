<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Catalog owns the products table. Loaded by CatalogServiceProvider.
 *
 * Indexes are kept few on purpose: the table holds ~10 million rows and
 * full-text search will be done by Elasticsearch, not MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 20)->unique();
            $table->string('name', 200);
            $table->text('description');
            $table->string('brand', 60);
            $table->string('category', 60);
            $table->string('color', 30);
            $table->json('tags');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('image', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('brand');
            // For incremental sync to a search index ("changed since ...").
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
