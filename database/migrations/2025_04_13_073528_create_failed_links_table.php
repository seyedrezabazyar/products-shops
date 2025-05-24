<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedLinksTable extends Migration
{
    public function up()
    {
        Schema::create('failed_links', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique(); // URL منحصر به فرد
            $table->integer('attempts')->default(0); // تعداد تلاش‌ها
            $table->text('error_message')->nullable(); // پیام خطا
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('failed_links');
    }
}
