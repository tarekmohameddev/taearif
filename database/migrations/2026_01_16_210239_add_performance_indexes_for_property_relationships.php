<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds critical indexes identified in performance analysis for GET /api/properties/{id} endpoint.
     * These indexes optimize eager loading queries for property relationships:
     * - user_property_slider_imgs.property_id
     * - user_property_amenities.property_id
     * - user_property_amenities.amenity_id
     * - Composite index for nested eager loading
     *
     * @return void
     */
    public function up()
    {
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return $result[0]->count > 0;
            } catch (\Exception $e) {
                // If table doesn't exist or error, return false
                return false;
            }
        };

        // ===== user_property_slider_imgs table - Index for eager loading =====
        
        // Index on property_id for galleryImages relationship
        if (!$hasIndex('user_property_slider_imgs', 'idx_slider_imgs_property_id')) {
            Schema::table('user_property_slider_imgs', function (Blueprint $table) {
                $table->index('property_id', 'idx_slider_imgs_property_id');
            });
        }

        // ===== user_property_amenities table - Indexes for eager loading with nested relationship =====
        
        // Index on property_id for proertyAmenities relationship
        if (!$hasIndex('user_property_amenities', 'idx_amenities_property_id')) {
            Schema::table('user_property_amenities', function (Blueprint $table) {
                $table->index('property_id', 'idx_amenities_property_id');
            });
        }

        // Index on amenity_id for nested amenity relationship
        if (!$hasIndex('user_property_amenities', 'idx_amenities_amenity_id')) {
            Schema::table('user_property_amenities', function (Blueprint $table) {
                $table->index('amenity_id', 'idx_amenities_amenity_id');
            });
        }

        // Composite index for eager loading with nested relationship (property -> amenities -> amenity)
        if (!$hasIndex('user_property_amenities', 'idx_amenities_property_amenity')) {
            Schema::table('user_property_amenities', function (Blueprint $table) {
                $table->index(['property_id', 'amenity_id'], 'idx_amenities_property_amenity');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('user_property_slider_imgs')) {
            Schema::table('user_property_slider_imgs', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_slider_imgs_property_id');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            });
        }

        if (Schema::hasTable('user_property_amenities')) {
            Schema::table('user_property_amenities', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_amenities_property_id');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
                
                try {
                    $table->dropIndex('idx_amenities_amenity_id');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
                
                try {
                    $table->dropIndex('idx_amenities_property_amenity');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            });
        }
    }
};
