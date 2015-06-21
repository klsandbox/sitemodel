<?php

namespace Klsandbox\SiteModel;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Console\Command;
use DB;
use Config;
use Schema;

class SiteAppend extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'site:append';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Appends unique.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire() {

        $now = new \Carbon\Carbon();
        $str = $now->toDateTimeString();
        $str = preg_replace('/\-| /', '_', $str);
        $str = preg_replace('/:/', '', $str);

        // TODO: Doesnt handle ondelete and onupdate
        $this->comment("site:append");

        $writeToFile = true;
        $file = [];

        $anyTableHasData = false;

        if ($writeToFile) {
            array_push($file, "
                
                <?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AppendSite extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
");
        }


        foreach (Config::get('site.tables') as $tableName) {
            $indexList = DB::select(DB::raw("SHOW INDEXES FROM $tableName WHERE NOT Non_unique and Key_Name <> 'PRIMARY' and Key_name not like '%site_id%'"));

            $indexGroup = collect($indexList)->groupBy(function ($e) {
                return $e->Key_name;
            });

            $foreignKeyList = DB::select(DB::raw("select information_schema.key_column_usage.*
FROM information_schema.key_column_usage
WHERE referenced_table_name IS NOT NULL
and TABLE_NAME = '$tableName' and CONSTRAINT_SCHEMA = 'raniagold'"));

            $foreignKeys = [];
            foreach ($foreignKeyList as $fk) {
                $foreignKeys[$fk->COLUMN_NAME] = (object) [
                            'REFERENCED_COLUMN_NAME' => $fk->REFERENCED_COLUMN_NAME,
                            'REFERENCED_TABLE_NAME' => $fk->REFERENCED_TABLE_NAME,
                            'CONSTRAINT_NAME' => $fk->CONSTRAINT_NAME,
                ];
            }

            $command = $this;

            if (!$writeToFile) {
                Schema::table($tableName, function(Blueprint $table) use ($indexGroup, $tableName, $foreignKeys, $command) {
                    $command->comment("Table $tableName");

                    if (!Schema::hasColumn($tableName, 'site_id')) {
                        $table->integer('site_id')->unsigned();
                        $table->foreign('site_id')->references('id')->on('sites');
                    }

                    foreach ($indexGroup as $index) {
                        $keyName = $index[0]->Key_name;

                        $list = ['site_id'];
                        foreach ($index as $i) {

                            if (array_has($foreignKeys, $i->Column_name)) {
                                $fk = $foreignKeys[$i->Column_name];
                                $table->dropForeign($fk->CONSTRAINT_NAME);
                            }

                            array_push($list, $i->Column_name);
                        }

                        $command->comment("Drop $keyName");
                        $table->dropUnique($keyName);

                        foreach ($index as $i) {
                            if (array_has($foreignKeys, $i->Column_name)) {
                                $fk = $foreignKeys[$i->Column_name];

                                $this->comment("\$table->foreign($i->Column_name)->reference($fk->REFERENCED_COLUMN_NAME)->on($fk->REFERENCED_TABLE_NAME);");
                                $table->foreign($i->Column_name)->references($fk->REFERENCED_COLUMN_NAME)->on($fk->REFERENCED_TABLE_NAME);
                            }
                        }

                        $command->comment("Add " . implode(',', $list));
                        $table->unique($list);
                    }
                });
            } else {

                $tableData = [];
                $tableHasData = false;

                array_push($tableData, "
                Schema::table('$tableName', function(Blueprint \$table) {
                ");
                
                if (!Schema::hasColumn($tableName, 'site_id')) {
                    $tableHasData = true;
                    array_push($tableData, "
                        \$table->integer('site_id')->unsigned();
                        \$table->foreign('site_id')->references('id')->on('sites');
                ");
                }

                foreach ($indexGroup as $index) {
                    $tableHasData = true;
                    $keyName = $index[0]->Key_name;

                    $list = ['site_id'];
                    foreach ($index as $i) {

                        if (array_has($foreignKeys, $i->Column_name)) {
                            $fk = $foreignKeys[$i->Column_name];
                            array_push($tableData, "
                                \$table->dropForeign('$fk->CONSTRAINT_NAME');
                                    ");
                        }

                        array_push($list, $i->Column_name);
                    }

                    $command->comment("Drop $keyName");
                    array_push($tableData, "
                        \$table->dropUnique('$keyName');
                                    ");

                    foreach ($index as $i) {
                        if (array_has($foreignKeys, $i->Column_name)) {
                            $fk = $foreignKeys[$i->Column_name];

                            $this->comment("\$table->foreign($i->Column_name)->reference($fk->REFERENCED_COLUMN_NAME)->on($fk->REFERENCED_TABLE_NAME);");

                            array_push($tableData, "
    \$table->foreign('$i->Column_name')->references('$fk->REFERENCED_COLUMN_NAME')->on('$fk->REFERENCED_TABLE_NAME');
                                    ");
                        }
                    }

                    $command->comment("Add " . implode(',', $list));

                    $param = "[" . implode(',', array_map(function ($e) {
                                        return "'$e'";
                                    }, $list)) . "]";

                    array_push($tableData, "
                        \$table->unique($param);
                                    ");
                }

                array_push($tableData, "});");

                if ($tableHasData) {
                    $anyTableHasData = true;
                    $file = array_merge($file, $tableData);
                }
            }
        }

        if ($writeToFile) {
            array_push($file, "
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        throw  new Exception('not implemented to rollback site append');
    }
}
");
        }

        $path = database_path('migrations') . "/{$str}_append_site.php";

        if ($anyTableHasData) {
            $this->comment("Writting to $path");
            file_put_contents($path, $file);
        } else {
            $this->comment("No data to write");
        }
    }

}
