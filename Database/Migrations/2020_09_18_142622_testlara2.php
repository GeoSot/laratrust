<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LaratrustSetupTables extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->createMainTables();
        $this->createRoleUserTable();
        $this->createPermissionUserTable();
        $this->createPermissionRoleTable();

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('laratrust.tables.permission_user'));
        Schema::dropIfExists(config('laratrust.tables.permission_role'));
        Schema::dropIfExists(config('laratrust.tables.permissions'));
        Schema::dropIfExists(config('laratrust.tables.role_user'));
        Schema::dropIfExists(config('laratrust.tables.roles'));

        if ($this->IsTeamsEnabled()) {
            Schema::dropIfExists(config('laratrust.tables.teams'));
        }
    }

    private function createMainTables(): void
    {
        // Create table for storing roles
        Schema::create(config('laratrust.tables.roles'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create table for storing permissions
        Schema::create(config('laratrust.tables.permissions'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        if ($this->IsTeamsEnabled()) // Create table for storing teams
        {
            Schema::create(config('laratrust.tables.teams'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('display_name')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createRoleUserTable(): void
    {
        // Create table for associating roles to users and teams (Many To Many Polymorphic)
        Schema::create(config('laratrust.tables.role_user'), function (Blueprint $table) {
            $table->unsignedBigInteger(config('laratrust.foreign_keys.role'));
            $table->unsignedBigInteger(config('laratrust.foreign_keys.user'));
            $table->string('user_type');
            if ($this->IsTeamsEnabled()) {
                $table->unsignedBigInteger(config('laratrust.foreign_keys.team'))->nullable();
            }

            $table->foreign(config('laratrust.foreign_keys.role'))->references('id')->on(config('laratrust.tables.roles'))
                ->onUpdate('cascade')->onDelete('cascade');


            if ($this->IsTeamsEnabled()) {

                $table->foreign(config('laratrust.foreign_keys.team'))->references('id')->on(config('laratrust.tables.teams'))
                    ->onUpdate('cascade')->onDelete('cascade');
                $table->unique([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.role'), 'user_type', config('laratrust.foreign_keys.team')]);

            } else {

                $table->primary([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.role'), 'user_type']);

            }
        });
    }

    private function createPermissionUserTable(): void
    {
        // Create table for associating permissions to users (Many To Many Polymorphic)
        Schema::create(config('laratrust.tables.permission_user'), function (Blueprint $table) {
            $table->unsignedBigInteger(config('laratrust.foreign_keys.permission'));
            $table->unsignedBigInteger(config('laratrust.foreign_keys.user'));
            $table->string('user_type');

            if ($this->IsTeamsEnabled()) {
                $table->unsignedBigInteger(config('laratrust.foreign_keys.team'))->nullable();
            }



            $table->foreign(config('laratrust.foreign_keys.permission'))->references('id')->on(config('laratrust.tables.permissions'))
                ->onUpdate('cascade')->onDelete('cascade');

            if ($this->IsTeamsEnabled()) {

                $table->foreign(config('laratrust.foreign_keys.team'))->references('id')->on(config('laratrust.tables.teams'))
                    ->onUpdate('cascade')->onDelete('cascade');

                $table->unique([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.permission'), 'user_type', config('laratrust.foreign_keys.team')]);

            } else {

                $table->primary([config('laratrust.foreign_keys.user'), config('laratrust.foreign_keys.permission'), 'user_type']);
            }
        });
    }

    private function createPermissionRoleTable(): void
    {
        // Create table for associating permissions to roles (Many-to-Many)
        Schema::create(config('laratrust.tables.permission_role'), function (Blueprint $table) {
            $table->unsignedBigInteger(config('laratrust.foreign_keys.permission'));
            $table->unsignedBigInteger(config('laratrust.foreign_keys.role'));

            $table->foreign(config('laratrust.foreign_keys.permission'))->references('id')->on(config('laratrust.tables.permissions'))
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(config('laratrust.foreign_keys.role'))->references('id')->on(config('laratrust.tables.roles'))
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary([config('laratrust.foreign_keys.permission'), config('laratrust.foreign_keys.role')]);
        });
    }

    /**
     * @return bool
     */
    private function IsTeamsEnabled()
    {
        return config('laratrust.teams.enabled', false);
    }
}
