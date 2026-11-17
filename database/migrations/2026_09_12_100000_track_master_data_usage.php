<?php

use App\Services\MasterDataUsage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (MasterDataUsage::MASTERS as $class) {
            Schema::table((new $class)->getTable(), function (Blueprint $table) {
                $table->timestamp('used_at')->nullable();
            });
        }
        foreach (MasterDataUsage::MASTERS as $class) {
            $class::chunkById(200, function ($models) use ($class) {
                foreach ($models as $model) {
                    if (MasterDataUsage::isUsed($model)) {
                        $class::whereKey($model->id)->update(['used_at' => now()]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (MasterDataUsage::MASTERS as $class) {
            Schema::table((new $class)->getTable(), fn (Blueprint $table) => $table->dropColumn('used_at'));
        }
    }
};
